<?php

namespace App\Models;

use Database\Factories\SiteMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['key', 'image_path', 'link_url', 'is_active'])]
class SiteMedia extends Model
{
    /** @use HasFactory<SiteMediaFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (SiteMedia $siteMedia) {
            $siteMedia->deleteStoredImage();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether image_path holds an externally hosted URL rather than a path
     * on the local `public` disk (i.e. the admin pasted a link instead of
     * uploading a file).
     */
    public function hasExternalImageUrl(): bool
    {
        return (bool) $this->image_path && Str::startsWith($this->image_path, ['http://', 'https://']);
    }

    /**
     * Delete the currently stored image from the `public` disk, if it's a
     * locally uploaded file. Externally linked URLs aren't ours to delete.
     */
    public function deleteStoredImage(): void
    {
        if ($this->image_path && ! $this->hasExternalImageUrl()) {
            Storage::disk('public')->delete($this->image_path);
        }
    }
}
