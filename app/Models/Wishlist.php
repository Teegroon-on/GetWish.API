<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function friends() {
        return $this->belongsToMany(User::class);
    }

    public function wishes()
    {
        return $this->hasMany(Wish::class);
    }

    public function theme() {
        return $this->belongsTo(WishlistTheme::class, 'wishlist_theme_id');
    }
}
