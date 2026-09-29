<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Blog Manager — tính năng & cài đặt

Ứng dụng demo trong repo này là một blog có chatbot AI, kèm các nhóm tính năng chính:

**Loại nội dung: chữ, video và audio**

- Mỗi bài viết chọn một loại nội dung ở dashboard: **📝 bài viết chữ**, **🎥 bài viết video** (dán link YouTube / Vimeo) hoặc **🎧 bài viết audio** (tải lên tệp MP3).
- Bài audio lưu tệp trên disk `public` trong `storage/app/public/posts/audio/`, chấp nhận `mp3, m4a, wav, ogg`, tối đa 20 MB; trang bài viết hiển thị trình phát `<audio controls>` kèm tên đoạn âm thanh (tuỳ chọn, mặc định lấy tiêu đề bài).
- Bài video / audio không bắt buộc có nội dung chữ (nội dung chỉ làm mô tả); đổi sang loại khác sẽ tự xoá tệp âm thanh cũ, xoá bài cũng xoá luôn tệp.
- Danh sách bài ( công khai và trong dashboard) hiển thị badge **🎧 Audio** bên cạnh badge **🎥 Video**.
- **Thư viện MP3** (`/dashboard/audio`) là màn danh sách riêng cho toàn bộ bài audio: nghe thử trực tiếp, dung lượng, tên file, tác giả, trạng thái, lọc theo từ khoá / trạng thái, xoá riêng file MP3 mà vẫn giữ bài viết. Admin xem toàn bộ, creator chỉ thấy file của mình.
- **Bộ lọc loại nội dung** ngay trong danh sách bài (cả trang công khai lẫn dashboard): 📝 Chữ / 🎥 Video / 🎧 Audio, tham số `?type=text|video|audio`, chọn xong tự lọc.
- **Danh mục mặc định `Video` và `MP3`**: hệ thống chỉ tạo sẵn hai danh mục này (`Category::DEFAULTS`, seed `CategorySeeder`). Bài video mặc định rơi vào danh mục `Video`, bài audio vào `MP3`; tác giả vẫn tự ghi đè được nếu muốn. Hai danh mục được tự tạo lại mỗi lần mở màn quản lý nên xoá nhầm không sao.
- **Màn admin "Danh mục Video & MP3"** (`/admin/media-categories`): xem số bài / số bài đã xuất bản / số bài đúng loại nội dung của từng danh mục, lọc theo từng nhóm, đổi tên danh mục (cascade sang mọi bài) và nhảy tới danh sách bài tương ứng.

**@mention trong bình luận và bài viết**

- Mỗi tài khoản có tên định danh `@username` tự sinh từ tên hiển thị (bỏ dấu, không trùng — trùng thì thêm hậu tố `-2`, `-3`…). Xem tại hồ sơ quản trị `/admin/users/{id}`.
- Gõ `@` trong ô bình luận (kể cả ô trả lời) hoặc trong trình soạn bài ở dashboard sẽ hiện danh sách gợi ý: tìm theo cả `username` lẫn tên có dấu; ↑/↓ chọn, Enter/Tab chèn, Esc đóng.
- Mention được tô sáng khi hiển thị (bình luận và thân bài viết), kèm tooltip tên + vai trò; nội dung HTML của bài chỉ thay trong text node nên không ảnh hưởng markup.
- Người được nhắc tên nhận thông báo 🔔 (loại `mention`) dẫn thẳng tới bài viết / bình luận. Bài nháp không gửi thông báo, và sửa lại bài đã xuất bản không gửi lại thông báo cho người đã được nhắc.

**Ảnh bài viết (mỗi bài 1 ảnh)**

- Form tạo/sửa bài viết có ô tải ảnh lên (`image`) và ô mô tả ảnh (`image_alt`); ảnh hợp lệ là `jpg, jpeg, png, webp, gif`, tối đa 4 MB.
- Ảnh được lưu trên disk `public` trong thư mục `storage/app/public/posts/`, tên file đã bỏ dấu tiếng Việt.
- Đúng một ảnh cho mỗi bài, dùng chung cho **màn danh sách** (thumbnail trong card) và **màn chi tiết** (ảnh bìa + thẻ `og:image` / `twitter:image` khi chia sẻ).
- Sửa bài: tải ảnh mới sẽ thay và xoá ảnh cũ; tick "Xóa ảnh hiện tại" sẽ bỏ ảnh. Xoá bài sẽ xoá luôn file ảnh.
- Để ảnh hiển thị được trên trình duyệt, cần tạo symlink `public/storage` **một lần** sau khi cài đặt:

```bash
php artisan storage:link
```

**Phân tích tương tác (chỉ admin)**

- Đếm số lượt xem, lượt chia sẻ và lượt yêu thích (❤️) cho từng bài viết, lưu trong `posts.*_count` và bảng `post_events`.
- Trang `/admin/analytics`: KPI, biểu đồ xu hướng theo ngày, top bài viết / chủ đề / từ khoá, nguồn chia sẻ, lọc theo 7/30/90/365 ngày hoặc toàn bộ.
- Từ khoá được **tự động tách** từ tiêu đề, tóm tắt và nội dung bài viết (đã loại bỏ từ dừng tiếng Việt), không cần nhập tag thủ công.
- Chatbot trả lời được các câu hỏi dạng "chủ đề nào đang nhiều tương tác?", "từ khoá hot là gì?", "bài X có bao nhiêu lượt xem?" — các tool phân tích này **từ chối người dùng không phải admin**.

**Chạy thử**

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Seeder tạo sẵn bài viết mẫu (mỗi bài một ảnh bìa tự vẽ bằng GD) và dữ liệu tương tác để trang analytics có số liệu ngay, kèm ba tài khoản demo (mật khẩu đều là `password`):

| Vai trò | Email | Quyền |
| --- | --- | --- |
| Admin | `admin@example.com` | Xem `/admin/analytics`, chatbot trả lời câu hỏi phân tích, sửa/xoá mọi bài viết |
| Creator | `creator@example.com` | Tạo & sửa bài viết của mình (có tải ảnh lên) |
| User | `user@example.com` | Đọc, yêu thích ❤️, bình luận, chia sẻ |

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
