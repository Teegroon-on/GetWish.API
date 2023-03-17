<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WishlistTheme extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * Register thumbs to wish theme media.
     *
     * @param \Spatie\MediaLibrary\MediaCollections\Models\Media|null $media
     * @return void
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('iconThumb')
            ->performOnCollections('icons')
            ->width(128)
            ->height(128);
        $this->addMediaConversion('thumb')
            ->performOnCollections('images')
            ->width(750)
            ->height(1624);
        $this->addMediaConversion('cardThumb')
            ->performOnCollections('cards')
            ->width(640)
            ->height(276);
        $this->addMediaConversion('previewThumb')
            ->performOnCollections('previews')
            ->width(520)
            ->height(293);
    }

    /**
     * Card of wish theme.
     *
     * @return string|null
     */
    public function card() {
        $icon = $this->getFirstMediaUrl('cards', 'cardThumb');
        if ($icon === '')
            return null;
        return $icon;
    }

    /**
     * Preview of wish theme.
     *
     * @return string|null
     */
    public function preview() {
        $icon = $this->getFirstMediaUrl('previews', 'previewThumb');
        if ($icon === '')
            return null;
        return $icon;
    }

    /**
     * Icon of wish theme.
     *
     * @return string|null
     */
    public function icon() {
        $icon = $this->getFirstMediaUrl('icons', 'iconThumb');
        if ($icon === '')
            return null;
        return $icon;
    }

    /**
     * Image of wish theme.
     *
     * @return string|null
     */
    public function image() {
        $image = $this->getFirstMediaUrl('images', 'thumb');
        if ($image === '')
            return null;
        return $image;
    }

    /**
     * Set icon of wish theme.
     *
     * @param  string  $base64Image
     * @return Media|null
     */
    public function setIconFromBase64(string $base64Image): Media {
        try {
            $this->deleteIcon();
            return $this->addMediaFromBase64($base64Image)->toMediaCollection('icons');
        } catch (FileCannotBeAdded $throw) {
            return null;
        }
    }

    /**
     * Set preview of wish theme.
     *
     * @param  string  $base64Image
     * @return Media|null
     */
    public function setPreviewFromBase64(string $base64Image): Media {
        try {
            $this->deletePreview();
            return $this->addMediaFromBase64($base64Image)->toMediaCollection('previews');
        } catch (FileCannotBeAdded $throw) {
            return null;
        }
    }

    /**
     * Set card of wish theme.
     *
     * @param  string  $base64Image
     * @return Media|null
     */
    public function setCardFromBase64(string $base64Image): Media {
        try {
            $this->deleteCard();
            return $this->addMediaFromBase64($base64Image)->toMediaCollection('cards');
        } catch (FileCannotBeAdded $throw) {
            return null;
        }
    }

    /**
     * Set image of wish theme.
     *
     * @param  string  $base64Image
     * @return Media|null
     */
    public function setImageFromBase64(string $base64Image): Media {
        try {
            $this->deleteImage();
            return $this->addMediaFromBase64($base64Image)->toMediaCollection('images');
        } catch (FileCannotBeAdded $throw) {
            return null;
        }
    }

    /**
     * Delete preview of wish theme.
     *
     * @return void
     */
    public function deletePreview(): void {
        $this->clearMediaCollection('previews');
    }

    /**
     * Delete card of wish theme.
     *
     * @return void
     */
    public function deleteCard(): void {
        $this->clearMediaCollection('cards');
    }

    /**
     * Delete icon of wish theme.
     *
     * @return void
     */
    public function deleteIcon(): void {
        $this->clearMediaCollection('icons');
    }

    /**
     * Delete image of wish theme.
     *
     * @return void
     */
    public function deleteImage(): void {
        $this->clearMediaCollection('images');
    }
}
