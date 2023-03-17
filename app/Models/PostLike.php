<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostLike extends Model
{
    use HasFactory;

    /**
     * Get the user related with like.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the post related with like.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
