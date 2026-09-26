<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeriesPostsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Series $series, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Phần đầu của chuỗi',
            'summary' => 'Tóm tắt',
            'content' => 'Nội dung',
            'category' => 'hoc-tap',
            'series_id' => (string) $series->id,
            'series_part' => '1',
            'is_published' => '1',
        ], $overrides);
    }

    #[Test]
    public function test_a_post_can_join_a_series(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create(['title' => 'Học Laravel từ đầu']);

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload($series))
            ->assertRedirect(route('dashboard.posts.index'));

        $post = Post::query()->sole();
        $this->assertTrue($post->isSeries());
        $this->assertSame($series->id, $post->series_id);
        $this->assertSame(1, $post->series_part);
    }

    #[Test]
    public function test_joining_a_series_requires_a_part_number(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload($series, ['series_part' => null]))
            ->assertSessionHasErrors('series_part');
    }

    #[Test]
    public function test_a_series_part_number_must_be_unique_within_the_series(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create(['title' => 'Học Laravel từ đầu']);
        Post::factory()->for($creator, 'user')->inSeries($series, 1)->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload($series, ['series_part' => '1']))
            ->assertSessionHasErrors('series_part');

        // Cùng số phần nhưng khác chuỗi thì vẫn hợp lệ.
        $other = Series::factory()->create(['title' => 'Chuỗi khác']);

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload($other, ['series_part' => '1']))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertSame(1, Post::query()->where('series_id', $other->id)->count());
    }

    #[Test]
    public function test_updating_a_post_without_a_series_clears_the_part(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create(['title' => 'Chuỗi cũ']);
        $post = Post::factory()->for($creator, 'user')->inSeries($series, 2)->create();

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload($series, [
                'series_id' => '',
                'series_part' => null,
            ]))
            ->assertRedirect(route('dashboard.posts.index'));

        $fresh = $post->fresh();
        $this->assertFalse($fresh->isSeries());
        $this->assertNull($fresh->series_id);
        $this->assertNull($fresh->series_part);
    }

    #[Test]
    public function test_the_show_page_lists_series_parts_with_previous_and_next_links(): void
    {
        $series = Series::factory()->create(['title' => 'Học Laravel từ đầu']);
        Post::factory()->inSeries($series, 1)->create(['title' => 'Cài đặt môi trường']);
        $part2 = Post::factory()->inSeries($series, 2)->create(['title' => 'Routing cơ bản']);
        Post::factory()->inSeries($series, 3)->create(['title' => 'Eloquent nâng cao']);

        $this->get(route('posts.show', $part2))
            ->assertOk()
            ->assertSee('Chuỗi bài viết dài kỳ: Học Laravel từ đầu', false)
            ->assertSee('Cài đặt môi trường')
            ->assertSee('Eloquent nâng cao')
            ->assertSee('← Phần 1', false)
            ->assertSee('Phần 3 →', false);
    }

    #[Test]
    public function test_draft_parts_are_hidden_from_readers_but_shown_to_the_author(): void
    {
        $author = User::factory()->creator()->create();
        $series = Series::factory()->create(['title' => 'Chuỗi A']);
        $published = Post::factory()->for($author, 'user')->inSeries($series, 1)->create(['title' => 'Phần công khai']);
        Post::factory()->for($author, 'user')->draft()->inSeries($series, 2)->create(['title' => 'Phần nháp']);

        $this->get(route('posts.show', $published))
            ->assertOk()
            ->assertDontSee('Phần nháp');

        $this->actingAs($author)->get(route('posts.show', $published))
            ->assertOk()
            ->assertSee('Phần nháp');
    }

    #[Test]
    public function test_series_posts_show_a_badge_on_the_public_listing(): void
    {
        $series = Series::factory()->create(['title' => 'Học Laravel từ đầu']);
        Post::factory()->inSeries($series, 1)->create(['title' => 'Bài dài kỳ trên danh sách']);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('Bài dài kỳ trên danh sách')
            ->assertSee('📖 Phần 1', false);
    }
}
