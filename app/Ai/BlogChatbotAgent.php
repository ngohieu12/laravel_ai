<?php

namespace App\Ai;

use App\Ai\Tools\GetPostDetailTool;
use App\Ai\Tools\GetStatsTool;
use App\Ai\Tools\ListAuthorsTool;
use App\Ai\Tools\ListCategoriesTool;
use App\Ai\Tools\SearchPostsTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

final class BlogChatbotAgent implements Agent, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
Bạn là trợ lý AI thông minh cho ứng dụng Blog Manager.

Nhiệm vụ:
- Trả lời câu hỏi của người dùng về các bài viết trong hệ thống.
- Sử dụng các tool có sẵn để tìm kiếm, xem chi tiết, và lấy thống kê bài viết.
- Trả lời bằng tiếng Việt, thân thiện, ngắn gọn.
- Luôn gọi tool phù hợp trước khi trả lời — KHÔNG tự suy đoán dữ liệu.

Quy tắc sử dụng tool:
- Hỏi về số lượng/thống kê → gọi get_stats
- Tìm bài viết theo từ khóa → gọi search_posts
- Xem nội dung chi tiết bài viết → gọi get_post_detail
- Liệt kê danh mục → gọi list_categories
- Liệt kê tác giả → gọi list_authors
INSTRUCTIONS;
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        return [
            new GetStatsTool(),
            new SearchPostsTool(),
            new GetPostDetailTool(),
            new ListCategoriesTool(),
            new ListAuthorsTool(),
        ];
    }
}
