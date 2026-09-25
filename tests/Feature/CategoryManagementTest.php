<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_an_admin_can_create_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['name' => 'cong-nghe'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'cong-nghe']);
    }

    #[Test]
    public function test_renaming_a_category_updates_all_its_posts(): void
    {
        $admin = User::factory()->admin()->create();
        Category::register('cu');
        Post::factory()->create(['category' => 'cu']);
        Post::factory()->create(['category' => 'cu']);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', 'cu'), ['new_name' => 'moi'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSame(0, Post::query()->where('category', 'cu')->count());
        $this->assertSame(2, Post::query()->where('category', 'moi')->count());
        $this->assertDatabaseHas('categories', ['name' => 'moi']);
    }

    #[Test]
    public function test_renaming_merges_into_an_existing_category(): void
    {
        $admin = User::factory()->admin()->create();
        Category::register('mot');
        Category::register('hai');
        Post::factory()->create(['category' => 'mot']);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', 'mot'), ['new_name' => 'hai'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['name' => 'mot']);
        $this->assertDatabaseHas('categories', ['name' => 'hai']);
        $this->assertSame(1, Post::query()->where('category', 'hai')->count());
    }

    #[Test]
    public function test_deleting_a_category_with_posts_requires_a_move_to_target(): void
    {
        $admin = User::factory()->admin()->create();
        Category::register('xoa');
        Category::register('giu');
        Post::factory()->create(['category' => 'xoa']);

        $this->actingAs($admin)
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', 'xoa'))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'xoa']);
        $this->assertSame(1, Post::query()->where('category', 'xoa')->count());
    }

    #[Test]
    public function test_deleting_a_category_moves_its_posts_to_the_target(): void
    {
        $admin = User::factory()->admin()->create();
        Category::register('xoa');
        Category::register('giu');
        Post::factory()->create(['category' => 'xoa']);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', 'xoa'), ['move_to' => 'giu'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['name' => 'xoa']);
        $this->assertSame(0, Post::query()->where('category', 'xoa')->count());
        $this->assertSame(1, Post::query()->where('category', 'giu')->count());
    }

    #[Test]
    public function test_creating_a_duplicate_category_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        Category::register('trung');

        $this->actingAs($admin)
            ->from(route('admin.categories.index'))
            ->post(route('admin.categories.store'), ['name' => 'trung'])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function test_creators_cannot_manage_categories(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($creator)->post(route('admin.categories.store'), ['name' => 'abc'])->assertForbidden();
    }
}
