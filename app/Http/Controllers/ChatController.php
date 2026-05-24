<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessagesSeen;
use App\Events\ReactionUpdated;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Show the main chat dashboard.
     */
    public function index()
    {
        // Automatically run migrations if groups table doesn't exist
        if (!\Illuminate\Support\Facades\Schema::hasTable('groups')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                \Illuminate\Support\Facades\Log::info('Migrations executed automatically from ChatController.');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Auto-migration failed: ' . $e->getMessage());
            }
        }

        // Dynamically patch last_read_at column on group_members pivot if it is missing
        if (\Illuminate\Support\Facades\Schema::hasTable('group_members') && 
            !\Illuminate\Support\Facades\Schema::hasColumn('group_members', 'last_read_at')) {
            try {
                \Illuminate\Support\Facades\Schema::table('group_members', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->timestamp('last_read_at')->nullable()->after('joined_at');
                });
                \Illuminate\Support\Facades\Log::info('Added missing last_read_at column to group_members table.');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Dynamic column patch failed: ' . $e->getMessage());
            }
        }

        $currentUser = Auth::user();
        
        // 1. Get all other users, mapped with their last message and unread count
        $usersList = User::where('id', '!=', $currentUser->id)
            ->get()
            ->map(function ($user) use ($currentUser) {
                // Get the last message in this conversation
                $lastMessage = Message::where(function ($q) use ($user, $currentUser) {
                    $q->where('sender_id', $currentUser->id)->where('receiver_id', $user->id);
                })->orWhere(function ($q) use ($user, $currentUser) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $currentUser->id);
                })->latest()->first();

                $user->last_message = $lastMessage;

                // Count unread messages sent by this specific contact to the logged-in user
                $user->unread_count = Message::where('sender_id', $user->id)
                    ->where('receiver_id', $currentUser->id)
                    ->where('is_seen', false)
                    ->count();

                return $user;
            });

        // 2. Get all groups current user belongs to
        $groupsList = $currentUser->groups()->get()->map(function ($group) use ($currentUser) {
            // Get the last message in this group
            $lastMessage = Message::where('group_id', $group->id)->latest()->first();
            $group->last_message = $lastMessage;

            // Count unread group messages sent after the current user joined and after their last_read_at
            $joinedAt = $group->pivot->joined_at;
            $lastReadAt = $group->pivot->last_read_at;

            $unreadQuery = Message::where('group_id', $group->id)
                ->where('sender_id', '!=', $currentUser->id)
                ->where('created_at', '>=', $joinedAt);

            if ($lastReadAt) {
                $unreadQuery->where('created_at', '>', $lastReadAt);
            }

            $group->unread_count = $unreadQuery->count();

            return $group;
        });

        // 3. Merge and sort by latest message time
        $users = collect($usersList)->concat($groupsList)->sortByDesc(function ($item) {
            return $item->last_message ? $item->last_message->created_at->timestamp : $item->created_at->timestamp;
        })->values();

        return view('chat.index', compact('users'));
    }

    /**
     * Get conversation history and mark unread messages as read.
     */
    public function getMessages(User $user)
    {
        $currentUser = Auth::user();

        // Retrieve messages between current user and target user
        $messages = Message::where(function ($q) use ($user, $currentUser) {
            $q->where('sender_id', $currentUser->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user, $currentUser) {
            $q->where('sender_id', $user->id)->where('receiver_id', $currentUser->id);
        })->orderBy('created_at', 'asc')->get()
        ->map(function ($msg) use ($currentUser) {
            $deletedBy = $msg->deleted_by ?? [];
            if (in_array($currentUser->id, $deletedBy)) {
                return null; // Exclude message if deleted for me
            }
            if ($msg->is_deleted_for_everyone) {
                $msg->message = '🚫 This message was deleted';
                $msg->attachment_path = null;
                $msg->attachment_name = null;
                $msg->attachment_type = null;
            }
            return $msg;
        })->filter()->values();

        // Mark messages sent by the target user to current user as seen
        $unreadMessages = Message::where('sender_id', $user->id)
            ->where('receiver_id', $currentUser->id)
            ->where('is_seen', false);

        $unreadCount = $unreadMessages->count();

        if ($unreadCount > 0) {
            $now = now()->toDateTimeString();
            $unreadMessages->update([
                'is_seen' => true,
                'seen_at' => $now,
            ]);

            /*
            try {
                // Broadcast seen status back to the sender
                broadcast(new MessagesSeen($user->id, $currentUser->id, $now))->toOthers();
            } catch (\Exception $e) {
                // Ignore broadcast failures silently
            }
            */
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'contact' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'is_online' => $user->is_online,
                'last_seen' => $user->last_seen_at ? $user->last_seen_at->diffForHumans() : 'offline',
            ]
        ]);
    }

    /**
     * Send a new message, handling file uploads and triggering live WebSockets.
     */
    public function sendMessage(Request $request, User $user)
    {
        $request->validate([
            'message' => 'nullable|string',
            'parent_id' => 'nullable|exists:messages,id',
            'attachment' => 'nullable|file|max:10240', // Max 10MB
        ]);

        if (!$request->message && !$request->hasFile('attachment')) {
            return response()->json(['success' => false, 'error' => 'Message or attachment is required.'], 422);
        }

        $currentUser = Auth::user();
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentType = null;

        // Process attachments
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('attachments', 'public');
            
            // Categorize file types for specialized UI bubbles
            $mime = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());
            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
            } elseif (
                str_starts_with($mime, 'audio/') || 
                in_array($extension, ['webm', 'mp3', 'wav', 'ogg', 'm4a', 'aac']) ||
                str_starts_with($attachmentName, 'voice_note_')
            ) {
                $attachmentType = 'audio';
            } else {
                $attachmentType = 'document';
            }
        }

        // Save message to database
        $message = Message::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $user->id,
            'message' => $request->message,
            'parent_id' => $request->parent_id,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
            'is_seen' => false,
        ]);

        /*
         // Broadcast to receiver (wrapped in try-catch to prevent crashes if Reverb is offline)
         try {
             broadcast(new MessageSent($message))->toOthers();
         } catch (\Exception $e) {
             \Illuminate\Support\Facades\Log::error('Reverb Broadcast Failed: ' . $e->getMessage());
         }
        */

        return response()->json([
            'success' => true,
            'message' => $message->load(['sender', 'parent.sender']),
        ]);
    }

    /**
     * Mark specific message chain as seen manually.
     */
    public function markAsSeen(User $user)
    {
        $currentUser = Auth::user();
        $now = now()->toDateTimeString();

        $updated = Message::where('sender_id', $user->id)
            ->where('receiver_id', $currentUser->id)
            ->where('is_seen', false)
            ->update([
                'is_seen' => true,
                'seen_at' => $now,
            ]);

        if ($updated > 0) {
            /*
            try {
                broadcast(new MessagesSeen($user->id, $currentUser->id, $now))->toOthers();
            } catch (\Exception $e) {
                // Ignore
            }
            */
        }

        return response()->json(['success' => true]);
    }

    /**
     * Pin or unpin a message.
     */
    public function pinMessage(Message $message)
    {
        $message->update(['is_pinned' => !$message->is_pinned]);
        return response()->json(['success' => true]);
    }

    /**
     * Delete a message (for me or for everyone).
     */
    public function deleteMessage(Request $request, Message $message)
    {
        $request->validate(['type' => 'required|in:me,everyone']);
        $currentUser = Auth::user();

        if ($request->type === 'everyone') {
            if ($message->sender_id !== $currentUser->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            $message->update(['is_deleted_for_everyone' => true]);
        } else {
            $deletedBy = $message->deleted_by ?? [];
            if (!in_array($currentUser->id, $deletedBy)) {
                $deletedBy[] = $currentUser->id;
                $message->update(['deleted_by' => $deletedBy]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Store typing status in cache.
     */
    public function notifyTyping(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|integer',
            'is_group' => 'nullable|boolean',
        ]);
        $currentUser = Auth::user();
        
        if ($request->is_group) {
            \Illuminate\Support\Facades\Cache::put(
                "typing_{$currentUser->id}_group_{$request->receiver_id}", 
                true, 
                3
            );
        } else {
            \Illuminate\Support\Facades\Cache::put(
                "typing_{$currentUser->id}_{$request->receiver_id}", 
                true, 
                3
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * Polling fallback endpoint to fetch updates for the sidebar.
     */
    public function pollUpdates()
    {
        $currentUser = Auth::user();
        
        // 1. Get all other users
        $usersList = User::where('id', '!=', $currentUser->id)
            ->get()
            ->map(function ($user) use ($currentUser) {
                $lastMessage = Message::where(function ($q) use ($user, $currentUser) {
                    $q->where('sender_id', $currentUser->id)->where('receiver_id', $user->id);
                })->orWhere(function ($q) use ($user, $currentUser) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $currentUser->id);
                })->latest()->first();

                $unreadCount = Message::where('sender_id', $user->id)
                    ->where('receiver_id', $currentUser->id)
                    ->where('is_seen', false)
                    ->count();

                // Check if this contact is typing to the current user
                $isTyping = \Illuminate\Support\Facades\Cache::get("typing_{$user->id}_{$currentUser->id}", false);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'is_group' => false,
                    'unread_count' => $unreadCount,
                    'is_online' => $user->is_online,
                    'is_typing' => $isTyping,
                    'last_message' => $lastMessage ? $lastMessage->load('sender') : null,
                ];
            });

        // 2. Get all groups current user belongs to
        $groupsList = $currentUser->groups()->get()->map(function ($group) use ($currentUser) {
            $lastMessage = Message::where('group_id', $group->id)->latest()->first();

            $joinedAt = $group->pivot->joined_at;
            $lastReadAt = $group->pivot->last_read_at;

            $unreadQuery = Message::where('group_id', $group->id)
                ->where('sender_id', '!=', $currentUser->id)
                ->where('created_at', '>=', $joinedAt);

            if ($lastReadAt) {
                $unreadQuery->where('created_at', '>', $lastReadAt);
            }

            $unreadCount = $unreadQuery->count();

            // Check if any group members are typing to this group
            $isTyping = false;
            foreach ($group->members as $member) {
                if ($member->id !== $currentUser->id) {
                    if (\Illuminate\Support\Facades\Cache::get("typing_{$member->id}_group_{$group->id}", false)) {
                        $isTyping = $member->name . ' is typing...';
                        break;
                    }
                }
            }

            return [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'avatar_url' => $group->avatar_url,
                'is_group' => true,
                'unread_count' => $unreadCount,
                'is_online' => true,
                'is_typing' => $isTyping,
                'last_message' => $lastMessage ? $lastMessage->load('sender') : null,
            ];
        });

        // 3. Combine them
        $users = collect($usersList)->concat($groupsList)->values();

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    /**
     * Toggle an emoji reaction on a message.
     * If the same emoji is already set by this user → remove it (toggle off).
     * If a different emoji is set → swap it.
     */
    public function toggleReaction(Request $request, Message $message)
    {
        $request->validate([
            'reaction' => 'required|string|max:10',
        ]);

        $currentUser = Auth::user();

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $currentUser->id)
            ->first();

        if ($existing) {
            if ($existing->reaction === $request->reaction) {
                // Same emoji → remove reaction
                $existing->delete();
            } else {
                // Different emoji → update reaction
                $existing->update(['reaction' => $request->reaction]);
            }
        } else {
            // No existing → create new reaction
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id'    => $currentUser->id,
                'reaction'   => $request->reaction,
            ]);
        }

        // Reload fresh reactions
        $message->refresh()->load('reactions.user');

        $reactionsPayload = $message->reactions->map(fn($r) => [
            'id'        => $r->id,
            'user_id'   => $r->user_id,
            'user_name' => $r->user?->name ?? 'Unknown',
            'reaction'  => $r->reaction,
        ])->values()->all();

        // Broadcast to the OTHER user in the conversation
        $otherId = ($message->sender_id === $currentUser->id)
            ? $message->receiver_id
            : $message->sender_id;

        broadcast(new ReactionUpdated($message, $currentUser->id))->toOthers();

        return response()->json([
            'success'    => true,
            'message_id' => $message->id,
            'reactions'  => $reactionsPayload,
        ]);
    }

    /**
     * Create a new chat group.
     */
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $currentUser = Auth::user();
        
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('groups', 'public');
        }

        // Create group
        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'creator_id' => $currentUser->id,
            'avatar' => $avatarPath,
        ]);

        // Attach creator as admin
        $group->members()->attach($currentUser->id, [
            'role' => 'admin',
            'joined_at' => now(),
            'last_read_at' => now(),
        ]);

        // Attach all selected members
        foreach ($request->members as $memberId) {
            if ($memberId != $currentUser->id) {
                $group->members()->attach($memberId, [
                    'role' => 'member',
                    'joined_at' => now(),
                    'last_read_at' => null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully!',
            'group' => $group->load('members'),
        ]);
    }

    /**
     * Get group conversation history and mark group messages as read.
     */
    public function getGroupMessages(Group $group)
    {
        $currentUser = Auth::user();

        // Ensure current user is a member
        if (!$group->members()->where('user_id', $currentUser->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'You are not a member of this group.',
            ], 403);
        }

        // Fetch messages for this group
        $messages = Message::where('group_id', $group->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($currentUser) {
                $deletedBy = $msg->deleted_by ?? [];
                if (in_array($currentUser->id, $deletedBy)) {
                    return null;
                }
                if ($msg->is_deleted_for_everyone) {
                    $msg->message = '🚫 This message was deleted';
                    $msg->attachment_path = null;
                    $msg->attachment_name = null;
                    $msg->attachment_type = null;
                }
                return $msg;
            })
            ->filter()
            ->values();

        // Update current user's last_read_at
        $group->members()->updateExistingPivot($currentUser->id, [
            'last_read_at' => now(),
        ]);

        // Map group members details for UI
        $members = $group->members->map(fn($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'avatar_url' => $m->avatar_url,
            'role' => $m->pivot->role,
        ]);

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'contact' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'avatar_url' => $group->avatar_url,
                'is_group' => true,
                'members' => $members,
            ]
        ]);
    }

    /**
     * Send a new message to a group.
     */
    public function sendGroupMessage(Request $request, Group $group)
    {
        $request->validate([
            'message' => 'nullable|string',
            'parent_id' => 'nullable|exists:messages,id',
            'attachment' => 'nullable|file|max:10240', // Max 10MB
        ]);

        $currentUser = Auth::user();

        // Ensure current user is a member
        if (!$group->members()->where('user_id', $currentUser->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'You are not a member of this group.',
            ], 403);
        }

        if (!$request->message && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot send an empty message.',
            ], 422);
        }

        // Handle attachment
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('attachments', 'public');
            $attachmentName = $file->getClientOriginalName();
            
            $mime = $file->getMimeType();
            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
            } elseif (str_starts_with($mime, 'audio/')) {
                $attachmentType = 'audio';
            } else {
                $attachmentType = 'document';
            }
        }

        // Save message to database
        $message = Message::create([
            'sender_id' => $currentUser->id,
            'group_id' => $group->id,
            'message' => $request->message,
            'parent_id' => $request->parent_id,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
            'is_seen' => false,
        ]);

        // Update current user's last_read_at
        $group->members()->updateExistingPivot($currentUser->id, [
            'last_read_at' => now(),
        ]);

        // Broadcast to other members if broadcasting works
        /*
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Group Broadcast Failed: ' . $e->getMessage());
        }
        */

        return response()->json([
            'success' => true,
            'message' => $message->load(['sender', 'parent.sender']),
        ]);
    }

    /**
     * Add new members to a group.
     */
    public function addGroupMembers(Request $request, Group $group)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $currentUser = Auth::user();

        // Authorize: must be a member and have 'admin' role in this group
        $pivot = $group->members()->where('user_id', $currentUser->id)->first()?->pivot;
        if (!$pivot || $pivot->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Only group admins can add new members.',
            ], 403);
        }

        // Attach new members
        $newUserIds = array_diff($request->user_ids, $group->members->pluck('id')->toArray());
        if (!empty($newUserIds)) {
            $group->members()->attach($newUserIds, [
                'role' => 'member',
                'joined_at' => now(),
                'last_read_at' => now(),
            ]);
        }

        // Return updated members list
        $members = $group->fresh()->members->map(fn($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'avatar_url' => $m->avatar_url,
            'role' => $m->pivot->role,
        ]);

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    /**
     * Remove a member from a group.
     */
    public function removeGroupMember(Group $group, User $user)
    {
        $currentUser = Auth::user();

        // Authorize: must be 'admin' in this group
        $pivot = $group->members()->where('user_id', $currentUser->id)->first()?->pivot;
        if (!$pivot || $pivot->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Only group admins can remove members.',
            ], 403);
        }

        // Prevent removing yourself
        if ($user->id === $currentUser->id) {
            return response()->json([
                'success' => false,
                'error' => 'You cannot remove yourself from the group.',
            ], 400);
        }

        // Detach member
        $group->members()->detach($user->id);

        // Return updated members list
        $members = $group->fresh()->members->map(fn($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'avatar_url' => $m->avatar_url,
            'role' => $m->pivot->role,
        ]);

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    /**
     * Update a group member's role (e.g. elevate to admin).
     */
    public function assignMemberRole(Request $request, Group $group, User $user)
    {
        $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        $currentUser = Auth::user();

        // Authorize: must be 'admin' in this group
        $pivot = $group->members()->where('user_id', $currentUser->id)->first()?->pivot;
        if (!$pivot || $pivot->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Only group admins can change roles.',
            ], 403);
        }

        // Update role
        $group->members()->updateExistingPivot($user->id, [
            'role' => $request->role,
        ]);

        // Return updated members list
        $members = $group->fresh()->members->map(fn($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'avatar_url' => $m->avatar_url,
            'role' => $m->pivot->role,
        ]);

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }
}
