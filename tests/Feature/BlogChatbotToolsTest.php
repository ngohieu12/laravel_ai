<?php

namespace Tests\Feature;

use App\Ai\Tools\GetStatsTool;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The chatbot can report the blog overview, including the engagement counters
 * (views, shares, favorites) that the admin analytics dashboard is built on.
 */
class BlogChatbotToolsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_the_stats_tool_reports_the_engagement_totals_of_published_posts(): void
    {
        $author = User::factory()->creator()->create();

        Post::factory()->for($author)->create([
            'category' => 'cong-nghe',
            'views_count' => 1200,
            'shares_count' => 30,
            'favorites_count' => 12,
        ]);

        Post::factory()->for($author)->inCategory('hoc-tap')->create([
            'views_count' => 800,
            'shares_count' => 20,
            'favorites_count' => 8,
        ]);

        $reply = (string) (new GetStatsTool)->handle(new Request([]));

        $this->assertStringContainsString('- Tổng bài viết: **2**', $reply);
        $this->assertStringContainsString('- Số danh mục: **2**', $reply);
        $this->assertStringContainsString('- Tổng lượt xem: **2.000**', $reply);
        $this->assertStringContainsString('- Tổng lượt chia sẻ: **50**', $reply);
        $this->assertStringContainsString('- Tổng lượt yêu thích: **20**', $reply);
    }

    #[Test]
    public function test_the_stats_tool_ignores_drafts_when_summing_engagement(): void
    {
        $author = User::factory()->creator()->create();

        Post::factory()->for($author)->create([
            'views_count' => 100,
            'shares_count' => 5,
            'favorites_count' => 3,
        ]);

        Post::factory()->for($author)->draft()->create([
            'views_count' => 999,
            'shares_count' => 99,
            'favorites_count' => 99,
        ]);

        $reply = (string) (new GetStatsTool)->handle(new Request([]));

        $this->assertStringContainsString('- Tổng bài viết: **1**', $reply);
        $this->assertStringContainsString('- Tổng lượt xem: **100**', $reply);
        $this->assertStringContainsString('- Tổng lượt chia sẻ: **5**', $reply);
        $this->assertStringContainsString('- Tổng lượt yêu thích: **3**', $reply);
    }

    #[Test]
    public function test_the_stats_tool_mentions_the_newest_post(): void
    {
        $author = User::factory()->creator()->create();

        Post::factory()->for($author)->create([
            'title' => 'Bài viết cũ',
            'created_at' => now()->subDays(30),
        ]);

        Post::factory()->for($author)->create([
            'title' => 'Bài viết mới nhất',
            'created_at' => now()->subDay(),
        ]);

        $reply = (string) (new GetStatsTool)->handle(new Request([]));

        $this->assertStringContainsString('Bài viết mới nhất: **Bài viết mới nhất**', $reply);
        $this->assertStringNotContainsString('Bài viết mới nhất: **Bài viết cũ**', $reply);
    }

    #[Test]
    public function test_the_stats_tool_works_without_any_post(): void
    {
        $reply = (string) (new GetStatsTool)->handle(new Request([]));

        $this->assertStringContainsString('- Tổng bài viết: **0**', $reply);
        $this->assertStringContainsString('- Tổng lượt xem: **0**', $reply);
        $this->assertStringNotContainsString('Bài viết mới nhất', $reply);
    }

    #[Test]
    public function test_the_stats_tool_is_available_to_every_signed_in_user(): void
    {
        Post::factory()->create(['views_count' => 10]);

        $reader = User::factory()->reader()->create();

        $this->actingAs($reader);

        $reply = (string) (new GetStatsTool)->handle(new Request([]));

        $this->assertStringContainsString('- Tổng lượt xem: **10**', $reply);
        $this->assertStringNotContainsString('chỉ dành cho admin', mb_strtolower($reply));
    }
}
