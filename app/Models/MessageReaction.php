<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'user_id',
        'reaction',
    ];

    protected $appends = ['user_name'];

    /**
     * Get the message this reaction belongs to.
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who reacted.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Appended: get user's name for frontend tooltips.
     */
    public function getUserNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }
}
