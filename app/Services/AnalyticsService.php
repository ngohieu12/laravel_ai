<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostEvent;
use App\Models\PostPin;
use App\Models\SearchLog;
use App\Support\KeywordExtractor;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns raw engagement data (post_events + denormalised counters) into the
 * numbers shown on the admin analytics dashboard and returned by the
 * analytics chatbot tools.
 *
 * Two data sources are combined on purpose:
 * - post_events  → anything time-bound (trends, per-period totals, platforms)
 * - post counters → rankings (top posts / topics / keywords), cheap to sort
 */
class AnalyticsService
{
    /**
     * Relative weight of each interaction when computing an engagement score.
     *
     * @var array<string, float>
     */
    public const METRIC_WEIGHTS = [
        'views' => 1.0,
        'shares' => 4.0,
        'favorites' => 6.0,
        'comments' => 3.0,
    ];

    /**
     * Hard cap on posts scanned while ranking keywords.
     */
    private const KEYWORD_SCAN_LIMIT = 500;

    /**
     * @var array<string, int|null> period key => number of days (null = all time)
     */
    public const PERIODS = [
        '7' => 7,
        '30' => 30,
        '90' => 90,
        '365' => 365,
        'all' => null,
    ];

    public const DEFAULT_PERIOD = '30';

    private readonly KeywordExtractor $keywords;

    public function __construct(?KeywordExtractor $keywords = null)
    {
        $this->keywords = $keywords ?? new KeywordExtractor;
    }

    /**
     * Resolve a period key into its start/end boundaries plus the previous
     * window of the same length (used for the change indicators).
     *
     * @return array{key: string, days: int|null, label: string, start: CarbonImmutable|null, end: CarbonImmutable, previous_start: CarbonImmutable|null, previous_end: CarbonImmutable|null}
     */
    public function resolvePeriod(?string $period): array
    {
        $key = array_key_exists((string) $period, self::PERIODS) ? (string) $period : self::DEFAULT_PERIOD;
        $days = self::PERIODS[$key];
        $end = CarbonImmutable::now()->endOfDay();

        if ($days === null) {
            return [
                'key' => 'all',
                'days' => null,
                'label' => 'Toàn bộ thời gian',
                'start' => null,
                'previous_start' => null,
                'previous_end' => null,
                'end' => $end,
            ];
        }

        $start = $end->copy()->subDays($days - 1)->startOfDay();

        return [
            'key' => $key,
            'days' => $days,
            'label' => "{$days} ngày qua",
            'start' => $start,
            'end' => $end,
            'previous_start' => $start->copy()->subDays($days),
            'previous_end' => $start->copy()->subSecond(),
        ];
    }

    /**
     * Headline numbers for the dashboard.
     *
     * @return array<string, mixed>
     */
    public function overview(?string $period = self::DEFAULT_PERIOD): array
    {
        $range = $this->resolvePeriod($period);

        $current = $this->eventTotals($range['start'], $range['end']);
        $previous = $range['previous_start'] === null
            ? null
            : $this->eventTotals($range['previous_start'], $range['previous_end']);

        $totals = [
            'views' => (int) Post::query()->sum('views_count'),
            'shares' => (int) Post::query()->sum('shares_count'),
            'favorites' => (int) Post::query()->sum('favorites_count'),
            'comments' => (int) Post::query()->sum('comments_count'),
            'engagement' => $this->engagementScore(
                (int) Post::query()->sum('views_count'),
                (int) Post::query()->sum('shares_count'),
                (int) Post::query()->sum('favorites_count'),
                (int) Post::query()->sum('comments_count'),
            ),
        ];

        return [
            'period' => $range,
            'totals' => $totals,
            'period_totals' => $current,
            'previous_period_totals' => $previous,
            'changes' => $previous === null ? [] : $this->buildChanges($current, $previous),
            'posts' => [
                'total' => Post::query()->count(),
                'published' => Post::query()->published()->count(),
                'draft' => Post::query()->where('is_published', false)->count(),
                'published_in_period' => Post::query()->published()->since($range['start'])->count(),
            ],
            'authors' => [
                'total' => Post::query()->whereNotNull('user_id')->distinct()->count('user_id'),
                'active_in_period' => PostEvent::query()->between($range['start'], $range['end'])->whereNotNull('user_id')->distinct()->count('user_id'),
            ],
            'comments_in_period' => Comment::query()->since($range['start'])->count(),
            'most_viewed_post' => Post::query()->with('user')->orderByDesc('views_count')->orderByDesc('favorites_count')->first(),
            'most_shared_post' => Post::query()->with('user')->orderByDesc('shares_count')->orderByDesc('views_count')->first(),
            'most_favorited_post' => Post::query()->with('user')->orderByDesc('favorites_count')->orderByDesc('views_count')->first(),
        ];
    }

