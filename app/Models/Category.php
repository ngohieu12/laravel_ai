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

    protected $fillable = [
        'name',
    ];

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
