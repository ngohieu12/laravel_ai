<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreatePostTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Tạo bài viết mới trong hệ thống bằng HTML content. Có thể đăng nháp hoặc đăng công khai.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Tiêu đề bài viết'),
            'content' => $schema->string()->description('Nội dung bài viết'),
            'summary' => $schema->string()->description('Tóm tắt ngắn gọn nội dung bài viết'),
            'category' => $schema->string()->description('Danh mục bài viết (VD: "Công nghệ", "Kinh doanh")')->default('general'),
            'author' => $schema->string()->description('Tên tác giả')->default('Admin'),
            'is_published' => $schema->boolean()->description('Đăng công khai (true) hay nháp (false)')->default(false),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $title = $request->string('title')->trim()->toString();
        $content = $request->string('content')->trim()->toString();
        $summary = $request->string('summary')->trim()->toString();
        $category = $request->string('category')->trim()->value('general');
        $author = $request->string('author')->trim()->value('Admin');
        $isPublished = $request->boolean('is_published', false);

        if ($title === '') {
            return 'Vui lòng cung cấp tiêu đề bài viết.';
        }

        if ($content === '') {
            return 'Vui lòng cung cấp nội dung bài viết.';
        }

        if ($summary === '') {
            $summary = Str::limit(strip_tags($content), 150, '...');
        }

        // Ensure content is HTML — convert markdown-ish bold/italic as a safety net
        if (! preg_match('/<\/?[a-z][\s>]/i', $content)) {
            $content = Str::markdown($content);
        }

        try {
            $post = Post::create([
                'title' => $title,
                'content' => $content,
                'summary' => $summary,
                'category' => $category,
                'author' => $author,
                'is_published' => $isPublished,
            ]);
        } catch (ValidationException $e) {
            return "Lỗi dữ liệu: {$e->getMessage()}";
        } catch (\Throwable $e) {
            return "Không thể tạo bài viết: {$e->getMessage()}";
        }

        $status = $isPublished ? 'đã đăng công khai' : 'đã lưu nháp';

        return "**Tạo bài viết {$status}!**\n\n".
            "- Tiêu đề: {$post->title}\n".
            "- Tác giả: {$post->author}\n".
            "- Danh mục: {$post->category}\n".
            "- Tóm tắt: {$post->summary}\n".
            "- Slug: {$post->slug}\n".
            "- ID: {$post->id}";
    }
}
