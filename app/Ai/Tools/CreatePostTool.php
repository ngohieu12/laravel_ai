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
            'is_published' => $schema->boolean()->description('Đăng công khai (true) hay nháp (false)')->default(false),
            'image' => $schema->string()
                ->description('URL ảnh công khai dùng làm ảnh đại diện cho bài viết (mỗi bài chỉ có 1 ảnh, hiển thị ở cả danh sách và chi tiết). Để trống nếu không có ảnh.')
                ->nullable()
                ->default(null),
            'image_alt' => $schema->string()
                ->description('Mô tả ngắn cho ảnh đại diện (alt text)')
                ->nullable()
                ->default(null),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $title = $request->string('title')->trim()->toString();
        $content = $request->string('content')->trim()->toString();
        $summary = $request->string('summary')->trim()->toString();
        $category = $request->string('category')->trim()->value('general');
        $isPublished = $request->boolean('is_published', false);
        $image = $request->string('image')->trim()->toString();
        $imageAlt = $request->string('image_alt')->trim()->toString();

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
                'user_id' => auth()->id(),
                'is_published' => $isPublished,
                // Only public http(s) URLs are accepted from the model.
                'image' => Str::startsWith($image, ['http://', 'https://']) ? $image : null,
                'image_alt' => $imageAlt !== '' ? $imageAlt : null,
            ]);
        } catch (ValidationException $e) {
            return "Lỗi dữ liệu: {$e->getMessage()}";
        } catch (\Throwable $e) {
            return "Không thể tạo bài viết: {$e->getMessage()}";
        }

        $status = $isPublished ? 'đã đăng công khai' : 'đã lưu nháp';

        $authorName = $post->user?->name ?? 'Admin';

        return "**Tạo bài viết {$status}!**\n\n".
            "- Tiêu đề: {$post->title}\n".
            "- Tác giả: {$authorName}\n".
            "- Danh mục: {$post->category}\n".
            "- Tóm tắt: {$post->summary}\n".
            "- Slug: {$post->slug}\n".
            ($post->image ? "- Ảnh đại diện: {$post->image}\n" : '').
            "- ID: {$post->id}";
    }
}
