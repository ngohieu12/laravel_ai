<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores the MP3 of an audio post on the public disk and removes the previous
 * file whenever it is replaced, so storage does not fill up with orphaned
 * uploads.
 *
 * Mirrors PostImage (same disk, same "delete the old file" contract) but with
 * audio specific validation: only the formats a browser can play inline.
 */
class PostAudio
{
    /**
     * Folder inside the public disk where post audio files live.
     */
    public const DIRECTORY = 'posts/audio';

    /**
     * Disk used for audio files (symlinked to /storage by `php artisan storage:link`).
     */
    public const DISK = 'public';

    /**
     * Accepted audio extensions, matching the validation rule on the form.
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = ['mp3', 'm4a', 'wav', 'ogg'];

    /**
     * Store an uploaded audio file and return its relative path.
     */
    public function store(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = $file->extension() ?: 'mp3';
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
     * Delete a stored audio file (ignores remote URLs and missing files).
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
     * Public URL for a stored path, or null when there is no audio.
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
     * Size of a stored file in bytes, or null when the file is missing (or the
     * value is a remote URL). Used by the MP3 library to show file weights.
     */
    public function size(?string $path): ?int
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        return $disk->exists($path) ? $disk->size($path) : null;
    }

    /**
     * Human readable file weight (e.g. "3.4 MB") for the library screen.
     */
    public function humanSize(?string $path): ?string
    {
        $bytes = $this->size($path);

        return $bytes === null ? null : self::formatBytes($bytes);
    }

    /**
     * Format a byte count for display (e.g. 3_600_000 -> "3.4 MB").
     */
    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float) max($bytes, 0);

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return ($index === 0 ? (string) (int) $value : number_format($value, 1)).' '.$units[$index];
    }

    /**
     * Remove the audio file of a post from disk (used when a post is deleted).
     */
    public function forget(Post $post): void
    {
        $this->delete($post->audio);
    }
}
