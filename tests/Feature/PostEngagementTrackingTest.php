<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostEvent;
use App\Models\User;
use App\Services\PostEngagementTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostEngagementTrackingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_opening_a_post_counts_one_view_and_records_the_event(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', $post))->assertOk();

        $this->assertSame(1, (int) $post->fresh()->views_count);
        $this->assertDatabaseHas('post_events', [
            'post_id' => $post->id,
            'type' => PostEvent::TYPE_VIEW,
        ]);
    }

    #[Test]
    public function test_repeated_views_from_the_same_visitor_inside_the_window_are_counted_once(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', $post));
        $this->get(route('posts.show', $post));
        $this->travel(PostEngagementTracker::VIEW_DEDUPLICATION_MINUTES - 1)->minutes();
        $this->get(route('posts.show', $post));

        $this->assertSame(1, (int) $post->fresh()->views_count);
        $this->assertSame(1, PostEvent::query()->ofType(PostEvent::TYPE_VIEW)->count());
    }

    #[Test]
    public function test_a_view_is_counted_again_after_the_deduplication_window(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', $post));
        $this->travel(PostEngagementTracker::VIEW_DEDUPLICATION_MINUTES + 1)->minutes();
        $this->get(route('posts.show', $post));

        $this->assertSame(2, (int) $post->fresh()->views_count);
        $this->assertSame(2, PostEvent::query()->ofType(PostEvent::TYPE_VIEW)->count());
    }

    #[Test]
    public function test_the_view_event_stores_where_the_reader_came_from(): void
    {
        $post = Post::factory()->create();

        $this->withHeader('referer', 'https://www.facebook.com/')
            ->get(route('posts.show', $post));

        $event = PostEvent::query()->ofType(PostEvent::TYPE_VIEW)->sole();

        $this->assertSame(['source' => 'facebook'], $event->meta);
    }

    #[Test]
    public function test_the_post_page_shows_view_share_and_comment_counters(): void
    {
        $post = Post::factory()->create([
            'views_count' => 120,
            'shares_count' => 7,
            'comments_count' => 0,
        ]);

        $this->get(route('posts.show', $post))
            ->assertSee('Số lượt xem')
            ->assertSee('Số lượt chia sẻ')
            ->assertSee('121');
    }

    #[Test]
    public function test_an_authenticated_user_can_record_a_share_click(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->reader()->create();

        $response = $this->actingAs($user)->postJson(route('posts.shares.track', $post), [
            'platform' => 'facebook',
        ]);

        $response->assertOk()->assertJson(['ok' => true, 'shares_count' => 1]);
        $this->assertSame(1, (int) $post->fresh()->shares_count);
        $this->assertDatabaseHas('post_events', [
            'post_id' => $post->id,
            'type' => PostEvent::TYPE_SHARE,
            'user_id' => $user->id,
        ]);
        $this->assertSame(['platform' => 'facebook'], PostEvent::query()->ofType(PostEvent::TYPE_SHARE)->sole()->meta);
    }

    #[Test]
    public function test_an_unknown_share_platform_is_recorded_as_other(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('posts.shares.track', $post), ['platform' => 'myspace'])
            ->assertOk();

        $this->assertSame('other', PostEvent::query()->ofType(PostEvent::TYPE_SHARE)->sole()->meta['platform']);
        $this->assertSame(1, (int) $post->fresh()->shares_count);
    }

    #[Test]
    public function test_sharing_without_a_platform_returns_422(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('posts.shares.track', $post), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('platform');

        $this->assertSame(0, PostEvent::query()->ofType(PostEvent::TYPE_SHARE)->count());
    }

    #[Test]
    public function test_guests_are_redirected_to_login_when_tracking_a_share(): void
    {
        $post = Post::factory()->create();

        $this->postJson(route('posts.shares.track', $post), ['platform' => 'facebook'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, (int) $post->fresh()->shares_count);
    }

    #[Test]
    public function test_favoriting_a_post_records_the_event_and_increments_the_counter(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->reader()->create();

        $this->actingAs($user)->post(route('posts.favorite', $post))->assertRedirect();

        $this->assertDatabaseCount('favorites', 1);
        $this->assertSame(1, (int) $post->fresh()->favorites_count);
        $this->assertDatabaseHas('post_events', [
            'post_id' => $post->id,
            'type' => PostEvent::TYPE_FAVORITE_ADD,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function test_removing_a_favorite_records_the_event_and_decrements_the_counter(): void
    {
        $post = Post::factory()->create(['favorites_count' => 1]);
        $user = User::factory()->reader()->create();

        $this->actingAs($user)->post(route('posts.favorite', $post));
        $this->actingAs($user)->post(route('posts.favorite', $post));

        $this->assertSame(0, (int) $post->fresh()->favorites_count);
        $this->assertDatabaseHas('post_events', ['post_id' => $post->id, 'type' => PostEvent::TYPE_FAVORITE_REMOVE]);
        $this->assertDatabaseCount('favorites', 0);
    }

    #[Test]
    public function test_the_favorites_counter_never_goes_below_zero(): void
    {
        $post = Post::factory()->create(['favorites_count' => 0]);
        $user = User::factory()->reader()->create();

        $this->actingAs($user);

        app(PostEngagementTracker::class)->trackFavorite(request(), $post, favorited: false, user: $user);

        $this->assertSame(0, (int) $post->fresh()->favorites_count);
    }

    #[Test]
    public function test_commenting_records_an_engagement_event_and_increments_the_counter(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->reader()->create();

        $this->actingAs($user)
            ->post(route('posts.comments.store', $post), ['content' => 'Bài viết rất hữu ích!'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('post_events', [
            'post_id' => $post->id,
            'type' => PostEvent::TYPE_COMMENT,
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, (int) $post->fresh()->comments_count);
        $this->assertSame(1, Comment::query()->count());
    }
}
