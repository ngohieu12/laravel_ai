<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores the single cover image of a post on the public disk and removes the
 * previous file whenever it is replaced, so storage does not fill up with
 * orphaned uploads.
 */
class PostImage
{
    /**
     * Folder inside the public disk where cover images live.
     */
    public const DIRECTORY = 'posts';

    /**
     * Disk used for cover images (symlinked to /storage by `php artisan storage:link`).
     */
    public const DISK = 'public';

    /**
     * @var string[]
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * Store an uploaded cover image and return its relative path.
     */
    public function store(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = $file->extension() ?: 'jpg';
        }

        $name = Str::of(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->limit(40, '')
            ->toString();

        $filename = ($name !== '' ? $name.'-' : '').now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'.'.$extension;

        return $file->storeAs(self::DIRECTORY, $filename, self::DISK);
    }

    /**
     * Delete a stored cover image (ignores remote URLs and missing files).
     */
    public function delete(?string $path): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return;
        }

        $disk = Storage::disk(self::DISK);

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /**
     * Public URL for a stored path, or null when the post has no image.
     */
    public function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    /**
     * Remove the cover image of a post from disk (used when a post is deleted).
     */
    public function forget(Post $post): void
    {
        $this->delete($post->image);
    }
}
