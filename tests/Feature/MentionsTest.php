<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Support\Mentions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MentionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_username_is_generated_from_the_display_name(): void
    {
        $user = User::factory()->create(['name' => 'Nguyễn Minh Anh']);

        $this->assertSame('nguyen-minh-anh', $user->username);
    }

    #[Test]
    public function duplicate_names_get_a_unique_suffix(): void
    {
        $first = User::factory()->create(['name' => 'Minh Anh']);
        $second = User::factory()->create(['name' => 'Minh Anh']);

        $this->assertSame('minh-anh', $first->username);
        $this->assertSame('minh-anh-2', $second->username);
    }

    #[Test]
    public function it_finds_mentions_and_ignores_email_addresses(): void
    {
        $user = User::factory()->create(['name' => 'Minh Anh']);

        $this->assertSame(['minh-anh'], Mentions::usernames('Chào @'.$user->username.' nhé'));
        $this->assertSame([], Mentions::usernames('Gửi mail cho minh@example.com nhé'));
    }

    #[Test]
    public function a_comment_renders_a_mention_as_a_highlight(): void
    {
        $mentioned = User::factory()->create(['name' => 'Minh Anh']);
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create(['is_published' => true]);

        $this->actingAs(User::factory()->reader()->create())
            ->post(route('posts.comments.store', $post), ['content' => 'Cảm ơn @'.$mentioned->username.'!'])
            ->assertRedirect();

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('class="mention"', false)
            ->assertSee('@'.$mentioned->username, false);
    }

    #[Test]
    public function mentioning_someone_in_a_comment_notifies_them(): void
    {
        $mentioned = User::factory()->reader()->create(['name' => 'Minh Anh']);
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();
        $commenter = User::factory()->reader()->create();

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['content' => 'Hey @'.$mentioned->username])
            ->assertRedirect();

        $data = $mentioned->fresh()->unreadNotifications->first()->data;
        $this->assertSame('mention', $data['kind']);
        $this->assertSame($commenter->name, $data['actor_name']);
    }

    #[Test]
    public function the_post_author_is_not_notified_twice_when_mentioned_in_a_comment(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();

        $this->actingAs(User::factory()->reader()->create())
            ->post(route('posts.comments.store', $post), ['content' => 'Anh ơi @'.$author->username])
            ->assertRedirect();

        $this->assertSame(1, $author->fresh()->notifications()->count());
    }

    #[Test]
    public function a_published_post_notifies_its_mentions(): void
    {
        $mentioned = User::factory()->reader()->create(['name' => 'Bảo Trâm']);
        $author = User::factory()->creator()->create();

        $this->actingAs($author)->post(route('dashboard.posts.store'), [
            'title' => 'Bài có nhắc tên',
            'summary' => 'Tóm tắt',
            'category' => 'Laravel',
            'content' => 'Cảm ơn @'.$mentioned->username.' đã đọc bài.',
            'content_type' => Post::CONTENT_TYPE_TEXT,
            'is_published' => '1',
        ])->assertRedirect();

        $this->assertSame(1, $mentioned->fresh()->unreadNotifications()->count());
    }

    #[Test]
    public function a_draft_post_does_not_notify_mentions(): void
    {
        $mentioned = User::factory()->reader()->create(['name' => 'Bảo Trâm']);
        $author = User::factory()->creator()->create();

        $this->actingAs($author)->post(route('dashboard.posts.store'), [
            'title' => 'Bản nhắc',
            'summary' => 'Tóm tắt',
            'category' => 'Laravel',
            'content' => 'Draft cho @'.$mentioned->username,
            'content_type' => Post::CONTENT_TYPE_TEXT,
        ])->assertRedirect();

        $this->assertSame(0, $mentioned->fresh()->notifications()->count());
    }

    #[Test]
    public function a_post_body_renders_mentions_without_touching_markup(): void
    {
        $mentioned = User::factory()->reader()->create(['name' => 'Bảo Trâm']);
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create([
            'is_published' => true,
            'content' => '<p>Xin chào @'.$mentioned->username.'</p>',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('<p>Xin chào <span class="mention"', false);
    }

    #[Test]
    public function the_suggestion_endpoint_matches_username_and_display_name(): void
    {
        $user = User::factory()->reader()->create(['name' => 'Nguyễn Minh Anh']);
        $author = User::factory()->creator()->create();

        $this->actingAs($author)->getJson(route('mentions.index', ['q' => 'nguyen']))
            ->assertOk()
            ->assertJsonPath('data.0.username', $user->username);

        // Tìm theo tên có dấu cũng ra kết quả.
        $this->actingAs($author)->getJson(route('mentions.index', ['q' => 'minh']))
            ->assertOk()
            ->assertJsonPath('data.0.username', $user->username);
    }

    #[Test]
    public function guests_cannot_reach_the_suggestion_endpoint(): void
    {
        $this->getJson(route('mentions.index', ['q' => 'a']))->assertUnauthorized();
    }
}
