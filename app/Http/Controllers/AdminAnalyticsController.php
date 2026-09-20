<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only analytics dashboard: views, shares, favorites (yêu thích),
 * comments — plus the topics and keywords currently getting the most
 * interaction.
 */
class AdminAnalyticsController extends Controller
{
    /**
     * Metrics the "top posts" table can be sorted by.
     */
    private const SORTABLE_METRICS = ['engagement', 'views', 'shares', 'favorites', 'comments'];

    public function __construct(private readonly AnalyticsService $analytics) {}

    /**
     * Show the engagement analytics dashboard.
     */
    public function index(Request $request): View
    {
        $period = $this->normalizePeriod($request->input('period'));
        $metric = $this->normalizeMetric($request->input('metric'));
        $category = is_string($request->input('category')) ? trim($request->input('category')) : '';

        $overview = $this->analytics->overview($period);
        $trend = $this->analytics->dailyTrend($period);
        $topPosts = $this->analytics->topPosts(limit: 10, metric: $metric, category: $category !== '' ? $category : null);
        $topCategories = $this->analytics->topCategories(limit: 8);
        $topKeywords = $this->analytics->topKeywords(limit: 20);
        $platforms = $this->analytics->sharePlatforms($period);
        $sources = $this->analytics->viewSources($period);
        $authors = $this->analytics->topAuthors(limit: 5);
        $recentEvents = $this->analytics->recentEvents(limit: 15);
        $categories = Post::query()->distinct()->orderBy('category')->pluck('category');

        return view('admin.analytics.index', [
            'overview' => $overview,
            'trend' => $trend,
            'topPosts' => $topPosts,
            'topCategories' => $topCategories,
            'topKeywords' => $topKeywords,
            'platforms' => $platforms,
            'sources' => $sources,
            'authors' => $authors,
            'recentEvents' => $recentEvents,
            'categories' => $categories,
            'period' => $period,
            'metric' => $metric,
            'category' => $category,
            'metrics' => self::SORTABLE_METRICS,
            'chatbotQuestions' => $this->chatbotQuestions(),
        ]);
    }

    /**
     * Drill down into a single post's engagement.
     */
    public function show(Request $request, Post $post): View
    {
        $period = $this->normalizePeriod($request->input('period'));

        $summary = $this->analytics->postSummary($post, $period);
        $topKeywords = $this->analytics->topKeywords(limit: 10);

        return view('admin.analytics.show', [
            'summary' => $summary,
            'post' => $post,
            'period' => $period,
            'topKeywords' => $topKeywords,
            'chatbotQuestions' => [
                'Phân tích tương tác của bài viết "'.$post->title.'"?',
                'Từ khoá nào của bài "'.$post->title.'" đang hút người xem nhất?',
            ],
        ]);
    }

    /**
     * Suggested questions admins can hand to the chatbot.
     *
     * @return string[]
     */
    private function chatbotQuestions(): array
    {
        return [
            'Chủ đề nào đang có lượt tương tác cao nhất?',
            'Từ khoá nào đang thu hút nhiều lượt xem nhất?',
            'Top 5 bài viết có nhiều lượt chia sẻ nhất?',
            'Phân tích số lượt xem, lượt chia sẻ và lượt yêu thích 30 ngày qua?',
            'Kênh nào mang về nhiều lượt chia sẻ nhất?',
        ];
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
