<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /**
     *  Get the user associated with friend.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     *  Get the user associated with friend.
     */
    public function friend()
    {
        return $this->hasOne(User::class, 'id', 'user_friend_id');
    }
}
