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
        'message', 
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

    protected $appends = ['attachment_url'];

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
