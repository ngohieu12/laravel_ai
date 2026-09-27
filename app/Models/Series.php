<?php

namespace App\Models;

use App\Services\PostImage;
use Database\Factories\SeriesFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An ordered set of posts about one topic — a "chuỗi bài viết dài kỳ".
 *
 * The series is the identity: posts reference it by id and order themselves
 * with `series_part`, so renaming the title never splits a series in two.
 * Whether a part is worth reading in depth is flagged by hand on the post
 * (`is_long_form`) instead of being inferred from its length.
 *
 * The cover image belongs to the series itself rather than to its first part,
 * so the list screen keeps a stable face for the whole series.
 */
class Series extends Model
{
    /** @use HasFactory<SeriesFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'image_alt',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * Resolved cover URL plus the `image` value it was resolved from, so the
     * views can call imageUrl() repeatedly at no cost while still seeing a
     * fresh value if the attribute changes.
     */
    private ?string $imageUrlCache = null;

    private string $imageUrlCacheKey = "\0";

    /**
     * The posts of this series, in reading order.
     *
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)
            ->whereNotNull('series_part')
            ->orderBy('series_part')
            ->orderBy('id');
    }

    /**
     * The creator of the series, when known.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Published parts only, still in reading order.
     *
     * @return Builder<Post>
     */
    public function scopePublishedParts(Builder $query): Builder
    {
        return $query->whereNotNull('series_part')
            ->where('is_published', true)
            ->orderBy('series_part')
            ->orderBy('id');
    }

    /**
     * Public URL of the series cover image (null when the series has none).
     */
    public function imageUrl(): ?string
    {
        $key = (string) $this->image;

        if ($this->imageUrlCacheKey !== $key) {
            $this->imageUrlCache = app(PostImage::class)->url($this->image);
            $this->imageUrlCacheKey = $key;
        }

        return $this->imageUrlCache;
    }

    /**
     * Alt text of the cover image, falling back to the series title.
     */
    public function imageAlt(): string
    {
        $alt = trim((string) $this->image_alt);

        return $alt !== '' ? $alt : (string) $this->title;
    }

    /**
     * The blurb shown on the public list: the series description, falling back
     * to the summary of the opening part.
     */
    public function blurb(): string
    {
        if (filled($this->description)) {
            return (string) $this->description;
        }

        return (string) ($this->posts()->published()->orderBy('series_part')->value('summary') ?? '');
    }

    /**
     * True when at least one part is published, i.e. the series is readable.
     */
    public function isPublic(): bool
    {
        return $this->posts()->published()->exists();
    }
}
