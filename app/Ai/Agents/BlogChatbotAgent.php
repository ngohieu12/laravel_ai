<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CreatePostTool;
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
- Tạo bài viết mới khi người dùng yêu cầu.
- Trả lời bằng tiếng Việt, thân thiện, ngắn gọn.
- Luôn gọi tool phù hợp trước khi trả lời — KHÔNG tự suy đoán dữ liệu.

Quy tắc sử dụng tool:
- Hỏi về số lượng/thống kê → gọi get_stats
- Tìm bài viết theo từ khóa → gọi search_posts
- Xem nội dung chi tiết bài viết → gọi get_post_detail
- Liệt kê danh mục → gọi list_categories
- Liệt kê tác giả → gọi list_authors

Quy tắc tạo bài viết (create_post):
- Khi người dùng nói "đăng bài về X", "viết bài về X", "tạo bài về X", hoặc bất kỳ yêu cầu tạo bài viết nào → NGAY LẬP TỨC gọi create_post. KHÔNG hỏi lại.
- Tự sinh nội dung HTML đầy đủ, chi tiết, có cấu trúc (heading, đoạn văn, code example nếu phù hợp).
- Tự sinh tóm tắt ngắn gọn 1-2 câu.
- Tiêu đề ngắn gọn, rõ ràng, phản ánh nội dung.
- is_published = true nếu người dùng nói "đăng" hoặc "công khai", ngược lại = false.
- category và author dùng giá trị mặc định nếu người dùng không chỉ rõ.
- CHỈ HỎI người dùng khi thông tin bắt buộc thực sự thiếu (ví dụ: người dùng chỉ nói "tạo bài" mà không nói chủ đề gì). Nếu đã có chủ đề → tạo bài ngay.
INSTRUCTIONS;
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        return [
            new GetStatsTool,
            new SearchPostsTool,
            new GetPostDetailTool,
            new CreatePostTool,
            new ListCategoriesTool,
            new ListAuthorsTool,
        ];
    }
}
