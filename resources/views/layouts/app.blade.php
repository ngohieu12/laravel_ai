<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Blog Manager')</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/color/96/blog.png">
    @stack('meta')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .prose { max-width: 800px; }
        .prose p { margin-bottom: 1em; line-height: 1.75; }
        .prose h1, .prose h2, .prose h3 { margin-top: 1.5em; margin-bottom: 0.5em; }
        .prose ul, .prose ol { margin: 1em 0; padding-left: 1.5em; }
        .prose li { margin-bottom: 0.5em; }
        .prose blockquote { border-left: 4px solid #64748b; padding-left: 1em; margin: 1em 0; color: #6b7280; font-style: italic; }
        .prose code { background: #f3f4f6; padding: 0.2em 0.4em; border-radius: 4px; font-size: 0.9em; }
        .prose pre { background: #1f2937; color: #e5e7eb; padding: 1em; border-radius: 8px; overflow-x: auto; margin: 1em 0; }
        .prose pre code { background: transparent; color: inherit; }
        .chat-bubble { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .typing-dots span { animation: blink 1.4s infinite both; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes blink { 0%, 60%, 100% { opacity: 0; } 30% { opacity: 1; } }
        html { scroll-behavior: smooth; }
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex: 1 0 auto; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-40 backdrop-blur-sm bg-white/95">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <!-- Brand + Desktop nav -->
                <div class="flex items-center">
                    <a href="{{ route('posts.index') }}" class="flex items-center space-x-2 group">
                        <img src="https://img.icons8.com/color/96/blog.png"
                             alt="Blog Manager Logo"
                             class="w-9 h-9 rounded-lg group-hover:scale-105 transition-transform"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <span class="w-9 h-9 rounded-lg bg-slate-700 text-white items-center justify-center font-bold hidden" style="display:none">📝</span>
                        <div class="flex flex-col leading-tight">
                            <span class="text-xl font-bold text-gray-800 group-hover:text-slate-700 transition">Blog Manager</span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider hidden sm:block">Nền tảng chia sẻ kiến thức</span>
                        </div>
                    </a>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center space-x-1 ml-8">
                        <a href="{{ route('posts.index') }}"
                           class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('posts.index', 'posts.show', 'posts.create', 'posts.edit') ? 'bg-slate-100 text-slate-700' : 'text-gray-600 hover:bg-gray-100 hover:text-slate-700' }}">
                            📝 Bài viết
                        </a>
                        @auth
                        <a href="{{ route('favorites.index') }}"
                           class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('favorites.*') ? 'bg-slate-100 text-slate-700' : 'text-gray-600 hover:bg-gray-100 hover:text-slate-700' }}">
                            ⭐ Yêu thích
                        </a>
                        <a href="{{ route('chatbot.index') }}"
                           class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('chatbot.*') ? 'bg-slate-100 text-slate-700' : 'text-gray-600 hover:bg-gray-100 hover:text-slate-700' }}">
                            🤖 Chatbot AI
                        </a>
                        @endauth
                    </nav>
                </div>

                <!-- Desktop Right side -->
                <div class="hidden md:flex items-center space-x-3">
                    @auth
                        @if(auth()->user()->hasRole('admin', 'creator'))
                        <a href="{{ route('posts.create') }}" class="inline-flex items-center bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Bài viết mới
                        </a>
                        @endif
                        <div class="flex items-center space-x-3 pl-3 border-l">
                            <div class="text-right hidden sm:block">
                                <div class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-gray-400">{{ ucfirst(auth()->user()->role) }}</div>
                            </div>
                            <div class="w-9 h-9 bg-gradient-to-br from-slate-400 to-slate-600 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-red-500 transition text-sm p-2 rounded-lg hover:bg-red-50" title="Đăng xuất">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-slate-700 transition text-sm font-medium px-3 py-2">
                            Đăng nhập
                        </a>
                        <a href="{{ route('register') }}" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                            Đăng ký miễn phí
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
                            class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden pb-4 space-y-1 border-t pt-3">
                <a href="{{ route('posts.index') }}"
                   class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->routeIs('posts.*') ? 'bg-slate-100 text-slate-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    📝 Bài viết
                </a>
                @auth
                <a href="{{ route('favorites.index') }}"
                   class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->routeIs('favorites.*') ? 'bg-slate-100 text-slate-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    ⭐ Yêu thích
                </a>
                <a href="{{ route('chatbot.index') }}"
                   class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->routeIs('chatbot.*') ? 'bg-slate-100 text-slate-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    🤖 Chatbot AI
                </a>
                @if(auth()->user()->hasRole('admin', 'creator'))
                <a href="{{ route('posts.create') }}"
                   class="block px-3 py-2 rounded-lg text-base font-medium bg-slate-700 text-white text-center mt-2">
                    ➕ Bài viết mới
                </a>
                @endif
                <div class="flex items-center justify-between px-3 py-2 mt-2 border-t pt-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-slate-400 to-slate-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-400">{{ ucfirst(auth()->user()->role) }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-500 text-sm font-medium px-2 py-1">Đăng xuất</button>
                    </form>
                </div>
                @else
                <a href="{{ route('login') }}"
                   class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-gray-100 text-center">
                    Đăng nhập
                </a>
                <a href="{{ route('register') }}"
                   class="block px-3 py-2 rounded-lg text-base font-medium bg-slate-700 text-white text-center">
                    Đăng ký miễn phí
                </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                <span class="mr-2">✅</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-8 w-full">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-gray-300 mt-auto">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand column -->
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <img src="https://img.icons8.com/color/96/blog.png" alt="Logo" class="w-9 h-9 rounded-lg bg-white/10 p-1">
                        <span class="text-xl font-bold text-white">Blog Manager</span>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed mb-4 max-w-md">
                        Nền tảng blog hiện đại với sự hỗ trợ của AI — nơi chia sẻ kiến thức, bình luận,
                        yêu thích các bài viết hay và trò chuyện cùng trợ lý thông minh để học tập hiệu quả hơn.
                    </p>
                    <div class="flex space-x-3">
                        <a href="https://www.facebook.com" target="_blank" rel="noopener noreferrer"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-[#1877F2] flex items-center justify-center transition" title="Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="https://twitter.com" target="_blank" rel="noopener noreferrer"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-black flex items-center justify-center transition" title="X (Twitter)">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="https://www.linkedin.com" target="_blank" rel="noopener noreferrer"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-[#0A66C2] flex items-center justify-center transition" title="LinkedIn">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="https://github.com" target="_blank" rel="noopener noreferrer"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center transition" title="GitHub">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                        </a>
                        <a href="mailto:hello@blogmanager.local"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center transition" title="Email">
                            ✉️
                        </a>
                    </div>
                </div>

                <!-- Links -->
                <div>
                    <h3 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Khám phá</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('posts.index') }}" class="text-gray-400 hover:text-white transition">📝 Tất cả bài viết</a></li>
                        @auth
                        <li><a href="{{ route('favorites.index') }}" class="text-gray-400 hover:text-white transition">⭐ Bài yêu thích</a></li>
                        <li><a href="{{ route('chatbot.index') }}" class="text-gray-400 hover:text-white transition">🤖 Trợ lý AI</a></li>
                        @if(auth()->user()->hasRole('admin', 'creator'))
                        <li><a href="{{ route('posts.create') }}" class="text-gray-400 hover:text-white transition">➕ Viết bài mới</a></li>
                        @endif
                        @else
                        <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition">🔐 Đăng nhập</a></li>
                        <li><a href="{{ route('register') }}" class="text-gray-400 hover:text-white transition">✨ Đăng ký</a></li>
                        @endauth
                    </ul>
                </div>

                <!-- Categories/Stats -->
                <div>
                    <h3 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Liên hệ</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li class="flex items-start space-x-2">
                            <span>📍</span>
                            <span>Thuận Thành, Bắc Ninh, Việt Nam</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span>✉️</span>
                            <span>hello@blogmanager.local</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span>🌐</span>
                            <span>blogmanager.local</span>
                        </li>
                    </ul>
                    <div class="mt-4 p-3 bg-slate-800 rounded-lg border border-slate-700">
                        <p class="text-xs text-gray-400 mb-2">Được hỗ trợ bởi</p>
                        <div class="flex items-center space-x-2 text-white text-sm font-medium">
                            🤖 Laravel AI
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="border-t border-slate-800 mt-10 pt-6 flex flex-col md:flex-row justify-between items-center space-y-3 md:space-y-0">
                <p class="text-sm text-gray-500">
                    © {{ date('Y') }} Blog Manager. Được xây dựng với ❤️ bằng Laravel.
                </p>
                <div class="flex items-center space-x-4 text-xs text-gray-500">
                    <a href="#" class="hover:text-gray-300 transition">Điều khoản sử dụng</a>
                    <a href="#" class="hover:text-gray-300 transition">Chính sách bảo mật</a>
                    <a href="#" class="hover:text-gray-300 transition">RSS</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
