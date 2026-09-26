<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Public reading surface for post-based series.
 *
 * A series is not a table of its own: it is the set of posts sharing a
 * `series_title`, ordered by `series_part`. The public slug on those posts is
 * what ties them together and gives the series a shareable URL.
 */
class PublicSeriesController extends Controller
{
    /**
     * List every series that has at least one published part.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $series = $this->publishedSeries()
            ->filter(function (array $row) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str_contains(
                    mb_strtolower($row['title'].' '.$row['description']),
                    mb_strtolower($search),
                );
            })
            ->values();

        return view('series.index', compact('series', 'search'));
    }

    /**
     * Show one series with its parts in reading order.
     */
    public function show(string $series): View
    {
        $parts = Post::query()
            ->published()
            ->inSeries($series)
            ->with(['user', 'tags'])
            ->withCount('comments')
            ->get();

        abort_if($parts->isEmpty(), 404);

        $seriesTitle = (string) $parts->first()->series_title;

        return view('series.show', [
            'seriesTitle' => $seriesTitle,
            'parts' => $parts,
            'totalMinutes' => (int) $parts->sum(fn (Post $post): int => $post->readingMinutes()),
            'longFormCount' => $parts->where('is_long_form', true)->count(),
        ]);
    }

    /**
     * Summarise the published series, most recently updated first.
     *
     * Parts are pulled in one query and then grouped in PHP: the counts and the
     * reading time are per series, which a grouped aggregate cannot express
     * alongside the searchable title.
     *
     * @return Collection<int, array{slug: string, title: string, description: string, parts_count: int, long_form_count: int, minutes: int, updated_at: string}>
     */
    private function publishedSeries(): Collection
    {
        $parts = Post::query()
            ->published()
            ->whereNotNull('series_slug')
            ->whereNotNull('series_title')
            ->where('series_title', '!=', '')
            ->get(['id', 'series_slug', 'series_title', 'series_part', 'is_long_form', 'content', 'summary', 'updated_at']);

        return $parts
            ->groupBy('series_slug')
            ->map(function (Collection $group): array {
                $first = $group->sortBy('series_part')->first();

                return [
                    'slug' => (string) $group->first()->series_slug,
                    'title' => (string) $first->series_title,
                    'description' => $this->describe($group),
                    'parts_count' => $group->count(),
                    'long_form_count' => $group->where('is_long_form', true)->count(),
                    'minutes' => (int) $group->sum(fn (Post $post): int => $post->readingMinutes()),
                    'updated_at' => (string) $group->max('updated_at'),
                ];
            })
            ->sortByDesc('updated_at')
            ->values();
    }

    /**
     * Use the summary of the opening part as the series blurb.
     *
     * @param  Collection<int, Post>  $group
     */
    private function describe(Collection $group): string
    {
        $opening = $group->sortBy('series_part')->first();

        return (string) ($opening?->summary ?? '');
    }
}
