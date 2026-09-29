<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaCategoriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_dashboard_list_can_be_filtered_by_content_type(): void
    {
        $creator = User::factory()->creator()->create();

        Post::factory()->for($creator, 'user')->create(['title' => 'Bài chữ thường']);
        Post::factory()->for($creator, 'user')->video()->create(['title' => 'Bài video vlog']);
        Post::factory()->for($creator, 'user')->create([
            'title' => 'Bài audio podcast',
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => 'posts/audio/tap-1.mp3',
        ]);

        $this->actingAs($creator)->get(route('dashboard.posts.index', ['type' => 'video']))
            ->assertOk()
            ->assertSee('Bài video vlog')
            ->assertDontSee('Bài chữ thường')
            ->assertDontSee('Bài audio podcast');

        $this->actingAs($creator)->get(route('dashboard.posts.index', ['type' => 'audio']))
            ->assertOk()
            ->assertSee('Bài audio podcast')
            ->assertDontSee('Bài video vlog');

        $this->actingAs($creator)->get(route('dashboard.posts.index', ['type' => 'khong-ton-tai']))
            ->assertOk()
            ->assertSee('Bài chữ thường');
    }

    #[Test]
    public function the_public_list_can_be_filtered_by_content_type(): void
    {
        $creator = User::factory()->creator()->create();

        Post::factory()->for($creator, 'user')->create(['title' => 'Bài chữ công khai']);
        Post::factory()->for($creator, 'user')->video()->create(['title' => 'Bài video công khai']);

        $this->get(route('posts.index', ['type' => 'video']))
            ->assertOk()
            ->assertSee('Bài video công khai')
            ->assertDontSee('Bài chữ công khai');
    }

    #[Test]
    public function the_seeder_creates_the_video_and_mp3_categories(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseHas('categories', ['name' => Category::VIDEO]);
        $this->assertDatabaseHas('categories', ['name' => Category::MP3]);
    }

    #[Test]
    public function audio_and_video_posts_default_to_their_category(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->post(route('dashboard.posts.store'), [
            'title' => 'Tập podcast 1',
            'summary' => 'Tóm tắt',
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'content' => 'Mô tả ngắn',
            'category' => '',
            'audio' => UploadedFile::fake()->create('tap-1.mp3', 200, 'audio/mpeg'),
            'is_published' => '1',
        ])->assertRedirect(route('dashboard.posts.index'));

        $this->assertSame(Category::MP3, Post::query()->firstOrFail()->category);
    }

    #[Test]
    public function the_admin_screen_manages_the_video_and_mp3_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->creator()->create();

        Post::factory()->for($creator, 'user')->video()->create([
            'title' => 'Video thuộc danh mục Video',
            'category' => Category::VIDEO,
        ]);

        $this->actingAs($admin)->get(route('admin.media-categories.index'))
            ->assertOk()
            ->assertSee('Danh mục Video & MP3')
            ->assertSee('Video thuộc danh mục Video')
            ->assertSee('MP3');

        $this->actingAs($admin)->get(route('admin.media-categories.index', ['bucket' => Category::MP3]))
            ->assertOk()
            ->assertSee('Danh mục Video & MP3')
            ->assertDontSee('Video thuộc danh mục Video');
    }

    #[Test]
    public function the_default_categories_are_restored_when_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        Category::ensureDefaults();
        Category::query()->whereIn('name', Category::DEFAULTS)->delete();

        $this->actingAs($admin)->get(route('admin.media-categories.index'))->assertOk();

        $this->assertDatabaseHas('categories', ['name' => Category::VIDEO]);
        $this->assertDatabaseHas('categories', ['name' => Category::MP3]);
    }

    #[Test]
    public function a_creator_cannot_open_the_media_category_screen(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->get(route('admin.media-categories.index'))->assertForbidden();
    }
}
