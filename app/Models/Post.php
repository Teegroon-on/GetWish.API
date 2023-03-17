<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * Upload attachment of post
     *
     * @param  string  $requestKey
     * @return Media|null
     */
    public function uploadAttachment(string $requestKey, $ext) {
        try {
            return $this->addMediaFromRequest($requestKey)
                ->setFileName('media.' . $ext)
                ->toMediaCollection('posts');
        } catch (FileCannotBeAdded $throw) {
            return null;
        }
    }

    /**
     * Register thumbs to post media.
     *
     * @param \Spatie\MediaLibrary\MediaCollections\Models\Media|null $media
     * @return void
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('posts')
            ->width(720)
            ->height(720)
            ->fit(Manipulations::FIT_CROP, 720, 720);
    }

    /**
     * Attachments of posts.
     *
     * @return array
     */
    public function attachments() {
        $images = $this->getMedia('posts');
        $resultImages = [];
        foreach ($images as $image) {
            if (strpos($image->getTypeFromMime(), 'image') === false) {
                $resultImages[] = $image->getUrl();
            } else {
                $resultImages[] = $image->getUrl('thumb');
            }
        }
        return $resultImages;
    }

    /**
     * Get the user that owns the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get likes of the post.
     */
    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    /**
     * Get comments of the post.
     */
    public function comments()
    {
        return $this->hasMany(PostComment::class);
    }
}
