<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RichTextEditorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_post_form_ships_the_rich_text_editor(): void
    {
        $creator = User::factory()->creator()->create();

        $this->actingAs($creator)->get(route('dashboard.posts.create'))
            ->assertOk()
            ->assertSee('data-rich-editor', false)
            ->assertSee('quill@2.0.3', false)
            ->assertSee('name="content"', false)
            ->assertSee('data-mention-input', false);

        $post = Post::factory()->for($creator, 'user')->create(['content' => '<p>Nội dung cũ</p>']);

        $this->actingAs($creator)->get(route('dashboard.posts.edit', $post))
            ->assertOk()
            ->assertSee('data-rich-editor', false)
            ->assertSee('Nội dung cũ', false);
    }

    #[Test]
    public function the_editor_markup_escapes_the_stored_html(): void
    {
        $creator = User::factory()->creator()->create();
        $post = Post::factory()->for($creator, 'user')->create([
            'content' => '<p>Xin chào</p></textarea><script>alert(1)</script>',
        ]);

        $response = $this->actingAs($creator)->get(route('dashboard.posts.edit', $post))->assertOk();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
    }

    #[Test]
    public function editor_alignment_and_lists_survive_sanitizing(): void
    {
        $html = '<h2>Tiêu đề</h2>'
            .'<p class="ql-align-center" style="text-align:center">Căn giữa</p>'
            .'<p style="text-align: justify">Căn đều</p>'
            .'<ol><li data-list="bullet" class="ql-indent-1">Một</li><li data-list="bullet">Hai</li></ol>'
            .'<blockquote>Trích dẫn</blockquote>'
            .'<pre class="ql-syntax">code</pre>'
            .'<a href="https://example.com" target="_blank">link</a>'
            .'<img src="/images/cover.jpg" alt="Ảnh">';

        $sanitized = HtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('class="ql-align-center"', $sanitized);
        $this->assertStringContainsString('style="text-align: center"', $sanitized);
        $this->assertStringContainsString('style="text-align: justify"', $sanitized);
        $this->assertStringContainsString('data-list="bullet"', $sanitized);
        $this->assertStringContainsString('class="ql-indent-1"', $sanitized);
        $this->assertStringContainsString('class="ql-syntax"', $sanitized);
        $this->assertStringContainsString('<blockquote>Trích dẫn</blockquote>', $sanitized);
        $this->assertStringContainsString('href="https://example.com"', $sanitized);
        $this->assertStringContainsString('src="/images/cover.jpg"', $sanitized);
    }

    #[Test]
    public function only_text_align_survives_the_style_attribute(): void
    {
        $sanitized = HtmlSanitizer::sanitize(
            '<p style="position:fixed;top:0;background-image:url(javascript:alert(1));text-align:center">x</p>'
        );

        $this->assertStringContainsString('text-align: center', $sanitized);
        $this->assertStringNotContainsString('position', $sanitized);
        $this->assertStringNotContainsString('background-image', $sanitized);
    }

    #[Test]
    public function foreign_classes_are_dropped(): void
    {
        $sanitized = HtmlSanitizer::sanitize('<p class="hidden text-red-500">x</p><p class="ql-size-large">y</p>');

        $this->assertStringContainsString('<p>x</p>', $sanitized);
        $this->assertStringContainsString('class="ql-size-large"', $sanitized);
    }

    #[Test]
    public function dangerous_markup_is_still_stripped(): void
    {
        $vectors = [
            '<p onclick="alert(1)">x</p>' => '<p>x</p>',
            '<img src=x onerror=alert(1)>' => '<img src="">',
            '<a href="javascript:alert(1)">x</a>' => '<a>x</a>',
            '<p style="background:url(javascript:alert(1))">x</p>' => '<p>x</p>',
            '<div data-x="<script>alert(1)</script>">y</div>' => '<div>y</div>',
            '<p class="ql-syntax evil" style="color:red">mix</p>' => '<p>mix</p>',
        ];

        foreach ($vectors as $input => $expected) {
            $this->assertSame($expected, HtmlSanitizer::sanitize($input));
        }
    }

    #[Test]
    public function formatted_content_is_rendered_on_the_public_page(): void
    {
        $creator = User::factory()->creator()->create();

        $post = Post::factory()->for($creator, 'user')->create([
            'content' => '<p class="ql-align-center" style="text-align:center">Căn giữa</p>'
                .'<ol><li data-list="bullet">Một</li></ol>',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('style="text-align: center"', false)
            ->assertSee('data-list="bullet"', false);
    }
}
