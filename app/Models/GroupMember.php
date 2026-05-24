<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupMember extends Pivot
{
    protected $table = 'group_members';

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'joined_at'
    ];

    public $timestamps = true;

    /**
     * Get the user who is a member of the group.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the group the user is a member of.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
