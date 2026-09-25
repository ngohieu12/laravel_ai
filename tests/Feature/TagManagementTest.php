<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Bài viết có tag',
            'summary' => 'Tóm tắt nội dung',
            'content' => 'Nội dung chi tiết bài viết.',
            'category' => 'cong-nghe',
            'is_published' => '1',
        ], $overrides);
    }

    // -----------------------------------------------------------------
    // Model helpers
    // -----------------------------------------------------------------

    #[Test]
    public function test_tag_names_are_normalised_and_deduplicated_by_slug(): void
    {
        $this->assertSame(
            ['Laravel', 'Trí tuệ nhân tạo', 'php'],
            Tag::parseNames(' Laravel , laravel,#Trí   tuệ nhân tạo, tri tue nhan tao, php, ,'),
        );

        $this->assertSame('tri-tue-nhan-tao', Tag::slugFor('Trí tuệ nhân tạo'));
    }

    // -----------------------------------------------------------------
    // Admin tag management screen
    // -----------------------------------------------------------------

    #[Test]
    public function test_an_admin_sees_the_tag_list_with_post_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $post->syncTags('laravel, php');
        Tag::findOrCreateByName('bo-trong');

        $this->actingAs($admin)
            ->get(route('admin.tags.index'))
            ->assertOk()
            ->assertSee('Quản lý tag')
            ->assertSee('#laravel')
            ->assertSee('#php')
            ->assertSee('#bo-trong')
            ->assertSee('Dọn 1 tag không dùng');
    }

    #[Test]
    public function test_the_tag_list_can_be_searched(): void
    {
        $admin = User::factory()->admin()->create();
        Tag::findOrCreateByName('Trí tuệ nhân tạo');
        Tag::findOrCreateByName('php');

        $this->actingAs($admin)
            ->get(route('admin.tags.index', ['search' => 'tri tue']))
            ->assertOk()
            ->assertSee('#Trí tuệ nhân tạo')
            ->assertDontSee('#php');
    }

    #[Test]
    public function test_an_admin_can_create_several_tags_at_once(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.tags.store'), ['name' => 'Laravel, PHP, laravel'])
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tags', ['name' => 'Laravel', 'slug' => 'laravel']);
        $this->assertDatabaseHas('tags', ['name' => 'PHP', 'slug' => 'php']);
        $this->assertSame(2, Tag::query()->count());
    }

    #[Test]
    public function test_creating_an_existing_tag_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        Tag::findOrCreateByName('laravel');

        $this->actingAs($admin)
            ->from(route('admin.tags.index'))
            ->post(route('admin.tags.store'), ['name' => 'LARAVEL'])
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Tag::query()->count());
    }

    #[Test]
    public function test_an_admin_can_rename_a_tag(): void
    {
        $admin = User::factory()->admin()->create();
        $tag = Tag::findOrCreateByName('lavarel');
        $post = Post::factory()->create();
        $post->tags()->attach($tag);

        $this->actingAs($admin)
            ->put(route('admin.tags.update', $tag), ['new_name' => 'Laravel'])
            ->assertRedirect(route('admin.tags.index'));

        $tag->refresh();
        $this->assertSame('Laravel', $tag->name);
        $this->assertSame('laravel', $tag->slug);
        $this->assertTrue($post->tags()->whereKey($tag->id)->exists());
    }

    #[Test]
    public function test_renaming_onto_an_existing_tag_merges_them(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Tag::findOrCreateByName('js');
        $target = Tag::findOrCreateByName('javascript');

        $onlySource = Post::factory()->create();
        $onlySource->tags()->attach($source);
        $both = Post::factory()->create();
        $both->tags()->attach([$source->id, $target->id]);

        $this->actingAs($admin)
            ->put(route('admin.tags.update', $source), ['new_name' => 'JavaScript'])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseMissing('tags', ['id' => $source->id]);
        $this->assertSame(2, $target->posts()->count());
        $this->assertSame(1, $both->tags()->count());
    }

    #[Test]
    public function test_deleting_a_tag_detaches_it_but_keeps_the_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $tag = Tag::findOrCreateByName('xoa-toi');
        $post = Post::factory()->create();
        $post->tags()->attach($tag);

        $this->actingAs($admin)
            ->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('post_tag', ['tag_id' => $tag->id]);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    #[Test]
    public function test_an_admin_can_clean_up_unused_tags(): void
    {
        $admin = User::factory()->admin()->create();
        $used = Tag::findOrCreateByName('dang-dung');
        Tag::findOrCreateByName('bo-hoang-1');
        Tag::findOrCreateByName('bo-hoang-2');
        Post::factory()->create()->tags()->attach($used);

        $this->actingAs($admin)
            ->delete(route('admin.tags.destroy-unused'))
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('success', 'Đã dọn 2 tag không dùng.');

        $this->assertSame(['dang-dung'], Tag::query()->pluck('slug')->all());
    }

    #[Test]
    public function test_creators_and_readers_cannot_manage_tags(): void
    {
        $tag = Tag::findOrCreateByName('laravel');

        foreach ([User::factory()->creator()->create(), User::factory()->reader()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.tags.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.tags.store'), ['name' => 'moi'])->assertForbidden();
            $this->actingAs($user)->put(route('admin.tags.update', $tag), ['new_name' => 'x'])->assertForbidden();
            $this->actingAs($user)->delete(route('admin.tags.destroy', $tag))->assertForbidden();
        }

        $this->assertDatabaseHas('tags', ['slug' => 'laravel']);
        $this->assertDatabaseMissing('tags', ['slug' => 'moi']);
    }

    #[Test]
    public function test_guests_are_redirected_to_login_from_tag_management(): void
    {
        $this->get(route('admin.tags.index'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // Tagging posts from the dashboard
    // -----------------------------------------------------------------

    #[Test]
    public function test_a_creator_can_tag_a_new_post(): void
    {
        $creator = User::factory()->creator()->create();
        Tag::findOrCreateByName('Laravel');

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload(['tags' => 'laravel, Trí tuệ nhân tạo']))
            ->assertRedirect(route('dashboard.posts.index'));

        $post = Post::query()->where('title', 'Bài viết có tag')->firstOrFail();

        $this->assertEqualsCanonicalizing(['laravel', 'tri-tue-nhan-tao'], $post->tags()->pluck('slug')->all());
        // The existing "Laravel" tag was reused, not duplicated.
        $this->assertSame(2, Tag::query()->count());
    }

    #[Test]
    public function test_updating_a_post_replaces_its_tags(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator)->create();
        $post->syncTags('cu, giu-lai');

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload(['tags' => 'giu-lai, moi']))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertEqualsCanonicalizing(['giu-lai', 'moi'], $post->tags()->pluck('slug')->all());
    }

    #[Test]
    public function test_clearing_the_tag_field_removes_all_tags(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator)->create();
        $post->syncTags('mot, hai');

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload(['tags' => '']))
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertSame(0, $post->tags()->count());
    }

    #[Test]
    public function test_updating_without_the_tag_field_keeps_existing_tags(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator)->create();
        $post->syncTags('giu-nguyen');

        $this->actingAs($creator)
            ->put(route('dashboard.posts.update', $post), $this->payload())
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertSame(['giu-nguyen'], $post->tags()->pluck('slug')->all());
    }

    #[Test]
    public function test_a_post_cannot_have_too_many_or_too_long_tags(): void
    {
        $creator = User::factory()->creator()->create();
        $tooMany = implode(',', array_map(fn (int $i) => 'tag'.$i, range(1, Tag::MAX_PER_POST + 1)));

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload(['tags' => $tooMany]))
            ->assertSessionHasErrors('tags');

        $this->actingAs($creator)
            ->post(route('dashboard.posts.store'), $this->payload(['tags' => str_repeat('a', Tag::MAX_NAME_LENGTH + 1)]))
            ->assertSessionHasErrors('tags');

        $this->assertSame(0, Post::query()->count());
        $this->assertSame(0, Tag::query()->count());
    }

    #[Test]
    public function test_the_post_forms_render_with_tag_suggestions(): void
    {
        $creator = User::factory()->creator()->create();
        Tag::findOrCreateByName('goi-y');
        $post = Post::factory()->for($creator)->create();
        $post->syncTags('da-gan');

        $this->actingAs($creator)
            ->get(route('dashboard.posts.create'))
            ->assertOk()
            ->assertSee('name="tags"', false)
            ->assertSee('#goi-y');

        $this->actingAs($creator)
            ->get(route('dashboard.posts.edit', $post))
            ->assertOk()
            ->assertSee('value="da-gan"', false);
    }

    #[Test]
    public function test_the_dashboard_list_can_be_filtered_by_tag(): void
    {
        $admin = User::factory()->admin()->create();
        $tagged = Post::factory()->create(['title' => 'Bai co tag laravel']);
        $tagged->syncTags('laravel');
        Post::factory()->create(['title' => 'Bai khong tag']);

        $this->actingAs($admin)
            ->get(route('dashboard.posts.index', ['tag' => 'laravel']))
            ->assertOk()
            ->assertSee('Bai co tag laravel')
            ->assertDontSee('Bai khong tag');
    }

    // -----------------------------------------------------------------
    // Public reading surface
    // -----------------------------------------------------------------

    #[Test]
    public function test_the_public_listing_can_be_filtered_by_tag(): void
    {
        $tagged = Post::factory()->create(['title' => 'Bai cong khai co tag']);
        $tagged->syncTags('Trí tuệ nhân tạo');
        Post::factory()->create(['title' => 'Bai cong khai khong tag']);

        $this->get(route('posts.index', ['tag' => 'tri-tue-nhan-tao']))
            ->assertOk()
            ->assertSee('Tag: #Trí tuệ nhân tạo')
            ->assertSee('Bai cong khai co tag')
            ->assertDontSee('Bai cong khai khong tag');
    }

    #[Test]
    public function test_popular_tags_only_count_published_posts(): void
    {
        Post::factory()->create()->syncTags('cong-khai');
        Post::factory()->draft()->create()->syncTags('chi-nhap');

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('#cong-khai')
            ->assertDontSee('#chi-nhap');
    }

    #[Test]
    public function test_the_post_detail_shows_its_tags(): void
    {
        $post = Post::factory()->create();
        $post->syncTags('laravel, php');

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('#laravel')
            ->assertSee('#php')
            ->assertSee(route('posts.index', ['tag' => 'php']), false);
    }

    #[Test]
    public function test_deleting_a_post_removes_its_tag_links(): void
    {
        $post = Post::factory()->create();
        $post->syncTags('laravel');

        $post->delete();

        $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id]);
        $this->assertDatabaseHas('tags', ['slug' => 'laravel']);
    }
}
