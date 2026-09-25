<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostEvent;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Post analytics for creators (and admins working on their own posts):
 * every metric is scoped to the posts the current user has published here.
 */
class CreatorAnalyticsController extends Controller
{
    private const SORTABLE_METRICS = ['engagement', 'views', 'shares', 'favorites', 'comments', 'saves'];

    public function __construct(private readonly AnalyticsService $analytics) {}

    /**
     * Overview of the current user's own posts.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $period = $this->normalizePeriod($request->input('period'));
        $metric = $this->normalizeMetric($request->input('metric'));
        $range = $this->analytics->resolvePeriod($period);

        $posts = Post::query()
            ->where('user_id', $user->id)
            ->withCount(['favoritedBy as favorites_count', 'pinnedBy as saves_count'])
            ->orderByDesc('created_at')
            ->get();

        $postIds = $posts->pluck('id')->all();

        $totals = [
            'views' => (int) $posts->sum('views_count'),
            'shares' => (int) $posts->sum('shares_count'),
            'favorites' => (int) $posts->sum('favorites_count'),
            'comments' => (int) $posts->sum('comments_count'),
            'saves' => (int) $posts->sum('saves_count'),
        ];
        $totals['engagement'] = $this->analytics->engagementScore(
            $totals['views'],
            $totals['shares'],
            $totals['favorites'],
            $totals['comments'],
        );

        $periodTotals = $this->scopedEventTotals($postIds, $range['start'], $range['end']);
        $trend = $this->scopedDailyTrend($postIds, $range);

        $topPosts = $posts
            ->sortByDesc(fn (Post $post): float => match ($metric) {
                'views' => (float) $post->views_count,
                'shares' => (float) $post->shares_count,
                'favorites' => (float) $post->favorites_count,
                'comments' => (float) $post->comments_count,
                'saves' => (float) $post->saves_count,
                default => $post->engagementScore(),
            })
            ->take(10)
            ->values();

        return view('dashboard.analytics.index', [
            'posts' => $posts,
            'totals' => $totals,
            'periodTotals' => $periodTotals,
            'trend' => $trend,
            'topPosts' => $topPosts,
            'period' => $period,
            'metric' => $metric,
            'metrics' => self::SORTABLE_METRICS,
            'periodLabel' => $range['label'],
        ]);
    }

    /**
     * Drill down into one of the current user's posts.
     */
    public function show(Request $request, Post $post): View
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $post->user_id === $user->id, 403, 'Bạn chỉ có thể xem phân tích bài viết của mình.');

        $period = $this->normalizePeriod($request->input('period'));
        $summary = $this->analytics->postSummary($post, $period);
        $summary['totals']['saves'] = $post->savesCount();
        $summary['period_totals']['saves'] = \App\Models\PostPin::query()
            ->where('post_id', $post->id)
            ->when($summary['period']['start'], fn ($q) => $q->where('created_at', '>=', $summary['period']['start']))
            ->count();

        return view('dashboard.analytics.show', [
            'summary' => $summary,
            'post' => $post,
            'period' => $period,
            'topKeywords' => $this->analytics->topKeywords(limit: 10),
            'chatbotQuestions' => [
                'Phân tích tương tác của bài viết "'.$post->title.'"?',
                'Từ khoá nào của bài "'.$post->title.'" đang hút người xem nhất?',
            ],
        ]);
    }

    /**
     * Event totals (views / shares / favorites / comments) for a set of posts.
     *
     * @param  int[]  $postIds
     * @return array<string, int|float>
     */
    private function scopedEventTotals(array $postIds, ?\Carbon\CarbonImmutable $from, ?\Carbon\CarbonImmutable $to): array
    {
        if ($postIds === []) {
            return ['views' => 0, 'shares' => 0, 'favorites' => 0, 'comments' => 0, 'saves' => 0, 'interactions' => 0];
        }

        $rows = PostEvent::query()
            ->whereIn('post_id', $postIds)
            ->whereIn('type', PostEvent::ENGAGEMENT_TYPES)
            ->between($from, $to)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $saves = \App\Models\PostPin::query()
            ->whereIn('post_id', $postIds)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->count();

        $views = (int) ($rows[PostEvent::TYPE_VIEW] ?? 0);
        $shares = (int) ($rows[PostEvent::TYPE_SHARE] ?? 0);
        $favorites = (int) ($rows[PostEvent::TYPE_FAVORITE_ADD] ?? 0);
        $comments = (int) ($rows[PostEvent::TYPE_COMMENT] ?? 0);

        return [
            'views' => $views,
            'shares' => $shares,
            'favorites' => $favorites,
            'comments' => $comments,
            'saves' => $saves,
            'interactions' => $views + $shares + $favorites + $comments + $saves,
        ];
    }

    /**
     * Zero-filled daily series for a set of posts (including saves).
     *
     * @param  int[]  $postIds
     * @return Collection<int, array<string, mixed>>
     */
    private function scopedDailyTrend(array $postIds, array $range): Collection
    {
        $days = $range['days'] ?? 30;

        if ($postIds === [] || $days < 1) {
            return collect();
        }

        $days = min($days, 365);
        $start = $range['end']->copy()->subDays($days - 1)->startOfDay();

        $rows = PostEvent::query()
            ->whereIn('post_id', $postIds)
            ->whereIn('type', PostEvent::ENGAGEMENT_TYPES)
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, type, COUNT(*) as total')
            ->groupBy('day', 'type')
            ->get();

        $savesPerDay = \App\Models\PostPin::query()
            ->whereIn('post_id', $postIds)
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $buckets = [];

        foreach ($rows as $row) {
            $buckets[$row->day][$row->type] = (int) $row->total;
        }

        $series = collect();

        foreach (\Carbon\CarbonPeriod::create($start, '1 day', $range['end']) as $day) {
            $key = $day->format('Y-m-d');
            $views = $buckets[$key][PostEvent::TYPE_VIEW] ?? 0;
            $shares = $buckets[$key][PostEvent::TYPE_SHARE] ?? 0;
            $favorites = $buckets[$key][PostEvent::TYPE_FAVORITE_ADD] ?? 0;
            $comments = $buckets[$key][PostEvent::TYPE_COMMENT] ?? 0;

            $series->push([
                'date' => $key,
                'label' => $day->format('d/m'),
                'views' => $views,
                'shares' => $shares,
                'favorites' => $favorites,
                'comments' => $comments,
                'saves' => (int) ($savesPerDay[$key] ?? 0),
                'engagement' => $this->analytics->engagementScore($views, $shares, $favorites, $comments),
            ]);
        }

        return $series;
    }

    private function normalizePeriod(mixed $period): string
    {
        $period = is_string($period) || is_int($period) ? (string) $period : AnalyticsService::DEFAULT_PERIOD;

        return array_key_exists($period, AnalyticsService::PERIODS) ? $period : AnalyticsService::DEFAULT_PERIOD;
    }

    private function normalizeMetric(mixed $metric): string
    {
        $metric = is_string($metric) ? $metric : 'engagement';

        return in_array($metric, self::SORTABLE_METRICS, true) ? $metric : 'engagement';
    }
}
