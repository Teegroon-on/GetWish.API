<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'phone',
        'code',
    ];

    /**
     * Get the user that owns the sms code.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
