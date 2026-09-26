<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Series;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->sentence(5), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'summary' => fake()->sentence(18),
            'content' => '<p>'.fake()->paragraph(6).'</p><p>'.fake()->paragraph(4).'</p>',
            'content_type' => Post::CONTENT_TYPE_TEXT,
            'video_url' => null,
            'series_id' => null,
            'series_part' => null,
            'image' => null,
            'image_alt' => null,
            'category' => fake()->randomElement(['cong-nghe', 'hoc-tap', 'cuoc-song', 'tutorial', 'general']),
            'user_id' => User::factory(),
            'is_published' => true,
            'views_count' => 0,
            'shares_count' => 0,
            'favorites_count' => 0,
            'comments_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * A post that has a stored cover image path.
     */
    public function withImage(?string $image = 'posts/cover.jpg', ?string $alt = 'Ảnh đại diện'): static
    {
        return $this->state(fn (array $attributes) => [
            'image' => $image,
            'image_alt' => $alt,
        ]);
    }

    /**
     * An unpublished draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }

    /**
     * Force a specific category ("chủ đề").
     */
    public function inCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => ['category' => $category]);
    }

    /**
     * A video post (embedded YouTube / Vimeo player instead of written body).
     */
    public function video(?string $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => Post::CONTENT_TYPE_VIDEO,
            'video_url' => $url,
            'content' => '',
        ]);
    }

    /**
     * Part of a long-running series ("chuỗi bài viết dài kỳ").
     */
    public function inSeries(Series $series, int $part): static
    {
        return $this->state(fn (array $attributes) => [
            'series_id' => $series->id,
            'series_part' => $part,
        ]);
    }
}
