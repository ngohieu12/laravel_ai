<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostEvent;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\PostEngagementTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $analytics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analytics = new AnalyticsService;
    }

    #[Test]
    public function test_the_engagement_score_weights_shares_and_favorites_above_views(): void
    {
        // 10 views + 2 shares×4 + 3 favorites×6 + 1 comment×3
        $this->assertSame(39.0, $this->analytics->engagementScore(10, 2, 3, 1));
        $this->assertSame(0.0, $this->analytics->engagementScore(0, 0, 0, 0));
    }

    #[Test]
    public function test_an_unknown_period_key_falls_back_to_thirty_days(): void
    {
        $range = $this->analytics->resolvePeriod('nonsense');

        $this->assertSame('30', $range['key']);
        $this->assertSame(30, $range['days']);
        $this->assertSame('30 ngày qua', $range['label']);
        $this->assertNotNull($range['previous_start']);
    }

    #[Test]
    public function test_the_all_time_period_has_no_boundaries_to_compare_with(): void
    {
        $range = $this->analytics->resolvePeriod('all');

        $this->assertNull($range['days']);
        $this->assertNull($range['start']);
        $this->assertNull($range['previous_start']);
    }

    #[Test]
    public function test_the_overview_separates_period_totals_from_lifetime_totals(): void
    {
        $post = Post::factory()->create(['views_count' => 40, 'favorites_count' => 4]);

        PostEvent::factory()->forPost($post)->at(now()->subDays(2))->create();
        PostEvent::factory()->forPost($post)->at(now()->subDays(20))->create();
        PostEvent::factory()->forPost($post)->at(now()->subDays(80))->create();

        $overview = $this->analytics->overview('30');

        $this->assertSame(2, $overview['period_totals']['views']);
        $this->assertSame(40, $overview['totals']['views']);
        $this->assertSame(4, $overview['totals']['favorites']);
        $this->assertSame(64.0, $overview['totals']['engagement']); // 40 + 4×6
        $this->assertSame(2.0, $overview['changes']['views']['absolute']); // 2 views in the window vs 0 in the previous one
        $this->assertSame(100.0, $overview['changes']['views']['percentage']);
    }

    #[Test]
    public function test_the_all_time_overview_has_no_change_indicators(): void
    {
        Post::factory()->create(['views_count' => 5]);

        $overview = $this->analytics->overview('all');

        $this->assertSame([], $overview['changes']);
        $this->assertNull($overview['previous_period_totals']);
        $this->assertSame(5, $overview['totals']['views']);
    }

    #[Test]
    public function test_the_daily_trend_is_zero_filled_and_covers_every_day(): void
    {
        $post = Post::factory()->create();

        PostEvent::factory()->forPost($post)->at(now()->subDays(2))->create();
        PostEvent::factory()->forPost($post)->at(now()->subDays(2))->create();
        PostEvent::factory()->forPost($post)->share('facebook')->at(now()->subDays(2))->create();

        $trend = $this->analytics->dailyTrend('7');

        $this->assertCount(7, $trend);
        $this->assertSame(now()->subDays(6)->format('Y-m-d'), $trend->first()['date']);
        $this->assertSame(now()->format('Y-m-d'), $trend->last()['date']);
        $this->assertSame(0, $trend->last()['views']);
        $busyDay = $trend->firstWhere('date', now()->subDays(2)->format('Y-m-d'));
        $this->assertSame(2, $busyDay['views']);
        $this->assertSame(1, $busyDay['shares']);
        $this->assertSame(6.0, $busyDay['engagement']); // 2 views + 1 share×4
    }

    #[Test]
    public function test_top_posts_are_ordered_by_the_selected_metric(): void
    {
        $shared = Post::factory()->create(['shares_count' => 6, 'views_count' => 1]);
        $viewed = Post::factory()->create(['shares_count' => 0, 'views_count' => 50]);

        $this->assertSame($shared->id, $this->analytics->topPosts(metric: 'shares')->first()->id);
        $this->assertSame($viewed->id, $this->analytics->topPosts(metric: 'views')->first()->id);
        $this->assertSame($viewed->id, $this->analytics->topPosts(metric: 'engagement')->first()->id);
    }

    #[Test]
    public function test_drafts_are_hidden_from_the_rankings_unless_requested(): void
    {
        $draft = Post::factory()->draft()->create(['views_count' => 100]);

        $this->assertFalse($this->analytics->topPosts()->contains('id', $draft->id));
        $this->assertTrue($this->analytics->topPosts(publishedOnly: false)->contains('id', $draft->id));
    }

    #[Test]
    public function test_top_posts_can_be_narrowed_to_a_keyword(): void
    {
        $laravelPost = Post::factory()->create(['title' => 'Học Laravel cơ bản', 'summary' => '', 'content' => '', 'views_count' => 3]);
        Post::factory()->create(['title' => 'Đọc sách mỗi ngày', 'summary' => '', 'content' => '', 'views_count' => 9]);

        $posts = $this->analytics->topPosts(keyword: 'laravel');

        $this->assertCount(1, $posts);
        $this->assertSame($laravelPost->id, $posts->first()->id);
    }

    #[Test]
    public function test_top_posts_report_their_numbers_inside_the_period(): void
    {
        $post = Post::factory()->create(['views_count' => 90, 'shares_count' => 9]);

        PostEvent::factory()->forPost($post)->at(now()->subDays(2))->create();
        PostEvent::factory()->forPost($post)->share('x')->at(now()->subDays(3))->create();
        PostEvent::factory()->forPost($post)->at(now()->subDays(120))->create();

        $posts = $this->analytics->topPosts(period: '30');

        $this->assertSame(1, $posts->first()->period_views);
        $this->assertSame(1, $posts->first()->period_shares);
        $this->assertSame(5.0, $posts->first()->period_engagement); // 1 view + 1 share×4
    }

    #[Test]
    public function test_categories_are_ranked_by_engagement_and_share_of_total(): void
    {
        Post::factory()->inCategory('cong-nghe')->create(['views_count' => 100, 'shares_count' => 10]);
        Post::factory()->inCategory('hoc-tap')->create(['views_count' => 10]);

        $categories = $this->analytics->topCategories();

        $this->assertSame('cong-nghe', $categories->first()['category']);
        $this->assertSame(140.0, $categories->first()['engagement']); // 100 + 10×4
        $this->assertSame(10.0, $categories->last()['engagement']);
        $this->assertSame(93.3, $categories->first()['share_of_engagement']);
        $this->assertSame(6.7, $categories->last()['share_of_engagement']);
    }

    #[Test]
    public function test_keywords_are_ranked_by_the_engagement_of_their_posts(): void
    {
        Post::factory()->create([
            'title' => 'Học lập trình Python cho người mới',
            'summary' => 'Python cơ bản',
            'content' => '<p>python</p>',
            'category' => 'hoc-tap',
            'views_count' => 50,
        ]);

        Post::factory()->create([
            'title' => 'Nuôi mèo trong căn hộ nhỏ',
            'summary' => 'Chăm sóc mèo',
            'content' => '<p>mèo</p>',
            'category' => 'cuoc-song',
            'views_count' => 4,
        ]);

        $keywords = $this->analytics->topKeywords(limit: 10);

        $this->assertSame('python', $keywords->first()['keyword']);
        $this->assertSame(50.0, $keywords->first()['engagement']);
        $this->assertSame(1, $keywords->first()['posts']);
        $this->assertTrue($keywords->pluck('keyword')->contains('meo'));
    }

    #[Test]
    public function test_share_platforms_are_counted_with_percentages(): void
    {
        $post = Post::factory()->create();

        PostEvent::factory()->forPost($post)->share('facebook')->create();
        PostEvent::factory()->forPost($post)->share('facebook')->create();
        PostEvent::factory()->forPost($post)->share('telegram')->create();

        $platforms = $this->analytics->sharePlatforms('30');

        $this->assertSame('Facebook', $platforms->first()['label']);
        $this->assertSame(2, $platforms->first()['shares']);
        $this->assertSame(66.7, $platforms->first()['percentage']);
        $this->assertSame('Telegram', $platforms->last()['label']);
        $this->assertSame(33.3, $platforms->last()['percentage']);
    }

    #[Test]
    public function test_view_sources_fall_back_to_direct_when_the_referer_is_missing(): void
    {
        $post = Post::factory()->create();

        PostEvent::factory()->forPost($post)->create(['meta' => ['source' => 'chatbot']]);
        PostEvent::factory()->forPost($post)->create(['meta' => []]);

        $sources = $this->analytics->viewSources('30');

        $this->assertSame('Từ chatbot AI', $sources->firstWhere('source', 'chatbot')['label']);
        $this->assertSame('Truy cập trực tiếp', $sources->firstWhere('source', 'direct')['label']);
        $this->assertSame(50.0, $sources->firstWhere('source', 'direct')['percentage']);
    }

    #[Test]
    public function test_authors_are_ranked_by_the_engagement_of_their_posts(): void
    {
        $busy = User::factory()->create(['name' => 'Tác Giả A']);
        $quiet = User::factory()->create(['name' => 'Tác Giả B']);

        Post::factory()->create(['user_id' => $busy->id, 'views_count' => 20, 'favorites_count' => 2]);
        Post::factory()->create(['user_id' => $quiet->id, 'views_count' => 1]);

        $authors = $this->analytics->topAuthors();

        $this->assertSame('Tác Giả A', $authors->first()['author']);
        $this->assertSame(32.0, $authors->first()['engagement']); // 20 + 2×6
        $this->assertSame(1.0, $authors->last()['engagement']);
    }

    #[Test]
    public function test_the_post_summary_collects_counters_trend_keywords_and_platforms(): void
    {
        $post = Post::factory()->create([
            'title' => 'Học Laravel nâng cao',
            'summary' => 'Kiến thức Laravel chuyên sâu',
            'content' => '<p>queue, horizon, laravel</p>',
            'views_count' => 12,
            'shares_count' => 2,
            'favorites_count' => 3,
            'comments_count' => 1,
        ]);

        PostEvent::factory()->forPost($post)->share('linkedin')->at(now()->subDay())->create();
        PostEvent::factory()->forPost($post)->at(now()->subDay())->create();

        $summary = $this->analytics->postSummary($post, '7');

        $this->assertSame(41.0, $summary['totals']['engagement']); // 12 + 2×4 + 3×6 + 1×3
        $this->assertSame('laravel', $summary['keywords'][0]);
        $this->assertSame('LinkedIn', $summary['platforms'][0]['label']);
        $this->assertSame(1, $summary['period_totals']['shares']);
        $this->assertSame(1, $summary['period_totals']['views']);
        $this->assertCount(7, $summary['trend']);
    }

    #[Test]
    public function test_recent_events_can_be_filtered_by_type(): void
    {
        $post = Post::factory()->create();

        PostEvent::factory()->forPost($post)->create();
        PostEvent::factory()->forPost($post)->share('facebook')->create();

        $this->assertCount(2, $this->analytics->recentEvents());
        $this->assertCount(1, $this->analytics->recentEvents(type: PostEvent::TYPE_SHARE));
    }

    #[Test]
    public function test_counters_are_recounted_from_the_relations_and_event_log(): void
    {
        $post = Post::factory()->create(['favorites_count' => 99, 'comments_count' => 99, 'views_count' => 99, 'shares_count' => 99]);
        $user = User::factory()->create();

        $user->toggleFavorite($post);
        Comment::create(['post_id' => $post->id, 'user_id' => $user->id, 'content' => 'Hay quá!']);
        PostEvent::factory()->forPost($post)->count(3)->create();
        PostEvent::factory()->forPost($post)->share('x')->count(2)->create();

        app(PostEngagementTracker::class)->syncCounters($post->fresh());

        $fresh = $post->fresh();

        $this->assertSame(1, $fresh->favorites_count);
        $this->assertSame(1, $fresh->comments_count);
        $this->assertSame(3, $fresh->views_count);
        $this->assertSame(2, $fresh->shares_count);
    }
}
