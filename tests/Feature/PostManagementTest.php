<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Bài viết mới toanh',
            'summary' => 'Tóm tắt nội dung',
            'content' => 'Nội dung chi tiết bài viết.',
            'category' => 'cong-nghe',
            'is_published' => '1',
        ], $overrides);
    }

    #[Test]
    public function test_a_creator_can_create_their_own_post(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload())
            ->assertRedirect(route('dashboard.posts.index'));

        $post = Post::query()->sole();
        $this->assertTrue($post->user->is($creator));
        $this->assertSame('Bài viết mới toanh', $post->title);
    }

    #[Test]
    public function test_a_creator_can_update_their_own_post(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->create();

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload(['title' => 'Đã sửa tiêu đề']))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertSame('Đã sửa tiêu đề', $post->fresh()->title);
    }

    #[Test]
    public function test_a_creator_can_delete_their_own_post(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->create();

        $this->actingAs($creator)
            ->delete(route('dashboard.posts.destroy', $post))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    #[Test]
    public function test_a_creator_cannot_update_a_post_of_another_user(): void
    {
        $creator = User::factory()->creator()->create();
        $other = User::factory()->creator()->create();
        $post = Post::factory()->for($other, 'user')->create();

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload())
            ->assertForbidden();

        $this->assertNotSame('Bài viết mới toanh', $post->fresh()->title);
    }

    #[Test]
    public function test_a_creator_cannot_delete_a_post_of_another_user(): void
    {
        $creator = User::factory()->creator()->create();
        $other = User::factory()->creator()->create();
        $post = Post::factory()->for($other, 'user')->create();

        $this->actingAs($creator)
            ->delete(route('dashboard.posts.destroy', $post))
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    #[Test]
    public function test_an_admin_can_update_any_post(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('dashboard.posts.update', $post), $this->payload(['title' => 'Admin sửa được']))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertSame('Admin sửa được', $post->fresh()->title);
    }

    #[Test]
    public function test_an_admin_can_delete_any_post(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('dashboard.posts.destroy', $post))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    #[Test]
    public function test_a_regular_user_cannot_access_the_management_screens(): void
    {
        $reader = User::factory()->reader()->create();
        $post = Post::factory()->create();

        $this->actingAs($reader)->get(route('dashboard.posts.create'))->assertForbidden();
        $this->actingAs($reader)->post(route('dashboard.posts.store'), $this->payload())->assertForbidden();
        $this->actingAs($reader)->get(route('dashboard.posts.edit', $post))->assertForbidden();
        $this->actingAs($reader)->put(route('dashboard.posts.update', $post), $this->payload())->assertForbidden();
        $this->actingAs($reader)->delete(route('dashboard.posts.destroy', $post))->assertForbidden();
    }

    #[Test]
    public function test_guests_are_redirected_to_login_from_the_management_screens(): void
    {
        $post = Post::factory()->create();

        $this->get(route('dashboard.posts.index'))->assertRedirect(route('login'));
        $this->post(route('dashboard.posts.store'), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('dashboard.posts.destroy', $post))->assertRedirect(route('login'));
    }

    #[Test]
    public function test_the_management_list_shows_the_creators_own_posts_including_drafts(): void
    {
        $creator = User::factory()->creator()->create();
        $own = Post::factory()->for($creator, 'user')->draft()->create(['title' => 'Nháp của tôi']);
        Post::factory()->draft()->create(['title' => 'Nháp người khác']);

        $this->actingAs($creator)->get(route('dashboard.posts.index'))
            ->assertOk()
            ->assertSee('Nháp của tôi')
            ->assertDontSee('Nháp người khác');
    }

    #[Test]
    public function test_the_admin_management_list_shows_posts_of_all_users(): void
    {
        Post::factory()->create(['title' => 'Bài của người khác']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard.posts.index'))
            ->assertOk()
            ->assertSee('Bài của người khác');
    }

    #[Test]
    public function test_the_management_search_box_filters_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->create(['title' => 'Học Laravel cơ bản']);
        Post::factory()->create(['title' => 'Nấu ăn ngày Tết']);

        $this->actingAs($admin)->get(route('dashboard.posts.index'))
            ->assertOk()
            ->assertSee('name="search"', false);

        $this->actingAs($admin)->get(route('dashboard.posts.index', ['search' => 'Laravel']))
            ->assertOk()
            ->assertSee('Học Laravel cơ bản')
            ->assertDontSee('Nấu ăn ngày Tết');
    }
}
