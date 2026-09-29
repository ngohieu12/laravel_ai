<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AudioPostsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_creator_can_publish_an_audio_post(): void
    {
        Storage::fake('public');

        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->post(route('dashboard.posts.store'), [
            'title' => 'Tập 1 — Mở đầu',
            'summary' => 'Tóm tắt tập đầu tiên',
            'category' => 'Podcast',
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'content' => 'Phần mô tả ngắn cho tập đầu tiên.',
            'audio' => UploadedFile::fake()->create('tap-1.mp3', 300, 'audio/mpeg'),
            'audio_title' => 'Tập 1',
            'is_published' => '1',
        ])->assertRedirect(route('dashboard.posts.index'));

        $post = Post::query()->firstOrFail();

        $this->assertTrue($post->isAudio());
        $this->assertSame('Tập 1', $post->audio_title);
        Storage::disk('public')->assertExists($post->audio);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('🎧 Audio')
            ->assertSee('<audio controls', false);
    }

    #[Test]
    public function the_dashboard_forms_render_the_audio_fields(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->get(route('dashboard.posts.create'))
            ->assertOk()
            ->assertSee('🎧 Bài viết audio')
            ->assertSee('name="audio"', false);

        $post = Post::factory()->for($creator, 'user')->create([
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => 'posts/audio/tap-1.mp3',
            'audio_title' => 'Tập 1',
        ]);

        $this->actingAs($creator)->get(route('dashboard.posts.edit', $post))
            ->assertOk()
            ->assertSee('Xóa tệp âm thanh hiện tại');
    }

    #[Test]
    public function an_audio_post_requires_an_audio_file(): void
    {
        Storage::fake('public');

        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->post(route('dashboard.posts.store'), [
            'title' => 'Không có tệp',
            'summary' => 'Tóm tắt',
            'category' => 'Podcast',
            'content_type' => Post::CONTENT_TYPE_AUDIO,
        ])->assertSessionHasErrors('content_type');

        $this->assertSame(0, Post::query()->count());
    }

    #[Test]
    public function a_non_audio_file_is_rejected(): void
    {
        Storage::fake('public');

        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->post(route('dashboard.posts.store'), [
            'title' => 'Sai định dạng',
            'summary' => 'Tóm tắt',
            'category' => 'Podcast',
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => UploadedFile::fake()->create('tai-lieuhinh.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('audio');
    }

    #[Test]
    public function switching_a_post_to_another_type_drops_the_audio_file(): void
    {
        Storage::fake('public');

        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->create([
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => 'posts/audio/tap-1.mp3',
            'content' => 'Mô tả',
        ]);

        $this->actingAs($creator)->put(route('dashboard.posts.update', $post), [
            'title' => $post->title,
            'summary' => $post->summary,
            'category' => $post->category,
            'content_type' => Post::CONTENT_TYPE_TEXT,
            'content' => 'Nội dung chữ mới',
        ])->assertRedirect();

        $post->refresh();
        $this->assertFalse($post->isAudio());
        $this->assertNull($post->audio);
    }

    #[Test]
    public function deleting_an_audio_post_removes_the_file(): void
    {
        Storage::fake('public');

        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->post(route('dashboard.posts.store'), [
            'title' => 'Tập xóa',
            'summary' => 'Tóm tắt',
            'category' => 'Podcast',
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => UploadedFile::fake()->create('xoa.mp3', 200, 'audio/mpeg'),
        ])->assertRedirect();

        $post = Post::query()->firstOrFail();
        $path = $post->audio;

        $this->actingAs($creator)
            ->delete(route('dashboard.posts.destroy', $post))
            ->assertRedirect();

        Storage::disk('public')->assertMissing($path);
    }
}
