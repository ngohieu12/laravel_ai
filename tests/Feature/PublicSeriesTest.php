<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Series;
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
    public function test_guests_can_open_the_series_list(): void
    {
        $series = Series::factory()->create(['title' => 'Học Laravel từ đầu']);
        Post::factory()->inSeries($series, 1)->create();

        $this->get(route('series.index'))
            ->assertOk()
            ->assertSee('Học Laravel từ đầu');
    }

    #[Test]
    public function test_parts_of_one_series_collapse_into_a_single_entry(): void
    {
        $series = Series::factory()->create(['title' => 'Một chuỗi']);
        Post::factory()->inSeries($series, 1)->create();
        Post::factory()->inSeries($series, 2)->create();
        $other = Series::factory()->create(['title' => 'Chuỗi khác']);
        Post::factory()->inSeries($other, 1)->create();

        $this->get(route('series.index'))
            ->assertOk()
            ->assertSee('Một chuỗi')
            ->assertSee('Chuỗi khác')
            ->assertSee('2 phần');

        // A series with no published part has nothing to read, so it is not listed.
        $empty = Series::factory()->create(['title' => 'Chuỗi rỗng']);
        $this->get(route('series.index'))->assertOk()->assertDontSee('Chuỗi rỗng');
        $this->get(route('series.show', $empty))->assertNotFound();

        $this->assertSame(2, Series::query()->whereHas('posts', fn ($q) => $q->published())->count());
    }

    #[Test]
    public function test_a_series_with_only_draft_parts_is_not_listed(): void
    {
        $series = Series::factory()->create(['title' => 'Chuỗi toàn nháp']);
        Post::factory()->inSeries($series, 1)->draft()->create();

        $this->get(route('series.index'))
            ->assertOk()
            ->assertDontSee('Chuỗi toàn nháp');

        $this->get(route('series.show', $series))->assertNotFound();
    }

    #[Test]
    public function test_guests_can_read_a_series_with_its_parts_in_order(): void
    {
        $series = Series::factory()->create(['title' => 'Đọc theo thứ tự']);
        Post::factory()->inSeries($series, 1)->create(['content' => 'noi-dung-phan-mot']);
        Post::factory()->inSeries($series, 2)->create(['content' => 'noi-dung-phan-hai']);
        Post::factory()->inSeries($series, 3)->create(['content' => 'noi-dung-phan-ba']);

        $this->get(route('series.show', $series))
            ->assertOk()
            ->assertSee('Đọc theo thứ tự')
            // Assert on the bodies: titles also appear in the table of contents.
            ->assertSeeInOrder(['noi-dung-phan-mot', 'noi-dung-phan-hai', 'noi-dung-phan-ba']);
    }

    #[Test]
    public function test_draft_parts_are_hidden_from_readers(): void
    {
        $series = Series::factory()->create(['title' => 'Chuỗi có nháp']);
        Post::factory()->inSeries($series, 1)->create(['title' => 'Phần đã xuất bản']);
        Post::factory()->inSeries($series, 2)->draft()->create(['title' => 'Phần nháp chưa công bố']);

        $this->get(route('series.show', $series))
            ->assertOk()
            ->assertSee('Phần đã xuất bản')
            ->assertDontSee('Phần nháp chưa công bố');
    }

    #[Test]
    public function test_renaming_a_series_keeps_its_url_and_its_parts(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create(['title' => 'Tên cũ']);
        Post::factory()->inSeries($series, 1)->create(['title' => 'Phần một']);
        Post::factory()->inSeries($series, 2)->create(['title' => 'Phần hai']);

        $this->actingAs($creator)
            ->put(route('dashboard.series.update', $series), ['title' => 'Tên mới'])
            ->assertRedirect();

        // The id is the identity, so renaming must not break the public page
        // nor detach the parts.
        $this->get(route('series.show', $series))
            ->assertOk()
            ->assertSee('Tên mới')
            ->assertSee('Phần một')
            ->assertSee('Phần hai');
    }

    #[Test]
    public function test_the_list_can_be_searched_by_series_title(): void
    {
        $laravel = Series::factory()->create(['title' => 'Học Laravel từ đầu']);
        $docker = Series::factory()->create(['title' => 'Làm quen với Docker']);
        Post::factory()->inSeries($laravel, 1)->create();
        Post::factory()->inSeries($docker, 1)->create();

        $this->get(route('series.index', ['search' => 'Docker']))
            ->assertOk()
            ->assertSee('Làm quen với Docker')
            ->assertDontSee('Học Laravel từ đầu');
    }

    #[Test]
    public function test_the_long_form_flag_is_manual_and_survives_a_round_trip(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload($series, ['is_long_form' => '1']))
            ->assertRedirect(route('dashboard.posts.index'));

        $post = Post::query()->latest('id')->firstOrFail();

        $this->assertTrue($post->is_long_form);
        $this->assertSame($series->id, $post->series_id);
    }

    #[Test]
    public function test_an_unchecked_long_form_box_stores_false(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload($series))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertFalse(Post::query()->latest('id')->firstOrFail()->is_long_form);
    }

    #[Test]
    public function test_the_long_form_flag_shows_on_the_series_pages(): void
    {
        $series = Series::factory()->create(['title' => 'Chuỗi có bài dài']);
        Post::factory()->inSeries($series, 1)->create(['is_long_form' => true]);
        Post::factory()->inSeries($series, 2)->create(['is_long_form' => false]);

        $this->get(route('series.index'))->assertOk()->assertSee('1 bài dài');
        $this->get(route('series.show', $series))->assertOk()->assertSee('Bài dài kỳ');
    }

    #[Test]
    public function test_a_post_can_leave_a_series(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create();
        $post = Post::factory()->for($creator, 'user')->inSeries($series, 1)->create();

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload($series, [
                'series_id' => '',
                'series_part' => null,
            ]))
            ->assertRedirect(route('dashboard.posts.index'));

        $post->refresh();

        $this->assertNull($post->series_id);
        $this->assertNull($post->series_part);
    }

    #[Test]
    public function test_a_post_cannot_join_a_series_that_does_not_exist(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload(new Series(['title' => 'Chưa lưu']), ['series_id' => '999999']))
            ->assertSessionHasErrors('series_id');
    }

    #[Test]
    public function test_deleting_a_series_keeps_its_posts(): void
    {
        $creator = User::factory()->creator()->create();
        $series = Series::factory()->create(['title' => 'Chuỗi sắp xoá']);
        $post = Post::factory()->for($creator, 'user')->inSeries($series, 1)->create();

        $this->actingAs($creator)
            ->delete(route('dashboard.series.destroy', $series))
            ->assertRedirect();

        $this->assertDatabaseMissing('series', ['id' => $series->id]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'series_id' => null]);
    }

    #[Test]
    public function test_series_management_is_closed_to_readers(): void
    {
        $reader = User::factory()->reader()->create();

        $this->actingAs($reader)->get(route('dashboard.series.index'))->assertForbidden();
        $this->actingAs($reader)
            ->post(route('dashboard.series.store'), ['title' => 'Không được tạo'])
            ->assertForbidden();
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
