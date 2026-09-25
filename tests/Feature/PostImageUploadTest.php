<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\PostImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostImageUploadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_creating_a_post_stores_the_uploaded_cover_image(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('dashboard.posts.store'), $this->postPayload([
                'image' => UploadedFile::fake()->image('anh-dai-dien.jpg'),
                'image_alt' => 'Ảnh minh hoạ Laravel',
            ]))
            ->assertRedirect();

        $post = Post::query()->sole();

        $this->assertNotNull($post->image);
        $this->assertStringStartsWith(PostImage::DIRECTORY.'/', $post->image);
        $this->assertSame('Ảnh minh hoạ Laravel', $post->image_alt);
        Storage::disk(PostImage::DISK)->assertExists($post->image);
    }

    #[Test]
    public function test_the_uploaded_image_is_shown_on_the_list_screen(): void
    {
        Storage::fake(PostImage::DISK);

        $post = Post::factory()->create([
            'image' => UploadedFile::fake()->image('cover.png')->store('posts', PostImage::DISK),
            'image_alt' => 'Ảnh bìa bài viết',
        ]);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee(Storage::disk(PostImage::DISK)->url($post->image), false)
            ->assertSee('Ảnh bìa bài viết');
    }

    #[Test]
    public function test_the_same_image_is_shown_on_the_detail_screen_as_the_cover(): void
    {
        Storage::fake(PostImage::DISK);

        $post = Post::factory()->create([
            'image' => UploadedFile::fake()->image('cover.png')->store('posts', PostImage::DISK),
            'image_alt' => 'Ảnh bìa chi tiết',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee(Storage::disk(PostImage::DISK)->url($post->image), false)
            ->assertSee('Ảnh bìa chi tiết');
    }

    #[Test]
    public function test_the_cover_image_is_exposed_as_an_open_graph_tag(): void
    {
        Storage::fake(PostImage::DISK);

        $post = Post::factory()->create([
            'image' => UploadedFile::fake()->image('cover.png')->store('posts', PostImage::DISK),
        ]);

        $this->get(route('posts.show', $post))
            ->assertSee('property="og:image"', false)
            ->assertSee(Storage::disk(PostImage::DISK)->url($post->image), false);
    }

    #[Test]
    public function test_a_post_without_an_image_renders_a_placeholder_on_the_list_screen(): void
    {
        Post::factory()->create(['title' => 'Bài viết không có ảnh', 'image' => null]);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('Bài viết chưa có ảnh đại diện');
    }

    #[Test]
    public function test_updating_a_post_replaces_the_old_image_and_deletes_it_from_disk(): void
    {
        Storage::fake(PostImage::DISK);

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('posts', PostImage::DISK);
        $post = Post::factory()->create(['image' => $oldPath, 'image_alt' => 'Ảnh cũ']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('dashboard.posts.update', $post), $this->postPayload([
                'image' => UploadedFile::fake()->image('new.png'),
                'image_alt' => 'Ảnh mới',
            ]))
            ->assertRedirect();

        $post->refresh();

        $this->assertNotSame($oldPath, $post->image);
        $this->assertSame('Ảnh mới', $post->image_alt);
        Storage::disk(PostImage::DISK)->assertExists($post->image);
        Storage::disk(PostImage::DISK)->assertMissing($oldPath);
    }

    #[Test]
    public function test_updating_a_post_without_touching_the_image_keeps_it(): void
    {
        Storage::fake(PostImage::DISK);

        $path = UploadedFile::fake()->image('keep.jpg')->store('posts', PostImage::DISK);
        $post = Post::factory()->create(['image' => $path, 'image_alt' => 'Giữ nguyên ảnh']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('dashboard.posts.update', $post), $this->postPayload())
            ->assertRedirect();

        $post->refresh();

        $this->assertSame($path, $post->image);
        $this->assertSame('Giữ nguyên ảnh', $post->image_alt);
        Storage::disk(PostImage::DISK)->assertExists($path);
    }

    #[Test]
    public function test_the_current_image_can_be_removed_with_the_remove_image_flag(): void
    {
        Storage::fake(PostImage::DISK);

        $path = UploadedFile::fake()->image('remove.jpg')->store('posts', PostImage::DISK);
        $post = Post::factory()->create(['image' => $path, 'image_alt' => 'Ảnh sẽ bị xoá']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('dashboard.posts.update', $post), $this->postPayload(['remove_image' => '1']))
            ->assertRedirect();

        $post->refresh();

        $this->assertNull($post->image);
        $this->assertNull($post->image_alt);
        Storage::disk(PostImage::DISK)->assertMissing($path);
    }

    #[Test]
    public function test_deleting_a_post_deletes_its_image(): void
    {
        Storage::fake(PostImage::DISK);

        $path = UploadedFile::fake()->image('doomed.jpg')->store('posts', PostImage::DISK);
        $post = Post::factory()->create(['image' => $path]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('dashboard.posts.destroy', $post))
            ->assertRedirect();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk(PostImage::DISK)->assertMissing($path);
    }

    #[Test]
    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('dashboard.posts.store'), $this->postPayload([
                'image' => UploadedFile::fake()->create('script.php', 20, 'application/x-php'),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function test_an_oversized_image_is_rejected(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('dashboard.posts.store'), $this->postPayload([
                'image' => UploadedFile::fake()->image('big.jpg')->size(5 * 1024),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function test_an_overlong_image_description_is_rejected(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('dashboard.posts.store'), $this->postPayload([
                'image_alt' => str_repeat('a', 256),
            ]))
            ->assertSessionHasErrors('image_alt');
    }

    #[Test]
    public function test_guests_cannot_upload_an_image(): void
    {
        Storage::fake(PostImage::DISK);

        $this->post(route('dashboard.posts.store'), $this->postPayload([
            'image' => UploadedFile::fake()->image('cover.jpg'),
        ]))->assertRedirect(route('login'));

        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function test_a_creator_can_upload_an_image_for_their_own_post(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->creator()->create())
            ->post(route('dashboard.posts.store'), $this->postPayload([
                'image' => UploadedFile::fake()->image('cover.webp', 400, 300),
            ]))
            ->assertRedirect();

        $post = Post::query()->sole();

        $this->assertNotNull($post->image);
        Storage::disk(PostImage::DISK)->assertExists($post->image);
    }

    #[Test]
    public function test_a_remote_image_url_is_served_as_is(): void
    {
        $post = Post::factory()->create(['image' => 'https://cdn.example.com/cover.jpg']);

        $this->assertSame('https://cdn.example.com/cover.jpg', $post->imageUrl());

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('https://cdn.example.com/cover.jpg', false);
    }

    #[Test]
    public function test_the_alt_text_of_a_cover_image_is_used_on_the_detail_screen(): void
    {
        $post = Post::factory()->withImage('posts/cover.jpg', 'Ảnh bìa về Laravel')->create();

        $this->assertSame('Ảnh bìa về Laravel', $post->imageAlt());

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('alt="Ảnh bìa về Laravel"', false)
            ->assertSee('content="Ảnh bìa về Laravel"', false);
    }

    #[Test]
    public function test_the_alt_text_falls_back_to_the_post_title(): void
    {
        $post = Post::factory()->withImage('posts/cover.jpg', null)->create([
            'title' => 'Tiêu đề dùng làm alt',
        ]);

        $this->assertSame('Tiêu đề dùng làm alt', $post->imageAlt());

        $blank = Post::factory()->withImage('posts/cover.jpg', '   ')->create();

        $this->assertSame((string) $blank->title, $blank->imageAlt());
    }

    /**
     * Valid payload for the post form, merged with per-test overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Bài viết có ảnh đại diện',
            'summary' => 'Tóm tắt ngắn gọn cho bài viết có ảnh.',
            'content' => '<p>Nội dung bài viết có ảnh đại diện.</p>',
            'category' => 'cong-nghe',
            'is_published' => '1',
        ], $overrides);
    }
}
