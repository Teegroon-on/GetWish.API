<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Wish extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     *  Get the wishlist associated with wish.
     */
    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function reservation()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Register thumbs to wish theme media.
     *
     * @param \Spatie\MediaLibrary\MediaCollections\Models\Media|null $media
     * @return void
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('wishThumb')
            ->performOnCollections('wishes')
            ->width(200)
            ->height(200);
        $this->addMediaConversion('fullThumb')
            ->performOnCollections('wishes')
            ->width(1125)
            ->height(750);
    }

    /**
     * Main image of wish.
     *
     * @return string|null
     */
    public function mainImage() {
        $image = $this->getFirstMediaUrl('wishes', 'wishThumb');
        if ($image === '')
            return null;
        return $image;
    }

    /**
     * Images of wish.
     *
     * @return array
     */
    public function images() {
        $images = $this->getMedia('wishes');
        $resultImages = [];
        foreach ($images as $image) {
            $resultImages[] = $image->getUrl('fullThumb');
        }
        return $resultImages;
    }

    /**
     * Get images of wish with full params.
     *
     * @return array
     */
    public function imagesFull() {
        return $this->getMedia('wishes');
    }

    /**
     * Add image of wish.
     *
     * @param  string  $base64Image
     * @return Media|null
     */
    public function addImageFromBase64(string $base64Image): Media {
        try {
            return $this->addMediaFromBase64($base64Image)->toMediaCollection('wishes');
        } catch (FileCannotBeAdded $throw) {
            return null;
        }
    }

    /**
     * Delete image of wish.
     *
     * @return void
     */
    public function deleteImage(int $index): void {
        $images = $this->getMedia('wishes');
        if (count($images) < $index) {
            throw new Exception('Index must be lower then count of images', 461);
        }
        try {
            $images[$index]->delete();
        } catch (\Throwable $th) {
            throw new Exception('Cannot delete image', 462);
        }
    }
}
