<?php

namespace App\Models;

use App\Support\VietnameseText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Free-form post tags (thẻ / tag).
 *
 * Unlike categories (one per post), a post can carry many tags. Tags are
 * identified by their slug, so "Laravel", "laravel" and " LARAVEL " all map to
 * the same tag. Authors create tags implicitly from the post form; admins
 * rename, merge and delete them from the tag management screen.
 */
class Tag extends Model
{
    use HasFactory;

    /** Maximum length of a single tag name. */
    public const MAX_NAME_LENGTH = 50;

    /** Maximum number of tags a single post may carry. */
    public const MAX_PER_POST = 10;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Posts carrying this tag.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    /**
     * Normalise a user supplied tag name (trim + collapse inner whitespace,
     * strip a leading "#").
     */
    public static function normalizeName(string $name): string
    {
        $name = ltrim(trim($name), '#');

        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * URL-safe, diacritic-free slug used as the tag identity.
     */
    public static function slugFor(string $name): string
    {
        return Str::slug(VietnameseText::toAscii(static::normalizeName($name)));
    }

    /**
     * Split a comma separated tag string (or an array of names) into unique,
     * normalised names — deduplicated by slug, first spelling wins.
     *
     * @param  string|array<int, string>|null  $input
     * @return array<int, string>
     */
    public static function parseNames(string|array|null $input): array
    {
        $parts = is_array($input) ? $input : explode(',', (string) $input);

        $names = [];

        foreach ($parts as $part) {
            $name = static::normalizeName((string) $part);
            $slug = static::slugFor($name);

            if ($name === '' || $slug === '' || isset($names[$slug])) {
                continue;
            }

            $names[$slug] = $name;
        }

        return array_values($names);
    }

    /**
     * Find the tag matching the name (by slug) or create it.
     */
    public static function findOrCreateByName(string $name): ?Tag
    {
        $name = static::normalizeName($name);
        $slug = static::slugFor($name);

        if ($slug === '') {
            return null;
        }

        return static::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
    }

    /**
     * Resolve many names to tag models, creating missing ones.
     *
     * @param  string|array<int, string>|null  $input
     * @return Collection<int, Tag>
     */
    public static function resolveMany(string|array|null $input): Collection
    {
        return collect(static::parseNames($input))
            ->map(fn (string $name): ?Tag => static::findOrCreateByName($name))
            ->filter()
            ->values();
    }

    /**
     * Rename this tag. When another tag already uses the target slug, this tag
     * is merged into it (its posts are moved over) and then deleted.
     *
     * Returns the tag that survives the operation.
     */
    public function renameTo(string $name): Tag
    {
        $name = static::normalizeName($name);
        $slug = static::slugFor($name);

        if ($slug === '') {
            return $this;
        }

        $existing = static::query()->where('slug', $slug)->whereKeyNot($this->getKey())->first();

        if (! $existing) {
            $this->update(['name' => $name, 'slug' => $slug]);

            return $this;
        }

        $this->mergeInto($existing);

        return $existing;
    }

    /**
     * Move every post of this tag onto the target tag, then delete this tag.
     */
    public function mergeInto(Tag $target): void
    {
        if ($target->is($this)) {
            return;
        }

        DB::transaction(function () use ($target): void {
            $postIds = $this->posts()->pluck('posts.id')->all();

            $target->posts()->syncWithoutDetaching($postIds);

            $this->delete();
        });
    }

    /**
     * Delete every tag that is not attached to any post.
     *
     * @return int Number of deleted tags.
     */
    public static function deleteUnused(): int
    {
        return static::query()->doesntHave('posts')->delete();
    }

    /**
     * Comma separated list of names, as shown in the post form.
     *
     * @param  iterable<int, Tag>  $tags
     */
    public static function toInputString(iterable $tags): string
    {
        return collect($tags)->pluck('name')->implode(', ');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Tags whose name or slug contains the search term.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $slug = static::slugFor($search);

        return $query->where(function (Builder $q) use ($search, $slug): void {
            $q->where('name', 'like', '%'.$search.'%');

            if ($slug !== '') {
                $q->orWhere('slug', 'like', '%'.$slug.'%');
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
