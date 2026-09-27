<?php

namespace Database\Factories;

use App\Models\Series;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Series>
 */
class SeriesFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(12),
            'user_id' => User::factory(),
        ];
    }

    /**
     * A series that carries its own cover image.
     */
    public function withImage(?string $image = 'series/cover.jpg', ?string $alt = 'Ảnh đại diện chuỗi'): static
    {
        return $this->state(fn (array $attributes) => [
            'image' => $image,
            'image_alt' => $alt,
        ]);
    }
}
