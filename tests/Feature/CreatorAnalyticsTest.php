<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatorAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_a_creator_sees_analytics_for_their_own_posts(): void
    {
        $creator = User::factory()->creator()->create();
        Post::factory()->for($creator, 'user')->create(['title' => 'Bài của tôi']);

        $this->actingAs($creator)->get(route('dashboard.analytics.index'))
            ->assertOk()
            ->assertSee('Bài của tôi');
    }

    #[Test]
    public function test_a_creator_can_see_analytics_for_a_single_own_post(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->create(['title' => 'Bài cần phân tích']);

        $this->actingAs($creator)->get(route('dashboard.analytics.posts.show', $post))
            ->assertOk()
            ->assertSee('Bài cần phân tích');
    }

    #[Test]
    public function test_a_creator_cannot_see_analytics_for_posts_of_others(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->create();

        $this->actingAs($creator)->get(route('dashboard.analytics.posts.show', $post))->assertForbidden();
    }

    #[Test]
    public function test_regular_users_cannot_access_analytics(): void
    {
        $reader = User::factory()->reader()->create();
        $post = Post::factory()->create();

        $this->actingAs($reader)->get(route('dashboard.analytics.index'))->assertForbidden();
        $this->actingAs($reader)->get(route('dashboard.analytics.posts.show', $post))->assertForbidden();
    }

    #[Test]
    public function test_the_admin_overview_shows_favorites_saves_and_search_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $fan = User::factory()->reader()->create();
        $fan->favorites()->attach($post, ['created_at' => now()]);
        $fan->togglePin($post);
        SearchLog::record('laravel', null, 3);

        $this->actingAs($admin)->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Lượt yêu thích')
            ->assertSee('Lượt lưu bài')
            ->assertSee('Lượt tìm kiếm');
    }

    #[Test]
    public function test_the_admin_can_see_per_post_analytics_for_any_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['title' => 'Bài bất kỳ']);

        $this->actingAs($admin)->get(route('admin.analytics.posts.show', $post))
            ->assertOk()
            ->assertSee('Bài bất kỳ');
    }

    #[Test]
    public function test_regular_users_cannot_access_admin_analytics(): void
    {
        $reader = User::factory()->reader()->create();
        $post = Post::factory()->create();

        $this->actingAs($reader)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($reader)->get(route('admin.analytics.posts.show', $post))->assertForbidden();
    }
}
