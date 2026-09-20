<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PostEvent>
 */
class PostEventFactory extends Factory
{
    protected $model = PostEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => null,
            'type' => PostEvent::TYPE_VIEW,
            'meta' => [],
            'session_hash' => hash('sha256', Str::random(16)),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Record the event for a specific post.
     */
    public function forPost(Post $post): static
    {
        return $this->state(fn (array $attributes) => ['post_id' => $post->id]);
    }

    /**
     * Record the event on behalf of a user.
     */
    public function byUser(?User $user): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $user?->id]);
    }

    /**
     * Stamp the event at a specific moment (used to build time series).
     */
    public function at(\DateTimeInterface $when): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }

    /**
     * A share event on the given platform.
     */
    public function share(string $platform = 'facebook'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostEvent::TYPE_SHARE,
            'meta' => ['platform' => $platform],
        ]);
    }
}
