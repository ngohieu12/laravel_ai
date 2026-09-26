<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoPostsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Bài video mới',
            'summary' => 'Tóm tắt bài video',
            'content' => 'Mô tả bài video.',
            'content_type' => Post::CONTENT_TYPE_VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'category' => 'cong-nghe',
            'is_published' => '1',
        ], $overrides);
    }

    #[Test]
    public function test_a_creator_can_publish_a_video_post_without_written_content(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload(['content' => '']))
            ->assertRedirect(route('dashboard.posts.index'));

        $post = Post::query()->sole();
        $this->assertTrue($post->isVideo());
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $post->video_url);
        $this->assertSame('', $post->content);
    }

    #[Test]
    public function test_a_video_post_requires_a_video_url(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload(['video_url' => '']))
            ->assertSessionHasErrors('video_url');
    }

    #[Test]
    public function test_a_video_post_requires_an_embeddable_url(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload(['video_url' => 'https://example.com/clip']))
            ->assertSessionHasErrors('video_url');
    }

    #[Test]
    public function test_a_text_post_still_requires_written_content(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload([
                'content_type' => Post::CONTENT_TYPE_TEXT,
                'video_url' => '',
                'content' => '',
            ]))
            ->assertSessionHasErrors('content');
    }

    #[Test]
    public function test_switching_a_post_back_to_text_clears_the_video_url(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->video()->create();

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload([
                'content_type' => Post::CONTENT_TYPE_TEXT,
                'video_url' => '',
                'content' => 'Bài chữ thay thế',
            ]))
            ->assertRedirect(route('dashboard.posts.index'));

        $fresh = $post->fresh();
        $this->assertFalse($fresh->isVideo());
        $this->assertNull($fresh->video_url);
        $this->assertSame('Bài chữ thay thế', $fresh->content);
    }

    #[Test]
    public function test_video_posts_render_an_embedded_player(): void
    {
        $post = Post::factory()->video()->create(['title' => 'Bài có video']);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('🎥 Video', false);
    }

    #[Test]
    public function test_video_posts_support_vimeo_links(): void
    {
        $post = Post::factory()->video('https://vimeo.com/76979871')->create();

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('https://player.vimeo.com/video/76979871', false);
    }

    #[Test]
    public function test_text_posts_do_not_render_a_player(): void
    {
        $post = Post::factory()->create(['title' => 'Bài chữ thuần']);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('youtube.com/embed', false);
    }

    #[Test]
    public function test_video_posts_show_a_badge_on_the_public_listing(): void
    {
        Post::factory()->video()->create(['title' => 'Bài video trên danh sách']);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('Bài video trên danh sách')
            ->assertSee('🎥 Video', false);
    }
}