    /**
     * Day-by-day event counts, zero-filled so charts have a continuous axis.
     *
     * @return Collection<int, array{date: string, label: string, views: int, shares: int, favorites: int, comments: int, engagement: float}>
     */
    public function dailyTrend(?string $period = self::DEFAULT_PERIOD): Collection
    {
        $range = $this->resolvePeriod($period);
        $days = $range['days'] ?? $this->spanInDays();

        if ($days < 1) {
            return collect();
        }

        // Cap the axis for "all time" so the chart stays readable.
        $days = min($days, 365);
        $start = $range['end']->copy()->subDays($days - 1)->startOfDay();

        $rows = PostEvent::query()
            ->whereIn('type', PostEvent::ENGAGEMENT_TYPES)
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, type, COUNT(*) as total')
            ->groupBy('day', 'type')
            ->get();

        $buckets = [];

        foreach ($rows as $row) {
            $buckets[$row->day][$row->type] = (int) $row->total;
        }

        $series = collect();

        foreach (CarbonPeriod::create($start, '1 day', $range['end']) as $day) {
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
                'engagement' => $this->engagementScore($views, $shares, $favorites, $comments),
            ]);
        }

        return $series;
    }

    /**
     * Highest engagement posts, optionally narrowed to a category or keyword.
     *
     * @param  string  $metric  views|shares|favorites|comments|engagement
     * @return Collection<int, Post>
     */
    public function topPosts(
        int $limit = 10,
        ?string $metric = 'engagement',
        ?string $category = null,
        ?string $keyword = null,
        ?string $period = null,
        bool $publishedOnly = true,
    ): Collection {
        $limit = max(1, min($limit, 50));
        $metric = in_array($metric, ['views', 'shares', 'favorites', 'comments', 'engagement'], true) ? $metric : 'engagement';

        if ($period !== null) {
            $range = $this->resolvePeriod($period);
            $periodCounts = $this->eventCountsPerPost($range['start'], $range['end']);
        } else {
            $periodCounts = null;
        }

        $query = Post::query()->with('user');

        if ($publishedOnly) {
            $query->published();
        }

        if ($category !== null && trim($category) !== '') {
            $query->where('category', 'like', $this->escapeLike($category));
        }

        if ($keyword !== null && trim($keyword) !== '') {
            $query->whereKey($this->keywordPostIds($keyword, $publishedOnly));
        }

        if ($periodCounts !== null) {
            $query->whereKey($periodCounts->keys()->all());
        }

        $posts = $query->orderByDesc('views_count')->orderByDesc('favorites_count')->limit(self::KEYWORD_SCAN_LIMIT)->get();

        if ($periodCounts !== null) {
            $posts = $posts->map(function (Post $post) use ($periodCounts): Post {
                $counts = $periodCounts[$post->id] ?? ['views' => 0, 'shares' => 0, 'favorites' => 0, 'comments' => 0];

                $post->setAttribute('period_views', $counts['views']);
                $post->setAttribute('period_shares', $counts['shares']);
                $post->setAttribute('period_favorites', $counts['favorites']);
                $post->setAttribute('period_comments', $counts['comments']);
                $post->setAttribute('period_engagement', $this->engagementScore($counts['views'], $counts['shares'], $counts['favorites'], $counts['comments']));

                return $post;
            });
        }

        $sortValue = fn (Post $post): float => match ($metric) {
            'views' => (float) ($periodCounts ? $post->getAttribute('period_views') : $post->views_count),
            'shares' => (float) ($periodCounts ? $post->getAttribute('period_shares') : $post->shares_count),
            'favorites' => (float) ($periodCounts ? $post->getAttribute('period_favorites') : $post->favorites_count),
            'comments' => (float) ($periodCounts ? $post->getAttribute('period_comments') : $post->comments_count),
            default => (float) ($periodCounts
                ? $post->getAttribute('period_engagement')
                : $this->engagementScore((int) $post->views_count, (int) $post->shares_count, (int) $post->favorites_count, (int) $post->comments_count)),
        };

        return $posts
            ->sortByDesc($sortValue)
            ->values()
            ->take($limit);
    }

    /**
     * Engagement aggregated per category ("chủ đề").
     *
     * @return Collection<int, array{category: string, posts: int, views: int, shares: int, favorites: int, comments: int, engagement: float, share_of_engagement: float}>
     */
    public function topCategories(int $limit = 10, bool $publishedOnly = true): Collection
    {
        $query = Post::query()
            ->selectRaw('category')
            ->selectRaw('COUNT(*) as posts_count')
            ->selectRaw('SUM(views_count) as views')
            ->selectRaw('SUM(shares_count) as shares')
            ->selectRaw('SUM(favorites_count) as favorites')
            ->selectRaw('SUM(comments_count) as comments');

        if ($publishedOnly) {
            $query->published();
        }

        $rows = $query->groupBy('category')->get()->map(function ($row): array {
            $views = (int) $row->views;
            $shares = (int) $row->shares;
            $favorites = (int) $row->favorites;
            $comments = (int) $row->comments;

            return [
                'category' => (string) ($row->category ?? 'khac'),
                'posts' => (int) $row->posts_count,
                'views' => $views,
                'shares' => $shares,
                'favorites' => $favorites,
                'comments' => $comments,
                'engagement' => $this->engagementScore($views, $shares, $favorites, $comments),
                'share_of_engagement' => 0.0,
            ];
        });

        $totalEngagement = $rows->sum('engagement');

        return $rows
            ->map(function (array $row) use ($totalEngagement): array {
                $row['share_of_engagement'] = $totalEngagement > 0
                    ? round(($row['engagement'] / $totalEngagement) * 100, 1)
                    : 0.0;

                return $row;
            })
            ->sortByDesc('engagement')
            ->values()
            ->take($limit);
    }

    /**
     * Keywords ranked by the engagement of the posts that mention them.
     *
     * @return Collection<int, array{keyword: string, posts: int, views: int, shares: int, favorites: int, comments: int, engagement: float, top_post_id: int|null, top_post_title: string|null}>
     */
    public function topKeywords(int $limit = 15, bool $publishedOnly = true): Collection
    {
        $query = Post::query()->select(['id', 'title', 'summary', 'category', 'content', 'views_count', 'shares_count', 'favorites_count', 'comments_count']);

        if ($publishedOnly) {
            $query->published();
        }

        $posts = $query->orderByDesc('views_count')->orderByDesc('favorites_count')->limit(self::KEYWORD_SCAN_LIMIT)->get();

        if ($posts->isEmpty()) {
            return collect();
        }

        $postKeywords = $this->keywords->keywordsForPosts($posts);

        $aggregates = [];

        foreach ($posts as $post) {
            $views = (int) $post->views_count;
            $shares = (int) $post->shares_count;
            $favorites = (int) $post->favorites_count;
            $comments = (int) $post->comments_count;
            $score = $this->engagementScore($views, $shares, $favorites, $comments);

            foreach ($postKeywords[$post->id] ?? [] as $keyword) {
                if (! isset($aggregates[$keyword])) {
                    $aggregates[$keyword] = [
                        'keyword' => $keyword,
                        'posts' => 0,
                        'views' => 0,
                        'shares' => 0,
                        'favorites' => 0,
                        'comments' => 0,
                        'engagement' => 0.0,
                        'top_post_id' => null,
                        'top_post_title' => null,
                        'top_post_engagement' => -1.0,
                    ];
                }

                $aggregate = &$aggregates[$keyword];
                $aggregate['posts']++;
                $aggregate['views'] += $views;
                $aggregate['shares'] += $shares;
                $aggregate['favorites'] += $favorites;
                $aggregate['comments'] += $comments;
                $aggregate['engagement'] += $score;

                if ($score > $aggregate['top_post_engagement']) {
                    $aggregate['top_post_engagement'] = $score;
                    $aggregate['top_post_id'] = $post->id;
                    $aggregate['top_post_title'] = $post->title;
                }

                unset($aggregate);
            }
        }

        return collect($aggregates)
            ->map(function (array $row): array {
                $row['engagement'] = round($row['engagement'], 1);
                unset($row['top_post_engagement']);

                return $row;
            })
            ->sortBy(
                fn (array $row): string => sprintf(
                    '%015.2f|%06d',
                    1_000_000_000 - $row['engagement'],
                    1_000_000 - $row['posts'],
                ),
            )
            ->values()
            ->take($limit);
    }

    /**
     * Which platforms drive the shares.
     *
     * @return Collection<int, array{platform: string, label: string, shares: int, percentage: float}>
     */
    public function sharePlatforms(?string $period = self::DEFAULT_PERIOD, int $limit = 10): Collection
    {
        $range = $this->resolvePeriod($period);

        $rows = PostEvent::query()
            ->ofType(PostEvent::TYPE_SHARE)
            ->between($range['start'], $range['end'])
            ->selectRaw($this->jsonSelect('meta', 'platform', 'other').' as platform, COUNT(*) as total')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $total = (int) $rows->sum('total');

        return $rows->map(fn ($row): array => [
            'platform' => (string) ($row->platform ?? 'other'),
            'label' => self::platformLabel((string) ($row->platform ?? 'other')),
            'shares' => (int) $row->total,
            'percentage' => $total > 0 ? round(((int) $row->total / $total) * 100, 1) : 0.0,
        ])->values();
    }

    /**
     * Where the readers come from (direct, internal, facebook, search, ...).
     *
     * @return Collection<int, array{source: string, label: string, views: int, percentage: float}>
     */
    public function viewSources(?string $period = self::DEFAULT_PERIOD, int $limit = 10): Collection
    {
        $range = $this->resolvePeriod($period);

        $rows = PostEvent::query()
            ->ofType(PostEvent::TYPE_VIEW)
            ->between($range['start'], $range['end'])
            ->selectRaw($this->jsonSelect('meta', 'source', 'direct').' as source, COUNT(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $total = (int) $rows->sum('total');

        $labels = [
            'direct' => 'Truy cập trực tiếp',
            'internal' => 'Từ trang nội bộ',
            'chatbot' => 'Từ chatbot AI',
            'search' => 'Từ công cụ tìm kiếm',
            'facebook' => 'Facebook',
            'x' => 'X (Twitter)',
            'linkedin' => 'LinkedIn',
            'telegram' => 'Telegram',
            'external' => 'Nguồn khác',
        ];

        return $rows->map(fn ($row): array => [
            'source' => (string) ($row->source ?? 'direct'),
            'label' => $labels[(string) ($row->source ?? 'direct')] ?? ucfirst((string) $row->source),
            'views' => (int) $row->total,
            'percentage' => $total > 0 ? round(((int) $row->total / $total) * 100, 1) : 0.0,
        ])->values();
    }

    /**
     * Engagement rolled up per author.
     *
     * @return Collection<int, array{author: string, user_id: int|null, posts: int, views: int, shares: int, favorites: int, comments: int, engagement: float}>
     */
    public function topAuthors(int $limit = 5): Collection
    {
        return Post::query()
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->selectRaw('posts.user_id')
            ->selectRaw('COALESCE(users.name, ?) as author', ['Đã xoá'])
            ->selectRaw('COUNT(*) as posts')
            ->selectRaw('SUM(posts.views_count) as views')
            ->selectRaw('SUM(posts.shares_count) as shares')
            ->selectRaw('SUM(posts.favorites_count) as favorites')
            ->selectRaw('SUM(posts.comments_count) as comments')
            ->groupBy('posts.user_id', 'author')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(function ($row): array {
                $views = (int) $row->views;
                $shares = (int) $row->shares;
                $favorites = (int) $row->favorites;
                $comments = (int) $row->comments;

                return [
                    'user_id' => $row->user_id === null ? null : (int) $row->user_id,
                    'author' => (string) $row->author,
                    'posts' => (int) $row->posts,
                    'views' => $views,
                    'shares' => $shares,
                    'favorites' => $favorites,
                    'comments' => $comments,
                    'engagement' => $this->engagementScore($views, $shares, $favorites, $comments),
                ];
            })
            ->sortByDesc('engagement')
            ->values();
    }

    /**
     * Everything the dashboard needs about one post.
     *
     * @return array<string, mixed>
     */
    public function postSummary(Post $post, ?string $period = self::DEFAULT_PERIOD): array
    {
        $range = $this->resolvePeriod($period);
        $counts = $this->eventCountsPerPost($range['start'], $range['end'], [$post->id])[$post->id]
            ?? ['views' => 0, 'shares' => 0, 'favorites' => 0, 'comments' => 0];

        $previous = $range['previous_start'] === null
            ? null
            : ($this->eventCountsPerPost($range['previous_start'], $range['previous_end'], [$post->id])[$post->id]
                ?? ['views' => 0, 'shares' => 0, 'favorites' => 0, 'comments' => 0]);

        $trend = PostEvent::query()
            ->whereIn('type', PostEvent::ENGAGEMENT_TYPES)
            ->where('post_id', $post->id)
            ->where('created_at', '>=', ($range['start'] ?? $range['end']->copy()->subDays(29))->startOfDay())
            ->selectRaw('date(created_at) as day, type, COUNT(*) as total')
            ->groupBy('day', 'type')
            ->get();

        $byDay = [];

        foreach ($trend as $row) {
            $byDay[$row->day][$row->type] = (int) $row->total;
        }

        $series = collect();
        $trendStart = ($range['start'] ?? $range['end']->copy()->subDays(29))->startOfDay();

        foreach (CarbonPeriod::create($trendStart, '1 day', $range['end']) as $day) {
            $key = $day->format('Y-m-d');
            $views = $byDay[$key][PostEvent::TYPE_VIEW] ?? 0;
            $shares = $byDay[$key][PostEvent::TYPE_SHARE] ?? 0;
            $favorites = $byDay[$key][PostEvent::TYPE_FAVORITE_ADD] ?? 0;
            $comments = $byDay[$key][PostEvent::TYPE_COMMENT] ?? 0;

            $series->push([
                'date' => $key,
                'label' => $day->format('d/m'),
                'views' => $views,
                'shares' => $shares,
                'favorites' => $favorites,
                'comments' => $comments,
                'engagement' => $this->engagementScore($views, $shares, $favorites, $comments),
            ]);
        }

        return [
            'post' => $post,
            'period' => $range,
            'totals' => [
                'views' => (int) $post->views_count,
                'shares' => (int) $post->shares_count,
                'favorites' => (int) $post->favorites_count,
                'comments' => (int) $post->comments_count,
                'saves' => PostPin::query()->where('post_id', $post->id)->count(),
                'engagement' => $this->engagementScore((int) $post->views_count, (int) $post->shares_count, (int) $post->favorites_count, (int) $post->comments_count),
            ],
            'period_totals' => $counts + [
                'saves' => PostPin::query()->where('post_id', $post->id)->between($range['start'], $range['end'])->count(),
                'engagement' => $this->engagementScore($counts['views'], $counts['shares'], $counts['favorites'], $counts['comments']),
            ],
            'previous_period_totals' => $previous === null ? null : $previous + [
                'saves' => PostPin::query()->where('post_id', $post->id)->between($range['previous_start'], $range['previous_end'] ?? null)->count(),
            ],
            'trend' => $series,
            'keywords' => $this->keywords->keywordsForPost($post),
            'platforms' => PostEvent::query()
                ->ofType(PostEvent::TYPE_SHARE)
                ->where('post_id', $post->id)
                ->between($range['start'], $range['end'])
                ->selectRaw($this->jsonSelect('meta', 'platform', 'other').' as platform, COUNT(*) as total')
                ->groupBy('platform')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row): array => [
                    'platform' => (string) ($row->platform ?? 'other'),
                    'label' => self::platformLabel((string) ($row->platform ?? 'other')),
                    'shares' => (int) $row->total,
                ])
                ->values(),
            'recent_events' => PostEvent::query()
                ->with('user')
                ->where('post_id', $post->id)
                ->latest()
                ->limit(20)
                ->get(),
        ];
    }

    /**
     * "Lượt lưu bài" (pins) — lifetime and per-period totals plus the most saved post.
     *
     * @return array<string, mixed>
     */
    public function savesOverview(?string $period = self::DEFAULT_PERIOD): array
    {
        $range = $this->resolvePeriod($period);

        $query = PostPin::query();
        $periodQuery = PostPin::query()->between($range['start'], $range['end']);

        return [
            'total' => (clone $query)->count(),
            'period' => (clone $periodQuery)->count(),
            'users' => (clone $query)->distinct()->count('user_id'),
            'most_saved_post' => Post::query()
                ->with('user')
                ->withCount('pinnedBy as saves_count')
                ->orderByDesc('saves_count')
                ->first(),
        ];
    }

    /**
     * "Lượt tìm kiếm" — how often readers searched the public listing.
     *
     * @return array<string, mixed>
     */
    public function searchOverview(?string $period = self::DEFAULT_PERIOD): array
    {
        $range = $this->resolvePeriod($period);

        return [
            'total' => SearchLog::query()->count(),
            'period' => SearchLog::query()->between($range['start'], $range['end'])->count(),
            'unique_queries' => SearchLog::query()->distinct()->count('query'),
            'without_results' => SearchLog::query()->where('results_count', 0)->count(),
        ];
    }

    /**
     * Most frequent search queries (case-insensitively grouped).
     *
     * @return Collection<int, array{query: string, searches: int, avg_results: float}>
     */
    public function topSearches(int $limit = 10): Collection
    {
        return SearchLog::query()
            ->selectRaw('LOWER(query) as query, COUNT(*) as searches, AVG(results_count) as avg_results')
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->map(fn ($row): array => [
                'query' => (string) $row->query,
                'searches' => (int) $row->searches,
                'avg_results' => round((float) ($row->avg_results ?? 0), 1),
            ])
            ->values();
    }

    /**
     * Latest searches (admin activity feed).
     *
     * @return Collection<int, SearchLog>
     */
    public function recentSearches(int $limit = 15): Collection
    {
        return SearchLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit(max(1, min($limit, 50)))
            ->get();
    }

    /**
     * Posts with the most pins (lượt lưu bài).
     *
     * @return Collection<int, Post>
     */
    public function topSavedPosts(int $limit = 5): Collection
    {
        return Post::query()
            ->with('user:id,name')
            ->withCount('pinnedBy as saves_count')
            ->orderByDesc('saves_count')
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 20)))
            ->get();
    }

    /**
     * Latest recorded interactions across the blog (admin activity feed).
     *
     * @return Collection<int, PostEvent>
     */
    public function recentEvents(int $limit = 20, ?string $type = null): Collection
    {
        return PostEvent::query()
            ->with(['post:id,title,slug,category', 'user:id,name'])
            ->when($type !== null && $type !== '', fn (Builder $query) => $query->ofType($type))
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Engagement score used to rank posts, topics and keywords.
     */
    public function engagementScore(int $views, int $shares, int $favorites, int $comments): float
    {
        return round(
            ($views * self::METRIC_WEIGHTS['views'])
            + ($shares * self::METRIC_WEIGHTS['shares'])
            + ($favorites * self::METRIC_WEIGHTS['favorites'])
            + ($comments * self::METRIC_WEIGHTS['comments']),
            1,
        );
    }

    public static function platformLabel(string $platform): string
    {
        return match (strtolower($platform)) {
            'facebook', 'fb' => 'Facebook',
            'x', 'twitter' => 'X (Twitter)',
            'linkedin' => 'LinkedIn',
            'telegram' => 'Telegram',
            'email' => 'Email',
            'copy' => 'Copy link',
            default => 'Khác',
        };
    }

    /**
     * Event totals per type for a window, plus a combined engagement score.
     *
     * @return array<string, int|float>
     */
    private function eventTotals(?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $rows = PostEvent::query()
            ->whereIn('type', PostEvent::ENGAGEMENT_TYPES)
            ->between($from, $to)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $views = (int) ($rows[PostEvent::TYPE_VIEW] ?? 0);
        $shares = (int) ($rows[PostEvent::TYPE_SHARE] ?? 0);
        $favorites = (int) ($rows[PostEvent::TYPE_FAVORITE_ADD] ?? 0);
        $comments = (int) ($rows[PostEvent::TYPE_COMMENT] ?? 0);

        return [
            'views' => $views,
            'shares' => $shares,
            'favorites' => $favorites,
            'comments' => $comments,
            'engagement' => $this->engagementScore($views, $shares, $favorites, $comments),
            'interactions' => $views + $shares + $favorites + $comments,
        ];
    }

    /**
     * Per-post event counts inside a window.
     *
     * @param  int[]|null  $postIds
     * @return Collection<int, array{views: int, shares: int, favorites: int, comments: int}>
     */
    private function eventCountsPerPost(?CarbonImmutable $from, ?CarbonImmutable $to, ?array $postIds = null): Collection
    {
        $rows = PostEvent::query()
            ->whereIn('type', PostEvent::ENGAGEMENT_TYPES)
            ->between($from, $to)
            ->when($postIds !== null, fn (Builder $query) => $query->whereIn('post_id', $postIds))
            ->selectRaw('post_id, type, COUNT(*) as total')
            ->groupBy('post_id', 'type')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $postId = (int) $row->post_id;
            $counts[$postId] ??= ['views' => 0, 'shares' => 0, 'favorites' => 0, 'comments' => 0];

            $bucket = match ((string) $row->type) {
                PostEvent::TYPE_VIEW => 'views',
                PostEvent::TYPE_SHARE => 'shares',
                PostEvent::TYPE_FAVORITE_ADD => 'favorites',
                PostEvent::TYPE_COMMENT => 'comments',
                default => null,
            };

            if ($bucket !== null) {
                $counts[$postId][$bucket] = (int) $row->total;
            }
        }

        return collect($counts);
    }

    /**
     * @return array<string, float>
     */
    private function buildChanges(array $current, array $previous): array
    {
        $changes = [];

        foreach (['views', 'shares', 'favorites', 'comments', 'engagement', 'interactions'] as $metric) {
            $now = (float) ($current[$metric] ?? 0);
            $before = (float) ($previous[$metric] ?? 0);

            $changes[$metric] = [
                'current' => $now,
                'previous' => $before,
                'absolute' => round($now - $before, 1),
                'percentage' => $before > 0 ? round((($now - $before) / $before) * 100, 1) : ($now > 0 ? 100.0 : 0.0),
            ];
        }

        return $changes;
    }

    /**
     * @return int[]
     */
    private function keywordPostIds(string $keyword, bool $publishedOnly): array
    {
        return $this->keywords->postsMatchingKeyword($keyword, $publishedOnly)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * How many days of history exist in the event log (used by "all time").
     */
    private function spanInDays(): int
    {
        $oldest = PostEvent::query()->min('created_at');

        if ($oldest === null) {
            return 30;
        }

        $days = CarbonImmutable::parse($oldest)->startOfDay()->diffInDays(CarbonImmutable::now()->startOfDay());

        return max(1, (int) $days + 1);
    }

    /**
     * Portable "read a key out of a JSON column, with a fallback" SQL fragment.
     */
    private function jsonSelect(string $column, string $key, string $fallback): string
    {
        $grammar = DB::connection()->getQueryGrammar();

        // Grammar::wrapJsonPath() is protected on recent frameworks, so check
        // that it is actually callable as a public API before using it.
        $hasPublicWrap = method_exists($grammar, 'wrapJsonPath')
            && (new \ReflectionMethod($grammar, 'wrapJsonPath'))->isPublic();

        $extract = $hasPublicWrap
            ? $grammar->wrapJsonPath($column, '->'.$key)
            : "json_extract({$column}, '$.{$key}')";

        return 'COALESCE('.$extract.', '.DB::connection()->getPdo()->quote($fallback).')';
    }

    private function escapeLike(string $value): string
    {
        return '%'.str_replace(['%', '_'], ['\%', '\_'], trim($value)).'%';
    }
}
