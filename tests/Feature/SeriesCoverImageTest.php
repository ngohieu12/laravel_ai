<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Series;
use App\Models\User;
use App\Services\PostImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeriesCoverImageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload accepted by the dashboard series form.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Chuỗi có ảnh đại diện',
            'description' => 'Mô tả chuỗi bài viết.',
        ], $overrides);
    }

    #[Test]
    public function test_creating_a_series_stores_the_uploaded_cover_image(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('dashboard.series.store'), $this->payload([
                'image' => UploadedFile::fake()->image('anh-chuoi.jpg'),
                'image_alt' => 'Ảnh minh hoạ chuỗi',
            ]))
            ->assertRedirect();

        $series = Series::query()->sole();

        $this->assertNotNull($series->image);
        $this->assertStringStartsWith(PostImage::SERIES_DIRECTORY.'/', $series->image);
        $this->assertSame('Ảnh minh hoạ chuỗi', $series->image_alt);
        Storage::disk(PostImage::DISK)->assertExists($series->image);
    }

    #[Test]
    public function test_the_series_cover_is_stored_apart_from_the_post_covers(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('dashboard.series.store'), $this->payload([
                'image' => UploadedFile::fake()->image('chuoi.jpg'),
            ]))
            ->assertRedirect();

        $this->assertStringStartsWith(
            PostImage::SERIES_DIRECTORY.'/',
            Series::query()->sole()->image
        );
    }

    #[Test]
    public function test_the_cover_image_is_shown_on_the_dashboard_list(): void
    {
        Storage::fake(PostImage::DISK);

        $series = Series::factory()->withImage(
            UploadedFile::fake()->image('cover.png')->store(PostImage::SERIES_DIRECTORY, PostImage::DISK),
            'Ảnh bìa chuỗi'
        )->create(['title' => 'Chuỗi có bìa']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard.series.index'))
            ->assertOk()
            ->assertSee(Storage::disk(PostImage::DISK)->url($series->image), false)
            ->assertSee('Ảnh bìa chuỗi');
    }

    #[Test]
    public function test_the_cover_image_is_shown_on_the_public_list(): void
    {
        Storage::fake(PostImage::DISK);

        $series = Series::factory()->withImage(
            UploadedFile::fake()->image('public.png')->store(PostImage::SERIES_DIRECTORY, PostImage::DISK),
            'Ảnh bìa công khai'
        )->create(['title' => 'Chuỗi công khai có bìa']);

        Post::factory()->inSeries($series, 1)->create();

        $this->get(route('series.index'))
            ->assertOk()
            ->assertSee(Storage::disk(PostImage::DISK)->url($series->image), false)
            ->assertSee('Ảnh bìa công khai');
    }

    #[Test]
    public function test_a_series_without_a_cover_renders_a_placeholder_on_the_dashboard_list(): void
    {
        Series::factory()->create(['image' => null]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard.series.index'))
            ->assertOk()
            ->assertSee('Chuỗi chưa có ảnh đại diện');
    }

    #[Test]
    public function test_updating_a_series_replaces_the_old_cover_and_deletes_it_from_disk(): void
    {
        Storage::fake(PostImage::DISK);

        $oldPath = UploadedFile::fake()->image('old.jpg')->store(PostImage::SERIES_DIRECTORY, PostImage::DISK);
        $series = Series::factory()->create(['image' => $oldPath, 'image_alt' => 'Ảnh cũ']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('dashboard.series.update', $series), $this->payload([
                'image' => UploadedFile::fake()->image('new.png'),
                'image_alt' => 'Ảnh mới',
            ]))
            ->assertRedirect();

        $series->refresh();

        $this->assertNotSame($oldPath, $series->image);
        $this->assertSame('Ảnh mới', $series->image_alt);
        Storage::disk(PostImage::DISK)->assertExists($series->image);
        Storage::disk(PostImage::DISK)->assertMissing($oldPath);
    }

    #[Test]
    public function test_updating_a_series_without_touching_the_cover_keeps_it(): void
    {
        Storage::fake(PostImage::DISK);

        $path = UploadedFile::fake()->image('keep.jpg')->store(PostImage::SERIES_DIRECTORY, PostImage::DISK);
        $series = Series::factory()->create(['image' => $path, 'image_alt' => 'Giữ nguyên ảnh']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('dashboard.series.update', $series), $this->payload(['title' => 'Tên đã đổi']))
            ->assertRedirect();

        $series->refresh();

        $this->assertSame($path, $series->image);
        $this->assertSame('Giữ nguyên ảnh', $series->image_alt);
        Storage::disk(PostImage::DISK)->assertExists($path);
    }

    #[Test]
    public function test_the_cover_can_be_removed_with_the_remove_image_flag(): void
    {
        Storage::fake(PostImage::DISK);

        $path = UploadedFile::fake()->image('remove.jpg')->store(PostImage::SERIES_DIRECTORY, PostImage::DISK);
        $series = Series::factory()->create(['image' => $path, 'image_alt' => 'Ảnh sẽ bị xoá']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('dashboard.series.update', $series), $this->payload(['remove_image' => '1']))
            ->assertRedirect();

        $series->refresh();

        $this->assertNull($series->image);
        $this->assertNull($series->image_alt);
        Storage::disk(PostImage::DISK)->assertMissing($path);
    }

    #[Test]
    public function test_deleting_a_series_deletes_its_cover_but_keeps_the_posts(): void
    {
        Storage::fake(PostImage::DISK);

        $path = UploadedFile::fake()->image('doomed.jpg')->store(PostImage::SERIES_DIRECTORY, PostImage::DISK);
        $series = Series::factory()->create(['image' => $path]);
        $post = Post::factory()->inSeries($series, 1)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('dashboard.series.destroy', $series))
            ->assertRedirect();

        $this->assertDatabaseMissing('series', ['id' => $series->id]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'series_id' => null]);
        Storage::disk(PostImage::DISK)->assertMissing($path);
    }

    #[Test]
    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('dashboard.series.store'), $this->payload([
                'image' => UploadedFile::fake()->create('script.php', 20, 'application/x-php'),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('series', 0);
    }

    #[Test]
    public function test_an_oversized_cover_is_rejected(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('dashboard.series.store'), $this->payload([
                'image' => UploadedFile::fake()->image('big.jpg')->size(5 * 1024),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('series', 0);
    }

    #[Test]
    public function test_a_creator_can_upload_a_cover_for_a_series(): void
    {
        Storage::fake(PostImage::DISK);

        $this->actingAs(User::factory()->creator()->create())
            ->post(route('dashboard.series.store'), $this->payload([
                'image' => UploadedFile::fake()->image('cover.webp', 400, 300),
            ]))
            ->assertRedirect();

        $series = Series::query()->sole();

        $this->assertNotNull($series->image);
        Storage::disk(PostImage::DISK)->assertExists($series->image);
    }

    #[Test]
    public function test_the_alt_text_falls_back_to_the_series_title(): void
    {
        $series = Series::factory()->withImage('series/cover.jpg', null)->create([
            'title' => 'Tiêu đề dùng làm alt',
        ]);

        $this->assertSame('Tiêu đề dùng làm alt', $series->imageAlt());

        $blank = Series::factory()->withImage('series/cover.jpg', '   ')->create();

        $this->assertSame((string) $blank->title, $blank->imageAlt());
    }

    #[Test]
    public function test_a_remote_cover_url_is_served_as_is(): void
    {
        $series = Series::factory()->create(['image' => 'https://cdn.example.com/series.jpg']);

        $this->assertSame('https://cdn.example.com/series.jpg', $series->imageUrl());
    }
}
