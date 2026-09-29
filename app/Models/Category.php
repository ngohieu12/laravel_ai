<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-managed registry of post categories.
 *
 * Posts keep their category as a denormalised string so listings and analytics
 * stay simple; renaming a category cascades to every post that uses it and new
 * names picked by authors are registered here automatically.
 */
class Category extends Model
{
    use HasFactory;

    /** Category every video post is filed under by default. */
    public const VIDEO = 'Video';

    /** Category every audio (MP3) post is filed under by default. */
    public const MP3 = 'MP3';

    /**
     * The only categories created out of the box.
     *
     * @var list<string>
     */
    public const DEFAULTS = [
        self::VIDEO,
        self::MP3,
    ];

    /**
     * Content type each default category is meant for.
     *
     * @var array<string, string>
     */
    public const MEDIA_CONTENT_TYPES = [
        self::VIDEO => Post::CONTENT_TYPE_VIDEO,
        self::MP3 => Post::CONTENT_TYPE_AUDIO,
    ];

    protected $fillable = [
        'name',
    ];

    /**
     * Create the default categories when they are missing.
     *
     * Called by the seeder and by the admin screen, so a database that lost
     * them (deleted by hand, fresh install without seeding) still gets the
     * Video / MP3 buckets back.
     */
    public static function ensureDefaults(): void
    {
        foreach (self::DEFAULTS as $name) {
            static::query()->firstOrCreate(['name' => $name]);
        }
    }

    /**
     * Default category for a content type (null for written articles, which
     * keep the free-form category the author picked).
     */
    public static function defaultFor(?string $contentType): ?string
    {
        $name = array_search((string) $contentType, self::MEDIA_CONTENT_TYPES, true);

        return $name === false ? null : (string) $name;
    }

    /**
     * Categories with their number of posts (name => count).
     *
     * @return array<string, int>
     */
    public static function counts(): array
    {
        return Post::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->all();
    }

    /**
     * Make sure the given category name exists in the registry.
     */
    public static function register(string $name): ?Category
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        return static::query()->firstOrCreate(['name' => $name]);
    }

    /**
     * Rename this category and cascade the new name to its posts.
     */
    public function rename(string $name): void
    {
        $name = trim($name);

        if ($name === '' || $name === $this->name) {
            return;
        }

        $existing = static::query()->where('name', $name)->first();

        Post::query()->where('category', $this->name)->update(['category' => $name]);

        if ($existing) {
            // Target name already existed — this row is now empty and redundant.
            $this->delete();

            return;
        }

        $this->update(['name' => $name]);
    }

    /**
     * Posts filed under this category (matched by name, the stored value).
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category', 'name');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
