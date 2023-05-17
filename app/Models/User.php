<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, InteractsWithMedia;

    protected $fillable = [
        'phone',
    ];

    protected $hidden = [
        'refresh_token',
        'refresh_token_expired_at',
        'remember_token',
    ];

    /**
     * Generate and set refresh token for user.
     *
     * @return string
     */
    public function generateRefreshToken(): string
    {
        $this->refresh_token = hash('sha256', Str::random(64));
        $this->refresh_token_expired_at = now()->addDays(config('sanctum.refresh_token_expiration'));
        return $this->refresh_token;
    }

    /**
     * Register thumb to avatar.
     *
     * @param \Spatie\MediaLibrary\MediaCollections\Models\Media|null $media
     * @return void
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(256)
            ->height(256);
    }

    /**
     * User avatar.
     *
     * @return string|null
     */
    public function avatar() {
        $avatar = $this->getFirstMediaUrl('avatars', 'thumb');
        if ($avatar === '')
            return null;
        return $avatar;
    }

    /**
     * Count of user friends.
     *
     * @return number
     */
    public function friends() {
        return $this->friendships()->wherePivot('status', '=', FriendshipStatus::ACCEPTED)->count();
    }

    /**
     * Count of user posts.
     *
     * @return number
     */
    public function countOfPosts() {
        return $this->posts()->where('active', true)->count();
    }

    /**
     * Count of user wishes.
     *
     * @return number
     */
    public function countOfWishes($user) {
        if ($user->id === $this->id) {
            return Wish::whereIn('wishlist_id', function($query) use ($user) {
                $query->select('id')
                    ->from('wishlists')
                    ->where('wishlists.user_id', $this->id)
                    ->where('is_archive', false);
            })->count();
        } else {
            return Wish::whereIn('wishlist_id', function($query) use ($user) {
                $query->select('id')
                    ->from('wishlists')
                    ->where('wishlists.user_id', $this->id)
                    ->where('is_archive', false)
                    ->where('private', false)
                    ->leftJoin('user_wishlist', 'user_wishlist.wishlist_id', '=', 'wishlists.id')
                    ->orWhere(function($query2) use ($user) {
                        $query2->where('private', true)
                            ->where('user_wishlist.user_id', $user->id)
                            ->where('wishlists.user_id', $this->id);
                    });
            })->count();
        }
    }

    /**
     * User is friend.
     *
     * @return number
     */
    public function isFriend($friend_id) {
        return $this->friendships()
            ->wherePivot('status', '=', FriendshipStatus::ACCEPTED)
            ->wherePivot('user_friend_id', '=', $friend_id)
            ->count() === 1;
    }

    /**
     * User is .
     *
     * @return number
     */
    public function isFriendRequest($friend_id) {
        return $this->friendships()
            ->wherePivot('status', '=', FriendshipStatus::PENDING)
            ->wherePivot('user_friend_id', '=', $friend_id)
            ->count() === 1;
    }

    /**
     * Delete refresh token for user.
     *
     * @return void
     */
    public function deleteRefreshToken(): void
    {
        $this->refresh_token = null;
        $this->refresh_token_expired_at = now();
    }

    /**
     * Add avatar of user.
     *
     * @param  string  $base64Image
     * @return Media|null
     */
    public function setAvatarFromBase64(string $base64Image): Media {
        try {
            $this->deleteAvatar();
            return 'huy';
            return $this->addMediaFromBase64($base64Image)->toMediaCollection('avatars');
        } catch (FileCannotBeAdded $throw) {
            return null;
        }
    }

    /**
     * Delete avatar of user.
     *
     * @return void
     */
    public function deleteAvatar(): void {
        $this->clearMediaCollection('avatars');
    }

    public function posts() {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function wishlists() {
        return $this->hasMany(Wishlist::class, 'user_id');
    }

    public function friendships() {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'user_friend_id')->using(Friendship::class)->withPivot('status');
    }

    public function reverseFriendships() {
        return $this->belongsToMany(User::class, 'friendships', 'user_friend_id', 'user_id')->using(Friendship::class)->withPivot('status');
    }
}
