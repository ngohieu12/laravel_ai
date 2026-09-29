<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quản lý danh mục Video & MP3 — the two categories the app ships with.
 *
 * They are the buckets video and audio posts are filed under, so this screen
 * shows how many posts each one holds, how many of them actually match the
 * matching content type, and lets the admin rename the category (renaming
 * cascades to every post using it).
 *
 * The buckets are re-created on every visit, so deleting one by hand is never
 * fatal.
 */
class AdminMediaCategoryController extends Controller
{
    /**
     * Posts listed per category.
     */
    private const PER_CATEGORY = 10;

    public function index(Request $request): View
    {
        Category::ensureDefaults();

        $selected = $request->input('bucket');
        $selected = in_array($selected, Category::DEFAULTS, true) ? $selected : null;

        $names = $selected !== null ? [$selected] : Category::DEFAULTS;

        $counts = Category::counts();

        $buckets = collect($names)->map(function (string $name) use ($counts): array {
            $contentType = Category::MEDIA_CONTENT_TYPES[$name] ?? null;

            $posts = Post::query()
                ->where('category', $name)
                ->with('user')
                ->latest('updated_at')
                ->limit(self::PER_CATEGORY)
                ->get();

            return [
                'name' => $name,
                'content_type' => $contentType,
                'total' => (int) ($counts[$name] ?? 0),
                'published' => Post::query()->where('category', $name)->published()->count(),
                'matching_type' => $contentType === null
                    ? 0
                    : Post::query()->where('category', $name)->ofType($contentType)->count(),
                'posts' => $posts,
            ];
        })->all();

        return view('admin.media-categories.index', compact('buckets', 'selected'));
    }
}
