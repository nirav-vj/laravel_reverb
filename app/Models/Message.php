<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id', 
        'receiver_id', 
        'group_id', 
        'message', 
        'parent_id',
        'attachment_path', 
        'attachment_name', 
        'attachment_type', 
        'is_seen', 
        'seen_at',
        'is_pinned',
        'is_deleted_for_everyone',
        'deleted_by'
    ];

    protected $casts = [
        'is_seen' => 'boolean',
        'seen_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_deleted_for_everyone' => 'boolean',
        'deleted_by' => 'array',
    ];

    protected $appends = ['attachment_url', 'reactions_data'];

    protected $with = ['reactions.user', 'parent', 'sender'];

    /**
     * Get the parent message that this message is replying to.
     */
    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    /**
     * Get the sender of the message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the group context of the message.
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Get the reactions on this message.
     */
    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Appended: returns serialized reactions grouped for the frontend.
     */
    public function getReactionsDataAttribute(): array
    {
        return $this->reactions->map(fn($r) => [
            'id'        => $r->id,
            'user_id'   => $r->user_id,
            'user_name' => $r->user?->name ?? 'Unknown',
            'reaction'  => $r->reaction,
        ])->values()->all();
    }

    /**
     * Get the full URL for the attachment.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->attachment_path) {
            return asset('storage/' . $this->attachment_path);
        }

        return null;
    }
}
