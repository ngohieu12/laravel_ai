<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.analytics.index'))->assertRedirect(route('login'));
    }

    #[Test]
    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_are_forbidden(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('admin.analytics.index'))
            ->assertForbidden();
    }

    public static function nonAdminRoles(): array
    {
        return [
            'creator' => [User::ROLE_CREATOR],
            'reader' => [User::ROLE_USER],
        ];
    }

    #[Test]
    public function test_admin_sees_the_engagement_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create([
            'views_count' => 10,
            'shares_count' => 2,
            'favorites_count' => 3,
            'comments_count' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertOk()
            ->assertSee('Phân tích tương tác')
            ->assertSee('Từ khoá đang có tương tác cao')
            ->assertSee('Chủ đề đang có tương tác cao');

        $overview = $response->viewData('overview');

        $this->assertSame(['views' => 10, 'shares' => 2, 'favorites' => 3, 'comments' => 1], array_intersect_key($overview['totals'], array_flip(['views', 'shares', 'favorites', 'comments'])));
        // 10 views + 2×4 shares + 3×6 favorites + 1×3 comments
        $this->assertSame(39.0, $overview['totals']['engagement']);
        $this->assertSame($post->id, $overview['most_viewed_post']->id);
    }

    #[Test]
    public function test_the_period_filter_changes_the_counted_events(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        PostEvent::factory()->forPost($post)->at(now()->subDays(3))->create();
        PostEvent::factory()->forPost($post)->at(now()->subDays(20))->create();

        $last7 = $this->actingAs($admin)->get(route('admin.analytics.index', ['period' => '7']));
        $last30 = $this->actingAs($admin)->get(route('admin.analytics.index', ['period' => '30']));

        $this->assertSame(1, $last7->viewData('overview')['period_totals']['views']);
        $this->assertSame(2, $last30->viewData('overview')['period_totals']['views']);
        $this->assertSame('7', $last7->viewData('period'));
    }

    #[Test]
    public function test_an_unknown_period_falls_back_to_the_default(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.analytics.index', ['period' => '999']));

        $response->assertOk();
        $this->assertSame('30', $response->viewData('period'));
    }

    #[Test]
    public function test_the_top_posts_table_can_be_sorted_by_shares(): void
    {
        $admin = User::factory()->admin()->create();
        $mostShared = Post::factory()->create(['title' => 'Bài chia sẻ nhiều', 'shares_count' => 9, 'views_count' => 1]);
        $mostViewed = Post::factory()->create(['title' => 'Bài xem nhiều', 'shares_count' => 0, 'views_count' => 50]);

        $byShares = $this->actingAs($admin)->get(route('admin.analytics.index', ['metric' => 'shares']));
        $byViews = $this->actingAs($admin)->get(route('admin.analytics.index', ['metric' => 'views']));

        $this->assertSame($mostShared->id, $byShares->viewData('topPosts')->first()->id);
        $this->assertSame($mostViewed->id, $byViews->viewData('topPosts')->first()->id);
    }

    #[Test]
    public function test_the_top_posts_table_can_be_filtered_by_category(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->inCategory('cong-nghe')->create(['title' => 'Bài công nghệ', 'views_count' => 5]);
        $life = Post::factory()->inCategory('cuoc-song')->create(['title' => 'Bài cuộc sống', 'views_count' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index', ['category' => 'cuoc-song']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('topPosts'));
        $this->assertSame($life->id, $response->viewData('topPosts')->first()->id);
    }

    #[Test]
    public function test_topics_and_keywords_are_ranked_for_the_admin(): void
    {
        $admin = User::factory()->admin()->create();

        Post::factory()->create([
            'title' => 'Giới thiệu Laravel và hệ sinh thái Laravel',
            'summary' => 'Laravel là framework PHP.',
            'content' => '<p>laravel laravel laravel</p>',
            'category' => 'cong-nghe',
            'views_count' => 100,
            'shares_count' => 5,
            'favorites_count' => 4,
            'comments_count' => 2,
        ]);

        Post::factory()->create([
            'title' => 'Đọc sách mỗi ngày',
            'summary' => 'Thói quen đọc sách.',
            'content' => '<p>sách</p>',
            'category' => 'hoc-tap',
            'views_count' => 10,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertOk();
        $this->assertSame('cong-nghe', $response->viewData('topCategories')->first()['category']);
        $this->assertSame('laravel', $response->viewData('topKeywords')->first()['keyword']);
        $this->assertGreaterThan(
            $response->viewData('topKeywords')->last()['engagement'],
            $response->viewData('topKeywords')->first()['engagement'],
        );
    }

    #[Test]
    public function test_non_admin_roles_are_forbidden_on_the_post_analytics_page(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->creator()->create())
            ->get(route('admin.analytics.posts.show', $post))
            ->assertForbidden();
    }

    #[Test]
    public function test_admin_sees_the_single_post_analytics_page(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create([
            'title' => 'Học Laravel nâng cao',
            'summary' => 'Tổng hợp kiến thức Laravel.',
            'views_count' => 8,
            'shares_count' => 1,
            'favorites_count' => 2,
            'comments_count' => 0,
        ]);

        PostEvent::factory()->forPost($post)->share('facebook')->at(now()->subDay())->create();
        PostEvent::factory()->forPost($post)->at(now()->subDay())->create();

        $response = $this->actingAs($admin)->get(route('admin.analytics.posts.show', $post));

        $response->assertOk()->assertSee('Học Laravel nâng cao');

        $summary = $response->viewData('summary');

        $this->assertSame(8, $summary['totals']['views']);
        // 8 + 1×4 + 2×6 + 0
        $this->assertSame(24.0, $summary['totals']['engagement']);
        $this->assertContains('laravel', $summary['keywords']);
        $this->assertSame('Facebook', $summary['platforms'][0]['label']);
        $this->assertSame(1, $summary['period_totals']['shares']);
    }

    #[Test]
    public function test_the_share_button_on_a_post_page_reports_to_the_tracking_endpoint(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->reader()->create())
            ->get(route('posts.show', $post))
            ->assertSee('data-share-platform="facebook"', false)
            ->assertSee(route('posts.shares.track', $post));
    }
}
