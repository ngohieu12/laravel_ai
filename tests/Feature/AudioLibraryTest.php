<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AudioLibraryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_library_lists_only_audio_posts(): void
    {
        $creator = User::factory()->creator()->create();

        $audio = Post::factory()->for($creator, 'user')->create([
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => 'posts/audio/tap-1.mp3',
            'audio_title' => 'Tập 1',
            'title' => 'Podcast tập một',
        ]);

        Post::factory()->for($creator, 'user')->create(['title' => 'Bài viết thường']);
        Post::factory()->for($creator, 'user')->create([
            'content_type' => Post::CONTENT_TYPE_VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Bài video',
        ]);

        $this->actingAs($creator)->get(route('dashboard.audio.index'))
            ->assertOk()
            ->assertSee('🎧 Thư viện MP3')
            ->assertSee('Podcast tập một')
            ->assertSee('Tập 1')
            ->assertDontSee('Bài viết thường')
            ->assertDontSee('Bài video');
    }

    #[Test]
    public function a_creator_only_sees_their_own_audio_files(): void
    {
        $creator = User::factory()->creator()->create();
        $other = User::factory()->creator()->create();

        Post::factory()->for($other, 'user')->create([
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => 'posts/audio/cua-nguoi-khac.mp3',
            'title' => 'Audio của người khác',
        ]);

        $this->actingAs($creator)->get(route('dashboard.audio.index'))
            ->assertOk()
            ->assertDontSee('Audio của người khác');
    }

    #[Test]
    public function an_admin_sees_every_audio_file(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->creator()->create();

        Post::factory()->for($creator, 'user')->create([
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => 'posts/audio/tap-2.mp3',
            'title' => 'Audio của creator',
        ]);

        $this->actingAs($admin)->get(route('dashboard.audio.index'))
            ->assertOk()
            ->assertSee('Audio của creator');
    }

    #[Test]
    public function a_creator_can_delete_the_mp3_of_their_own_post(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('posts/audio/tap-1.mp3', 'audio-bytes');

        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->create([
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => 'posts/audio/tap-1.mp3',
            'audio_title' => 'Tập 1',
        ]);

        $this->actingAs($creator)->delete(route('dashboard.audio.destroy', $post))
            ->assertRedirect(route('dashboard.audio.index'));

        Storage::disk('public')->assertMissing('posts/audio/tap-1.mp3');

        $post->refresh();
        $this->assertNull($post->audio);
        $this->assertNull($post->audio_title);
    }

    #[Test]
    public function a_creator_cannot_delete_the_mp3_of_another_author(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('posts/audio/cua-nguoi-khac.mp3', 'audio-bytes');

        $creator = User::factory()->creator()->create();
        $other = User::factory()->creator()->create();

        $post = Post::factory()->for($other, 'user')->create([
            'content_type' => Post::CONTENT_TYPE_AUDIO,
            'audio' => 'posts/audio/cua-nguoi-khac.mp3',
        ]);

        $this->actingAs($creator)->delete(route('dashboard.audio.destroy', $post))
            ->assertForbidden();

        Storage::disk('public')->assertExists('posts/audio/cua-nguoi-khac.mp3');
    }

    #[Test]
    public function the_library_is_linked_from_the_dashboard_navigation(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->get(route('dashboard.posts.index'))
            ->assertOk()
            ->assertSee(route('dashboard.audio.index'), false);
    }
}
