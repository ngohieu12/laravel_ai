<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicPostListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_guests_only_see_published_posts_on_the_public_list(): void
    {
        $published = Post::factory()->create(['title' => 'Bài viết công khai duy nhất', 'is_published' => true]);
        Post::factory()->draft()->create(['title' => 'Bản nháp bí mật']);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('Bài viết công khai duy nhất')
            ->assertDontSee('Bản nháp bí mật');
    }

    #[Test]
    public function test_the_public_list_has_no_edit_or_delete_paths_for_guests(): void
    {
        Post::factory()->create();

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertDontSee('/edit', false)
            ->assertDontSee('dashboard/posts', false)
            ->assertDontSee('DELETE', false);
    }

    #[Test]
    public function test_regular_users_do_not_see_edit_or_delete_paths_on_the_public_list(): void
    {
        Post::factory()->create();
        $reader = User::factory()->reader()->create();

        $this->actingAs($reader)->get(route('posts.index'))
            ->assertOk()
            ->assertDontSee('/edit', false)
            ->assertDontSee('dashboard/posts', false);
    }

    #[Test]
    public function test_the_public_list_supports_grid_and_list_view_modes(): void
    {
        Post::factory()->create();

        $this->get(route('posts.index', ['view' => 'grid']))
            ->assertOk()
            ->assertSee('view=grid', false)
            ->assertSee('view=list', false);

        $this->get(route('posts.index', ['view' => 'list']))
            ->assertOk();
    }

    #[Test]
    public function test_searching_records_a_search_log(): void
    {
        Post::factory()->create(['title' => 'Laravel hướng dẫn', 'summary' => 'Học Laravel']);

        $this->get(route('posts.index', ['search' => 'laravel']))->assertOk();

        $log = SearchLog::query()->sole();
        $this->assertSame('laravel', $log->query);
        $this->assertNull($log->user_id);
    }

    #[Test]
    public function test_browsing_without_a_search_term_does_not_record_a_search_log(): void
    {
        Post::factory()->create();

        $this->get(route('posts.index'))->assertOk();

        $this->assertSame(0, SearchLog::query()->count());
    }

    #[Test]
    public function test_guests_cannot_see_draft_posts_on_the_detail_page(): void
    {
        $draft = Post::factory()->draft()->create();

        $this->get(route('posts.show', $draft))->assertNotFound();
    }

    #[Test]
    public function test_the_detail_page_allows_guests_to_view_and_share_only(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee(route('posts.shares.track', $post), false)
            ->assertDontSee(route('posts.favorite', $post), false)
            ->assertDontSee('/edit', false);
    }
}
