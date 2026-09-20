<?php

namespace Tests\Feature;

use App\Ai\Agents\BlogChatbotAgent;
use App\Ai\Tools\AnalyticsTool;
use App\Ai\Tools\GetEngagementAnalyticsTool;
use App\Ai\Tools\GetHotKeywordsTool;
use App\Ai\Tools\GetPostEngagementTool;
use App\Ai\Tools\GetTrendingTopicsTool;
use App\Models\Post;
use App\Models\PostEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The chatbot answers "what is getting the most interaction?" through tools.
 * These tests drive the tools directly (no AI provider needed).
 */
class AnalyticsChatbotToolsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_the_chatbot_agent_registers_the_analytics_tools(): void
    {
        $tools = collect((new BlogChatbotAgent)->tools())
            ->map(fn ($tool) => Str::of(Str::snake(class_basename($tool)))->replaceLast('_tool', '')->toString())
            ->all();

        $this->assertContains('get_engagement_analytics', $tools);
        $this->assertContains('get_trending_topics', $tools);
        $this->assertContains('get_hot_keywords', $tools);
        $this->assertContains('get_post_engagement', $tools);
    }

    #[Test]
    #[DataProvider('analyticsTools')]
    public function test_non_admin_users_are_refused_by_every_analytics_tool(string $toolClass): void
    {
        $this->actingAs(User::factory()->reader()->create());

        /** @var AnalyticsTool $tool */
        $tool = new $toolClass;
        $reply = (string) $tool->handle(new Request([]));

        $this->assertSame(AnalyticsTool::ACCESS_DENIED_MESSAGE, $reply);
    }

    #[Test]
    #[DataProvider('analyticsTools')]
    public function test_creators_are_refused_by_every_analytics_tool(string $toolClass): void
    {
        $this->actingAs(User::factory()->creator()->create());

        /** @var AnalyticsTool $tool */
        $tool = new $toolClass;

        $this->assertSame(AnalyticsTool::ACCESS_DENIED_MESSAGE, (string) $tool->handle(new Request([])));
    }

    public static function analyticsTools(): array
    {
        return [
            'engagement analytics' => [GetEngagementAnalyticsTool::class],
            'trending topics' => [GetTrendingTopicsTool::class],
            'hot keywords' => [GetHotKeywordsTool::class],
            'post engagement' => [GetPostEngagementTool::class],
        ];
    }

    #[Test]
    public function test_a_guest_is_refused_by_the_analytics_tools(): void
    {
        $this->assertSame(
            AnalyticsTool::ACCESS_DENIED_MESSAGE,
            (string) (new GetTrendingTopicsTool)->handle(new Request([])),
        );
    }

    #[Test]
    public function test_the_trending_topics_tool_reports_the_hottest_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Post::factory()->inCategory('cong-nghe')->create(['views_count' => 100, 'shares_count' => 10, 'favorites_count' => 5]);
        Post::factory()->inCategory('hoc-tap')->create(['views_count' => 3]);

        $reply = (string) (new GetTrendingTopicsTool)->handle(new Request(['limit' => 5]));

        $this->assertStringContainsString('chủ đề đang có lượt tương tác cao nhất', $reply);
        $this->assertStringContainsString('Cong-nghe', $reply);
        $this->assertStringContainsString('170', $reply); // 100 views + 10×4 shares + 5×6 favorites
        $this->assertStringContainsString('100 lượt xem', $reply);
        $this->assertStringContainsString('10 lượt chia sẻ', $reply);
        $this->assertStringContainsString('5 lượt yêu thích', $reply);
        $this->assertLessThan(mb_strpos($reply, 'Hoc-tap'), mb_strpos($reply, 'Cong-nghe'));
    }

    #[Test]
    public function test_the_hot_keywords_tool_reports_the_keyword_with_the_most_interaction(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Post::factory()->create([
            'title' => 'Học lập trình Python cho người mới',
            'summary' => 'Python cơ bản đến nâng cao',
            'content' => '<p>python python</p>',
            'category' => 'hoc-tap',
            'views_count' => 60,
            'shares_count' => 4,
        ]);

        Post::factory()->create([
            'title' => 'Chăm sóc cây cảnh trong nhà',
            'summary' => 'Cây cảnh dễ trồng',
            'content' => '<p>cây</p>',
            'category' => 'cuoc-song',
            'views_count' => 2,
        ]);

        $reply = (string) (new GetHotKeywordsTool)->handle(new Request(['limit' => 5]));

        $this->assertStringContainsString('từ khoá đang có lượt tương tác cao nhất', $reply);
        $this->assertStringContainsString('python', $reply);
        $this->assertStringContainsString('| 84', $reply); // 60 views + 4×4 shares
        $this->assertLessThan(mb_strpos($reply, 'canh'), mb_strpos($reply, 'python'));
    }

    #[Test]
    public function test_the_engagement_analytics_tool_reports_period_and_lifetime_numbers(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $post = Post::factory()->create([
            'title' => 'Bài viết nổi bật',
            'views_count' => 1234,
            'shares_count' => 12,
            'favorites_count' => 30,
            'comments_count' => 4,
        ]);

        PostEvent::factory()->forPost($post)->count(2)->at(now()->subDay())->create();
        PostEvent::factory()->forPost($post)->share('facebook')->at(now()->subDay())->create();
        PostEvent::factory()->forPost($post)->at(now()->subDays(40))->create();

        $reply = (string) (new GetEngagementAnalyticsTool)->handle(new Request(['period' => '30', 'metric' => 'views']));

        $this->assertStringContainsString('30 ngày qua', $reply);
        $this->assertStringContainsString('1.234', $reply);      // lifetime views
        $this->assertStringContainsString('2 lượt xem', $reply); // views inside the period
        $this->assertStringContainsString('Bài viết nổi bật', $reply);
        $this->assertStringContainsString('Facebook 1', $reply);
        $this->assertStringContainsString('So với kỳ trước', $reply);
    }

    #[Test]
    public function test_the_engagement_analytics_tool_accepts_a_vietnamese_period_phrase(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $post = Post::factory()->create(['views_count' => 1]);
        PostEvent::factory()->forPost($post)->at(now()->subDays(100))->create();

        $reply = (string) (new GetEngagementAnalyticsTool)->handle(new Request(['period' => 'toàn bộ thời gian']));

        $this->assertStringContainsString('Toàn bộ thời gian', $reply);
        $this->assertStringContainsString('1 lượt xem', $reply);
    }

    #[Test]
    public function test_the_post_engagement_tool_finds_a_post_by_id(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $post = Post::factory()->create([
            'title' => 'Học Laravel nâng cao',
            'summary' => 'Kiến thức Laravel chuyên sâu',
            'content' => '<p>queue và horizon</p>',
            'views_count' => 20,
            'shares_count' => 3,
            'favorites_count' => 2,
            'comments_count' => 1,
        ]);

        PostEvent::factory()->forPost($post)->share('telegram')->at(now()->subDays(2))->create();

        $reply = (string) (new GetPostEngagementTool)->handle(new Request(['post_id' => $post->id]));

        $this->assertStringContainsString('Học Laravel nâng cao', $reply);
        $this->assertStringContainsString('20 lượt xem', $reply);
        $this->assertStringContainsString('3 lượt chia sẻ', $reply);
        $this->assertStringContainsString('2 lượt yêu thích', $reply);
        $this->assertStringContainsString('41 điểm tương tác', $reply); // 20 + 3×4 + 2×6 + 1×3
        $this->assertStringContainsString('laravel', $reply);
        $this->assertStringContainsString('Telegram', $reply);
    }

    #[Test]
    public function test_the_post_engagement_tool_finds_a_post_by_partial_title(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Post::factory()->create(['title' => 'Xu hướng công nghệ 2026', 'views_count' => 5]);

        $reply = (string) (new GetPostEngagementTool)->handle(new Request(['title' => 'xu hướng công nghệ']));

        $this->assertStringContainsString('Xu hướng công nghệ 2026', $reply);
        $this->assertStringContainsString('5 lượt xem', $reply);
    }

    #[Test]
    public function test_the_post_engagement_tool_matches_titles_typed_without_diacritics(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Post::factory()->create(['title' => 'Học Laravel cơ bản', 'views_count' => 7]);

        $reply = (string) (new GetPostEngagementTool)->handle(new Request(['title' => 'hoc laravel']));

        $this->assertStringContainsString('Học Laravel cơ bản', $reply);
        $this->assertStringContainsString('7 lượt xem', $reply);
    }

    #[Test]
    public function test_the_post_engagement_tool_explains_when_nothing_matches(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Post::factory()->create(['title' => 'Học Laravel']);

        $reply = (string) (new GetPostEngagementTool)->handle(new Request(['title' => 'khong-ton-tai']));

        $this->assertStringContainsString('Không tìm thấy bài viết', $reply);
    }

    #[Test]
    public function test_the_tools_say_there_is_nothing_to_report_on_an_empty_blog(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->assertStringContainsString('Chưa có chủ đề nào', (string) (new GetTrendingTopicsTool)->handle(new Request([])));
        $this->assertStringContainsString('Chưa có từ khoá nào', (string) (new GetHotKeywordsTool)->handle(new Request([])));
    }
}
