<?php

namespace App\Models;

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
 */
class Series extends Model
{
    /** @use HasFactory<SeriesFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

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
