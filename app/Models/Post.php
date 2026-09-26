<?php

namespace App\Models;

use App\Services\PostImage;
use App\Support\VideoUrl;
use App\Support\VietnameseText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    /** Plain written-article content (default). */
    public const CONTENT_TYPE_TEXT = 'text';

    /** Embedded-video content (YouTube / Vimeo link). */
    public const CONTENT_TYPE_VIDEO = 'video';

    /**
     * Content types a post may use.
     *
     * @var list<string>
     */
    public const CONTENT_TYPES = [
        self::CONTENT_TYPE_TEXT,
        self::CONTENT_TYPE_VIDEO,
    ];

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'content_type',
        'video_url',
        'series_title',
        'series_part',
        'is_long_form',
        'image',
        'image_alt',
        'category',
        'user_id',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'user_id' => 'integer',
        'series_part' => 'integer',
        'is_long_form' => 'boolean',
        'views_count' => 'integer',
        'shares_count' => 'integer',
        'favorites_count' => 'integer',
        'comments_count' => 'integer',
    ];

    /**
     * Relative weight of each interaction, used to rank "hot" content.
     *
     * @var array<string, float>
     */
    public const ENGAGEMENT_WEIGHTS = [
        'views' => 1.0,
        'shares' => 4.0,
        'favorites' => 6.0,
        'comments' => 3.0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolved cover URL plus the `image` value it was resolved from, so the
     * views can call imageUrl() repeatedly (meta tags + cover) at no cost while
     * still seeing a fresh value if the attribute changes.
     */
    private ?string $imageUrlCache = null;

    private string $imageUrlCacheKey = '\0';

    /**
     * Public URL of the post's cover image (null when the post has none).
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
     * Alt text of the cover image, falling back to the post title.
     */
    public function imageAlt(): string
    {
        $alt = trim((string) $this->image_alt);

        return $alt !== '' ? $alt : (string) $this->title;
    }

    // ------------- Content type & series (bài viết dài kỳ) -------------

    /**
     * Whether this post carries an embedded video (link YouTube / Vimeo).
     */
    public function isVideo(): bool
    {
        return $this->content_type === self::CONTENT_TYPE_VIDEO;
    }

    /**
     * Whether this post belongs to a long-running series (chuỗi bài dài kỳ).
     */
    public function isSeries(): bool
    {
        return filled($this->series_title);
    }

    /**
     * Embeddable player URL for video posts (null for text posts or links
     * that cannot be embedded).
     */
    public function videoEmbedUrl(): ?string
    {
        return VideoUrl::embedUrl($this->video_url);
    }

    /**
     * Query the posts of the same series, ordered by part number.
     *
     * Drafts are hidden from readers and shown only to whoever manages the
     * post (its author and admins).
     */
    public function seriesPartsQuery(bool $includeDrafts = false): Builder
    {
        return static::query()
            ->where('series_title', $this->series_title)
            ->when(! $includeDrafts, fn (Builder $query) => $query->published())
            ->orderBy('series_part')
            ->orderBy('id');
    }

    /**
     * The published (or manageable) posts of this series, ordered by part.
     *
     * @return Collection<int, Post>
     */
    public function seriesParts(bool $includeDrafts = false): Collection
    {
        if (! $this->isSeries()) {
            return new Collection;
        }

        return $this->seriesPartsQuery($includeDrafts)->get();
    }

    /**
     * Comments on this post (all levels).
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Tags (thẻ) attached to this post.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps()->orderBy('tags.name');
    }

    /**
     * Replace this post's tags with the given comma separated names (or
     * array of names), creating tags that do not exist yet.
     *
     * @param  string|array<int, string>|null  $names
     */
    public function syncTags(string|array|null $names): void
    {
        $this->tags()->sync(Tag::resolveMany($names)->pluck('id')->all());

        $this->unsetRelation('tags');
    }

    /**
     * Users who favorited this post.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'post_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Users who pinned (ghim / lưu lại) this post.
     */
    public function pinnedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_pins', 'post_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Engagement events recorded for this post (views, shares, favorites, comments).
     */
    public function events(): HasMany
    {
        return $this->hasMany(PostEvent::class);
    }

    /**
     * Count of users who favorited this post.
     */
    public function favoritesCount(): int
    {
        return $this->favoritedBy()->count();
    }

    /**
     * Count of users who pinned (lưu) this post.
     */
    public function savesCount(): int
    {
        return $this->pinnedBy()->count();
    }

    /**
     * Check if a given user has pinned this post.
     */
    public function isPinnedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->pinnedBy()->where('user_id', $user->id)->exists();
    }

    /**
     * Whether the given user may edit / delete / analyse this post:
     * admins manage everything, creators manage their own posts.
     */
    public function isManagedBy(User $user): bool
    {
        return $user->isAdmin() || ($user->isCreator() && $this->user_id === $user->id);
    }

    /**
     * Total interactions recorded for this post.
     */
    public function totalInteractions(): int
    {
        return (int) $this->views_count + (int) $this->shares_count + (int) $this->favorites_count + (int) $this->comments_count;
    }

    /**
     * Weighted engagement score — the number used to decide what is "hot".
     */
    public function engagementScore(): float
    {
        return round(
            ((int) $this->views_count * self::ENGAGEMENT_WEIGHTS['views'])
            + ((int) $this->shares_count * self::ENGAGEMENT_WEIGHTS['shares'])
            + ((int) $this->favorites_count * self::ENGAGEMENT_WEIGHTS['favorites'])
            + ((int) $this->comments_count * self::ENGAGEMENT_WEIGHTS['comments']),
            1,
        );
    }

    /**
     * Check if a given user has favorited this post.
     */
    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favoritedBy()->where('user_id', $user->id)->exists();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = $post->generateSlug($post->title);
            }
        });

        static::saved(function (Post $post) {
            // Keep the category registry in sync with the values posts use.
            Category::register((string) $post->category);
        });

        static::updating(function (Post $post) {
            if ($post->isDirty('title') && ! $post->isDirty('slug')) {
                $post->slug = $post->generateSlug($post->title, $post->id);
            }
        });

        // The public series slug is derived, never entered by hand, so it always
        // follows the series title. Every part of a series shares one slug.
        static::saving(function (Post $post) {
            if ($post->isDirty('series_title')) {
                $post->series_slug = $post->generateSeriesSlug((string) $post->series_title);
            }
        });
    }

    /**
     * Estimated reading time in minutes at 200 words per minute.
     *
     * Words are split on whitespace with the /u modifier so Vietnamese text is
     * counted correctly instead of being measured in bytes.
     */
    public function readingMinutes(): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags((string) $this->content)), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($words ?: []) / 200));
    }

    /**
     * Slug identifying a series on the public screens.
     *
     * Deterministic for a given title, so every part of the same series resolves
     * to the same URL. Two different titles that fold onto the same ASCII form
     * are disambiguated with a counter.
     */
    private function generateSeriesSlug(string $title): ?string
    {
        $title = trim($title);

        if ($title === '') {
            return null;
        }

        $base = Str::slug(VietnameseText::toAscii($title)) ?: 'chuoi-bai-viet';
        $slug = $base;
        $suffix = 1;

        while (static::where('series_slug', $slug)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->where('series_title', '!=', $title)
            ->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    private function generateSlug(string $title, ?int $excludeId = null): string
    {
        // Transliterate Vietnamese characters to ASCII.
        $slug = Str::slug(VietnameseText::toAscii($title));

        if ($slug === '') {
            $slug = 'post-'.time();
        }

        // Ensure uniqueness.
        $query = static::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            $slug .= '-'.time();
        }

        return $slug;
    }

    /**
     * Scope to the posts that belong to a series, in reading order.
     */
    public function scopeInSeries(Builder $query, string $slug): Builder
    {
        return $query->where('series_slug', $slug)
            ->whereNotNull('series_part')
            ->orderBy('series_part');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Posts created/updated since the given moment.
     */
    public function scopeSince(Builder $query, ?\DateTimeInterface $since): Builder
    {
        return $since ? $query->where('created_at', '>=', $since) : $query;
    }

    /**
     * Order by total interactions (views + shares + favorites + comments).
     */
    public function scopeMostInteracted(Builder $query): Builder
    {
        return $query->orderByRaw('(views_count + shares_count + favorites_count + comments_count) DESC');
    }

    /**
     * Posts carrying the tag with the given slug.
     */
    public function scopeWithTag(Builder $query, string $slug): Builder
    {
        return $query->whereHas('tags', fn (Builder $q) => $q->where('tags.slug', $slug));
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%");
        });
    }
}
