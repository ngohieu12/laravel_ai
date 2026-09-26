<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Chuỗi bài viết dài kỳ')</title>
    <meta name="description" content="@yield('description', 'Các chuỗi bài viết dài kỳ được sắp xếp theo thứ tự đọc.')">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .prose { max-width: 800px; }
        .prose p { margin-bottom: 1em; line-height: 1.8; }
        .prose h1, .prose h2, .prose h3, .prose h4 { margin-top: 1.5em; margin-bottom: 0.5em; font-weight: 700; }
        .prose h2 { font-size: 1.5em; }
        .prose h3 { font-size: 1.25em; }
        .prose ul, .prose ol { margin: 1em 0; padding-left: 1.5em; list-style: revert; }
        .prose li { margin-bottom: 0.5em; }
        .prose a { color: #b45309; text-decoration: underline; }
        .prose blockquote { border-left: 4px solid #d6d3d1; padding-left: 1em; margin: 1em 0; color: #78716c; font-style: italic; }
        .prose code { background: #f5f5f4; padding: 0.2em 0.4em; border-radius: 4px; font-size: 0.9em; }
        .prose pre { background: #292524; color: #e7e5e4; padding: 1em; border-radius: 8px; overflow-x: auto; margin: 1em 0; }
        .prose pre code { background: transparent; color: inherit; }
        .prose table { width: 100%; border-collapse: collapse; margin: 1em 0; }
        .prose th, .prose td { border: 1px solid #e7e5e4; padding: 0.5em 0.75em; text-align: left; }
        .fade-in { animation: fadeIn 0.4s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-stone-50 min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur border-b border-stone-200 sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('series.index') }}" class="flex items-center space-x-2">
                    <svg class="w-7 h-7 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="font-bold text-stone-800">Chuỗi bài viết</span>
                </a>

                <div class="flex items-center gap-3">
                    <a href="{{ route('posts.index') }}" class="text-stone-600 hover:text-stone-900 text-sm font-medium transition">
                        Tất cả bài viết
                    </a>
                    @auth
                        <a href="{{ route('dashboard.posts.index') }}" class="bg-stone-800 hover:bg-stone-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            Trang quản trị
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-stone-600 hover:text-stone-900 text-sm font-medium transition">
                            Đăng nhập
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="max-w-5xl mx-auto px-4 py-8 text-center">
            <p class="text-sm text-stone-500">Đọc theo thứ tự, từng phần một — không cần đăng nhập.</p>
            <a href="{{ route('series.index') }}" class="text-sm text-stone-600 hover:text-stone-900 font-medium transition">Xem tất cả chuỗi bài viết</a>
        </div>
    </footer>
</body>
</html>
