<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostEvent;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EngagementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_the_full_demo_seeder_runs(): void
    {
        Storage::fake('public');

        $this->app->make(DatabaseSeeder::class)->run();

        $this->assertSame(3, User::query()->count());
        $this->assertGreaterThanOrEqual(6, Post::query()->count());
        $this->assertGreaterThan(0, PostEvent::query()->count());
        $this->assertGreaterThan(0, Tag::query()->count());
        $this->assertTrue(
            Post::query()->where('slug', 'gioi-thieu-ve-laravel-13')->firstOrFail()
                ->tags()->where('slug', 'laravel')->exists(),
        );
    }

    #[Test]
    public function test_the_engagement_seeder_generates_valid_events(): void
    {
        Post::factory()->count(6)->create();

        $this->app->make(EngagementSeeder::class)->run();

        $views = PostEvent::query()->where('type', PostEvent::TYPE_VIEW)->get();
        $shares = PostEvent::query()->where('type', PostEvent::TYPE_SHARE)->get();

        $this->assertNotEmpty($views);
        $this->assertNotEmpty($shares);

        $sources = $views->map(fn (PostEvent $event) => json_decode((string) $event->getRawOriginal('meta'), true)['source'] ?? null)->unique();
        $platforms = $shares->map(fn (PostEvent $event) => json_decode((string) $event->getRawOriginal('meta'), true)['platform'] ?? null)->unique();

        $this->assertEmpty($sources->diff(['direct', 'internal', 'chatbot', 'search', 'facebook', 'external']));
        $this->assertEmpty($platforms->diff(['facebook', 'x', 'linkedin', 'telegram', 'copy', 'email']));
    }
}
