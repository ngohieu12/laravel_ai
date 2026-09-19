<?php

namespace App\Ai\Tools;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPostCommentsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Xem các bình luận (bao gồm cả trả lời lồng nhau) của một bài viết theo từ khóa tiêu đề. Dùng khi người dùng hỏi về bình luận, phản hồi, tranh luận, trả lời, ai đã nói gì trong bài viết X.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title_keyword' => $schema->string()
                ->description('Từ khóa trong tiêu đề bài viết muốn xem bình luận'),
            'limit' => $schema->integer()
                ->description('Số lượng bình luận gốc (root) tối đa muốn lấy (mặc định 10, tối đa 30)')
                ->default(10),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $keyword = $request->string('title_keyword')->toString();
        $limit = (int) $request->input('limit', 10);
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 30) {
            $limit = 30;
        }

        if (trim($keyword) === '') {
            return 'Vui lòng cung cấp từ khóa tiêu đề bài viết.';
        }

        $post = Post::published()
            ->where('title', 'like', "%{$keyword}%")
            ->first();

        if (! $post) {
            return "Không tìm thấy bài viết với tiêu đề chứa \"{$keyword}\".";
        }

        $roots = Comment::loadForPost($post, null);
        $totalCount = $post->comments()->count();

        if ($totalCount === 0) {
            return "Bài viết \"{$post->title}\" hiện chưa có bình luận nào.";
        }

        $slice = $roots->take($limit);

        $result = "**Bình luận của bài viết: {$post->title}** (tổng {$totalCount} bình luận)\n\n";
        foreach ($slice as $root) {
            $result .= $this->renderComment($root, 0);
        }

        if ($roots->count() > $limit) {
            $result .= "\n...(còn " . ($roots->count() - $limit) . " bình luận gốc khác)\n";
        }

        return $result;
    }

    private function renderComment(Comment $comment, int $depth): string
    {
        $indent = str_repeat('  ', $depth);
        $prefix = $depth === 0 ? '- ' : '↳ ';
        $author = $comment->user?->name ?? 'Người dùng';
        $date = $comment->created_at->format('d/m/Y H:i');
        $likes = $comment->favorites_count;
        $likeText = $likes > 0 ? " (❤️ {$likes})" : '';
        $content = trim(preg_replace('/\s+/', ' ', $comment->content));

        $out = "{$indent}{$prefix}**{$author}**{$likeText} · {$date}: {$content}\n";

        foreach ($comment->replies as $reply) {
            $out .= $this->renderComment($reply, $depth + 1);
        }

        return $out;
    }
}
