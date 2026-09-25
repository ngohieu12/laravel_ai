<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PinTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_a_user_can_pin_a_post(): void
    {
        $user = User::factory()->reader()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->post(route('posts.pin', $post))->assertRedirect();

        $this->assertTrue($user->fresh()->hasPinned($post));
        $this->assertDatabaseHas('post_pins', ['user_id' => $user->id, 'post_id' => $post->id]);
    }

    #[Test]
    public function test_pinning_twice_removes_the_pin(): void
    {
        $user = User::factory()->reader()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->post(route('posts.pin', $post));
        $this->assertTrue($user->fresh()->hasPinned($post));

        $this->actingAs($user)->post(route('posts.pin', $post));
        $this->assertFalse($user->fresh()->hasPinned($post));
    }

    #[Test]
    public function test_the_pins_list_shows_pinned_posts(): void
    {
        $user = User::factory()->reader()->create();
        $pinned = Post::factory()->create(['title' => 'Bài đã ghim']);
        $other = Post::factory()->create(['title' => 'Bài chưa ghim']);
        $user->togglePin($pinned);

        $this->actingAs($user)->get(route('pins.index'))
            ->assertOk()
            ->assertSee('Bài đã ghim')
            ->assertDontSee('Bài chưa ghim');
    }

    #[Test]
    public function test_a_post_reports_its_save_count(): void
    {
        $pinner = User::factory()->reader()->create();
        $other = User::factory()->reader()->create();
        $post = Post::factory()->create();

        $pinner->togglePin($post);
        $other->togglePin($post);

        $this->assertSame(2, $post->fresh()->savesCount());
    }

    #[Test]
    public function test_guests_cannot_pin_posts(): void
    {
        $post = Post::factory()->create();

        $this->post(route('posts.pin', $post))->assertRedirect(route('login'));
        $this->get(route('pins.index'))->assertRedirect(route('login'));
    }
}
