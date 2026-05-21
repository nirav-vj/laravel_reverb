<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessagesSeen;
use App\Events\ReactionUpdated;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
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
        $currentUser = Auth::user();
        
        // Get all other users, mapped with their last message and unread count
        $users = User::where('id', '!=', $currentUser->id)
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
            })
            // Sort by the latest message time or creation time if no messages exist
            ->sortByDesc(function ($user) {
                return $user->last_message ? $user->last_message->created_at->timestamp : $user->created_at->timestamp;
            })
            ->values();

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
            'message' => $message->load('sender'),
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
        $request->validate(['receiver_id' => 'required|integer']);
        $currentUser = Auth::user();
        
        // Cache that current user is typing to receiver_id for 3 seconds
        \Illuminate\Support\Facades\Cache::put(
            "typing_{$currentUser->id}_{$request->receiver_id}", 
            true, 
            3
        );

        return response()->json(['success' => true]);
    }

    /**
     * Polling fallback endpoint to fetch updates for the sidebar.
     */
    public function pollUpdates()
    {
        $currentUser = Auth::user();
        
        $users = User::where('id', '!=', $currentUser->id)
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
                    'unread_count' => $unreadCount,
                    'is_online' => $user->is_online,
                    'is_typing' => $isTyping,
                    'last_message' => $lastMessage ? $lastMessage->load('sender') : null,
                ];
            });

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
}
