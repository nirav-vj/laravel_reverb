<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'creator_id',
        'avatar'
    ];

    protected $appends = ['avatar_url', 'is_group'];

    /**
     * Get the members of the group.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
                    ->withPivot('role', 'joined_at', 'last_read_at')
                    ->withTimestamps();
    }

    /**
     * Get the messages sent to this group.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the creator of the group.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Appended attribute to determine if this item is a group.
     */
    public function getIsGroupAttribute(): bool
    {
        return true;
    }

    /**
     * Get the full URL for the group avatar or initials fallback.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=0D9488&color=fff&size=200&bold=true';
    }
}
