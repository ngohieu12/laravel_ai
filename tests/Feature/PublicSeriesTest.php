<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicSeriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload accepted by the dashboard post form.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Phần đầu của chuỗi',
            'summary' => 'Tóm tắt',
            'content' => 'Nội dung',
            'category' => 'hoc-tap',
            'series_title' => 'Học Laravel từ đầu',
            'series_part' => '1',
            'is_published' => '1',
        ], $overrides);
    }

    #[Test]
    public function test_guests_can_open_the_series_list(): void
    {
        Post::factory()->inSeries('Học Laravel từ đầu', 1)->create(['title' => 'Phần một xuất hiện']);

        $this->get(route('series.index'))
            ->assertOk()
            ->assertSee('Học Laravel từ đầu');
    }

    #[Test]
    public function test_the_list_groups_posts_into_one_entry_per_series(): void
    {
        Post::factory()->inSeries('Học Laravel từ đầu', 1)->create();
        Post::factory()->inSeries('Học Laravel từ đầu', 2)->create();
        Post::factory()->inSeries('Chuỗi khác', 1)->create();

        $response = $this->get(route('series.index'))->assertOk();

        $response->assertSee('Học Laravel từ đầu');
        $response->assertSee('Chuỗi khác');
        // Three parts in total, but only two series.
        $this->assertSame(2, Post::query()->whereNotNull('series_slug')->distinct('series_slug')->count('series_slug'));
    }

    #[Test]
    public function test_a_series_with_only_draft_parts_is_not_listed(): void
    {
        Post::factory()->inSeries('Chuỗi toàn nháp', 1)->draft()->create();

        $this->get(route('series.index'))
            ->assertOk()
            ->assertDontSee('Chuỗi toàn nháp');
    }

    #[Test]
    public function test_guests_can_read_a_series_with_its_parts_in_order(): void
    {
        $series = Post::factory()->inSeries('Đọc theo thứ tự', 1)
            ->create(['content' => 'noi-dung-phan-mot']);
        $second = Post::factory()->inSeries('Đọc theo thứ tự', 2)
            ->create(['content' => 'noi-dung-phan-hai']);
        $third = Post::factory()->inSeries('Đọc theo thứ tự', 3)
            ->create(['content' => 'noi-dung-phan-ba']);

        $this->assertSame($series->series_slug, $second->series_slug);
        $this->assertSame($series->series_slug, $third->series_slug);

        $this->get(route('series.show', $series->series_slug))
            ->assertOk()
            ->assertSee('Đọc theo thứ tự')
            // Assert on the bodies: titles also appear in the table of contents.
            ->assertSeeInOrder(['noi-dung-phan-mot', 'noi-dung-phan-hai', 'noi-dung-phan-ba']);
    }

    #[Test]
    public function test_draft_parts_are_hidden_from_readers(): void
    {
        $series = Post::factory()->inSeries('Chuỗi có nháp', 1)->create(['title' => 'Phần đã xuất bản']);
        Post::factory()->inSeries('Chuỗi có nháp', 2)->draft()->create(['title' => 'Phần nháp chưa công bố']);

        $this->get(route('series.show', $series->series_slug))
            ->assertOk()
            ->assertSee('Phần đã xuất bản')
            ->assertDontSee('Phần nháp chưa công bố');
    }

    #[Test]
    public function test_an_unknown_series_slug_is_a_404(): void
    {
        $this->get(route('series.show', 'khong-ton-tai'))->assertNotFound();
    }

    #[Test]
    public function test_the_list_can_be_searched_by_series_title(): void
    {
        Post::factory()->inSeries('Học Laravel từ đầu', 1)->create();
        Post::factory()->inSeries('Làm quen với Docker', 1)->create();

        $this->get(route('series.index', ['search' => 'Docker']))
            ->assertOk()
            ->assertSee('Làm quen với Docker')
            ->assertDontSee('Học Laravel từ đầu');
    }

    #[Test]
    public function test_the_long_form_flag_is_manual_and_survives_a_round_trip(): void
    {
        $creator = User::factory()->create(['role' => User::ROLE_CREATOR]);

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload(['is_long_form' => '1']))
            ->assertRedirect(route('dashboard.posts.index'));

        $post = Post::query()->latest('id')->firstOrFail();

        $this->assertTrue($post->is_long_form);
        $this->assertSame('hoc-laravel-tu-dau', $post->series_slug);
    }

    #[Test]
    public function test_an_unchecked_long_form_box_stores_false(): void
    {
        $creator = User::factory()->create(['role' => User::ROLE_CREATOR]);

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload())
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertFalse(Post::query()->latest('id')->firstOrFail()->is_long_form);
    }

    #[Test]
    public function test_two_titles_folding_to_the_same_ascii_get_different_slugs(): void
    {
        $first = Post::factory()->inSeries('Học Laravel', 1)->create();
        $second = Post::factory()->inSeries('Hoc Laravel', 1)->create();

        $this->assertSame('hoc-laravel', $first->series_slug);
        $this->assertNotSame($first->series_slug, $second->series_slug);
    }

    #[Test]
    public function test_the_long_form_flag_shows_on_the_series_pages(): void
    {
        $series = Post::factory()->inSeries('Chuỗi có bài dài', 1)->create(['is_long_form' => true]);
        Post::factory()->inSeries('Chuỗi có bài dài', 2)->create(['is_long_form' => false]);

        $this->get(route('series.index'))->assertOk()->assertSee('1 bài dài');
        $this->get(route('series.show', $series->series_slug))->assertOk()->assertSee('Bài dài kỳ');
    }

    #[Test]
    public function test_clearing_the_series_title_clears_the_series_slug(): void
    {
        $creator = User::factory()->create(['role' => User::ROLE_CREATOR]);

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload())
            ->assertRedirect(route('dashboard.posts.index'));

        $post = Post::query()->latest('id')->firstOrFail();
        $this->assertNotNull($post->series_slug);

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload([
                'series_title' => '',
                'series_part' => null,
            ]))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertNull($post->fresh()->series_slug);
    }

    #[Test]
    public function test_reading_time_is_estimated_from_the_content(): void
    {
        $post = Post::factory()->create([
            'content' => str_repeat('một hai ba bốn năm ', 100),
        ]);

        $this->assertSame(3, $post->readingMinutes());
    }

    #[Test]
    public function test_reading_time_ignores_markup_and_never_returns_zero(): void
    {
        $short = Post::factory()->create(['content' => '<p>Xin chào</p>']);
        $markupHeavy = Post::factory()->create(['content' => '<div><span>'.str_repeat('từ ', 250).'</span></div>']);

        $this->assertSame(1, $short->readingMinutes());
        // 250 words of real text, however much HTML wraps it.
        $this->assertSame(2, $markupHeavy->readingMinutes());
    }
}
