<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public reading surface for long-form series.
 *
 * Both screens resolve a series through the Series model by id, so a series can
 * be renamed freely without breaking its URL.
 */
class PublicSeriesController extends Controller
{
    /**
     * List the series that have at least one published part.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $series = Series::query()
            ->withCount('posts')
            ->withCount(['posts as published_parts_count' => fn ($query) => $query->published()])
            ->withCount(['posts as long_form_parts_count' => fn ($query) => $query->where('is_long_form', true)])
            ->whereHas('posts', fn ($query) => $query->published())
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->latest('updated_at')
            ->get()
            ->map(function (Series $series): array {
                $parts = $series->posts()->published()->get(['posts.id', 'posts.content']);

                return [
                    'id' => $series->id,
                    'title' => $series->title,
                    'description' => $series->blurb(),
                    'parts_count' => $series->published_parts_count,
                    'long_form_count' => $series->long_form_parts_count,
                    'minutes' => (int) $parts->sum(fn (Post $post): int => $post->readingMinutes()),
                ];
            });

        return view('series.index', compact('series', 'search'));
    }

    /**
     * Show one series with its parts in reading order.
     */
    public function show(Series $series): View
    {
        $parts = $series->posts()
            ->published()
            ->with(['user', 'tags'])
            ->withCount('comments')
            ->get();

        abort_if($parts->isEmpty(), 404);

        return view('series.show', [
            'series' => $series,
            'parts' => $parts,
            'totalMinutes' => (int) $parts->sum(fn (Post $post): int => $post->readingMinutes()),
            'longFormCount' => $parts->where('is_long_form', true)->count(),
        ]);
    }
}
