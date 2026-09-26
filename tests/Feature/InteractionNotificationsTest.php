<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InteractionNotificationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_commenting_on_a_post_notifies_the_author(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create(['title' => 'Bài của tác giả']);
        $commenter = User::factory()->reader()->create(['name' => 'Minh Anh']);

        $this->actingAs($commenter)
            ->post(route('posts.comments.store', $post), ['content' => 'Bài hay đấy!'])
            ->assertRedirect();

        $notifications = $author->fresh()->unreadNotifications;
        $this->assertSame(1, $notifications->count());

        $data = $notifications->first()->data;
        $this->assertSame('comment', $data['kind']);
        $this->assertSame($post->id, $data['post_id']);
        $this->assertSame($commenter->name, $data['actor_name']);
        $this->assertSame(0, $commenter->fresh()->notifications()->count());
    }

    #[Test]
    public function test_authors_are_not_notified_about_their_own_comments(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();

        $this->actingAs($author)
            ->post(route('posts.comments.store', $post), ['content' => 'Tự bình luận bài mình'])
            ->assertRedirect();

        $this->assertSame(0, $author->fresh()->notifications()->count());
    }

    #[Test]
    public function test_replying_to_a_comment_notifies_the_parent_commenter(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();

        $commenter = User::factory()->reader()->create();
        $replier = User::factory()->reader()->create(['name' => 'Người trả lời']);

        $comment = $post->comments()->create(['user_id' => $commenter->id, 'content' => 'Bình luận gốc']);

        $this->actingAs($replier)
            ->post(route('posts.comments.store', $post), ['content' => 'Phản hồi nhé', 'parent_id' => $comment->id])
            ->assertRedirect();

        $data = $commenter->fresh()->unreadNotifications->first()->data;
        $this->assertSame('reply', $data['kind']);
        $this->assertSame($comment->id, $data['comment_id']);

        // Trả lời bình luận thì tác giả bài không nhận thêm thông báo bình luận mới.
        $this->assertSame(0, $author->fresh()->notifications()->count());
    }

    #[Test]
    public function test_favoriting_a_post_notifies_the_author_once(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();
        $reader = User::factory()->reader()->create();

        $this->actingAs($reader)->post(route('posts.favorite', $post))->assertRedirect();
        $this->assertSame(1, $author->fresh()->unreadNotifications()->count());

        // Bỏ yêu thích không tạo thêm thông báo.
        $this->actingAs($reader)->post(route('posts.favorite', $post))->assertRedirect();
        $this->assertSame(1, $author->fresh()->unreadNotifications()->count());
    }

    #[Test]
    public function test_pinning_a_post_notifies_the_author(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();
        $reader = User::factory()->reader()->create();

        $this->actingAs($reader)->post(route('posts.pin', $post))->assertRedirect();

        $data = $author->fresh()->unreadNotifications->first()->data;
        $this->assertSame('pin', $data['kind']);
        $this->assertSame($post->id, $data['post_id']);
    }

    #[Test]
    public function test_liking_a_comment_notifies_the_comment_author_once(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();

        $commenter = User::factory()->reader()->create();
        $liker = User::factory()->reader()->create();

        $comment = $post->comments()->create(['user_id' => $commenter->id, 'content' => 'Bình luận được thích']);

        $this->actingAs($liker)
            ->post(route('posts.comments.favorite', [$post, $comment]))
            ->assertRedirect();

        $data = $commenter->fresh()->unreadNotifications->first()->data;
        $this->assertSame('comment_favorite', $data['kind']);
        $this->assertSame($comment->id, $data['comment_id']);

        // Bỏ thích rồi thích lại... chỉ thông báo lần đầu, bỏ thích không báo.
        $this->actingAs($liker)->post(route('posts.comments.favorite', [$post, $comment]));
        $this->assertSame(1, $commenter->fresh()->unreadNotifications()->count());
    }

    #[Test]
    public function test_users_are_not_notified_about_their_own_interactions(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();
        $comment = $post->comments()->create(['user_id' => $author->id, 'content' => 'Tự thích mình']);

        $this->actingAs($author)->post(route('posts.favorite', $post));
        $this->actingAs($author)->post(route('posts.pin', $post));
        $this->actingAs($author)->post(route('posts.comments.favorite', [$post, $comment]));

        $this->assertSame(0, $author->fresh()->notifications()->count());
    }

    #[Test]
    public function test_the_notifications_page_lists_interactions(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create(['title' => 'Bài của tôi']);
        $commenter = User::factory()->reader()->create(['name' => 'Minh Anh']);

        $this->actingAs($commenter)->post(route('posts.comments.store', $post), ['content' => 'Hay quá']);

        $this->actingAs($author)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Minh Anh đã bình luận về bài viết "Bài của tôi" của bạn.')
            ->assertSee('Đánh dấu tất cả đã đọc');
    }

    #[Test]
    public function test_a_notification_can_be_marked_read_and_leads_to_the_post(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();
        $reader = User::factory()->reader()->create();

        $this->actingAs($reader)->post(route('posts.favorite', $post));
        $notification = $author->fresh()->unreadNotifications->first();

        $this->actingAs($author)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('posts.show', ['post' => $post->id]));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function test_users_cannot_read_someone_elses_notification(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();
        $reader = User::factory()->reader()->create();
        $stranger = User::factory()->reader()->create();

        $this->actingAs($reader)->post(route('posts.pin', $post));
        $notification = $author->fresh()->unreadNotifications->first();

        $this->actingAs($stranger)
            ->post(route('notifications.read', $notification))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    #[Test]
    public function test_all_notifications_can_be_marked_read_at_once(): void
    {
        $author = User::factory()->creator()->create();
        $post = Post::factory()->for($author, 'user')->create();

        $this->actingAs(User::factory()->reader()->create())->post(route('posts.favorite', $post));
        $this->actingAs(User::factory()->reader()->create())->post(route('posts.pin', $post));
        $this->assertSame(2, $author->fresh()->unreadNotifications()->count());

        $this->actingAs($author)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $author->fresh()->unreadNotifications()->count());
    }

    #[Test]
    public function test_guests_are_redirected_to_login_from_the_notifications_page(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
    }
}
