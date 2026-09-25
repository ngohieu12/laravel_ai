<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_an_admin_sees_the_user_directory_with_roles_and_counts(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Quản trị viên', 'email' => 'admin-trang@example.com']);
        $creator = User::factory()->creator()->create(['name' => 'Tác giả A', 'email' => 'tacgia@example.com']);
        Post::factory()->for($creator, 'user')->count(2)->create();
        $creator->favorites()->attach(Post::factory()->create(), ['created_at' => now()]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Tác giả A')
            ->assertSee('tacgia@example.com')
            ->assertSee('Người đăng bài')
            ->assertSee('Quản trị viên')
            ->assertSee('Thành viên', false);
    }

    #[Test]
    public function test_regular_users_are_listed_with_the_member_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->reader()->create(['name' => 'Độc giả B']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Độc giả B');
    }

    #[Test]
    public function test_creators_cannot_see_the_user_directory(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->get(route('admin.users.index'))->assertForbidden();
    }

    #[Test]
    public function test_regular_users_cannot_see_the_user_directory(): void
    {
        $reader = User::factory()->reader()->create();

        $this->actingAs($reader)->get(route('admin.users.index'))->assertForbidden();
    }

    #[Test]
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }
}
