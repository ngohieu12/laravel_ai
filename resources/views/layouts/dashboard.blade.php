<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị') — Blog Manager</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
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
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex">
        <!-- Sidebar (backend navigation, role aware) -->
        <aside id="dashboard-sidebar"
               class="fixed lg:sticky top-0 z-40 h-screen w-64 shrink-0 bg-slate-900 text-slate-200 flex flex-col -translate-x-full lg:translate-x-0 transition-transform">
            <div class="px-5 py-5 border-b border-slate-800 flex items-center space-x-2">
                <img src="{{ asset('images/logo.png') }}" alt="" class="w-8 h-8 rounded-lg bg-white/10 p-0.5">
                <div class="leading-tight">
                    <div class="font-bold text-white">Blog Manager</div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-500">Khu vực quản trị</div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5 text-sm">
                <div>
                    <div class="px-2 mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Bài viết</div>
                    <a href="{{ route('dashboard.posts.index') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard.posts.index') ? 'bg-slate-700 text-white' : 'hover:bg-slate-800' }}">
                        📚 Quản lý bài viết
                    </a>
                    <a href="{{ route('dashboard.posts.create') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard.posts.create') ? 'bg-slate-700 text-white' : 'hover:bg-slate-800' }}">
                        ✍️ Viết bài mới
                    </a>
                </div>

                <div>
                    <div class="px-2 mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Phân tích</div>
                    <a href="{{ route('dashboard.analytics.index') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard.analytics.*') ? 'bg-slate-700 text-white' : 'hover:bg-slate-800' }}">
                        📈 Bài viết của tôi
                    </a>
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.analytics.index') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.analytics.*') ? 'bg-slate-700 text-white' : 'hover:bg-slate-800' }}">
                        📊 Toàn hệ thống
                    </a>
                    @endif
                </div>

                @if(auth()->user()->isAdmin())
                <div>
                    <div class="px-2 mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Hệ thống</div>
                    <a href="{{ route('admin.categories.index') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.categories.*') ? 'bg-slate-700 text-white' : 'hover:bg-slate-800' }}">
                        🗂️ Danh mục
                    </a>
                    <a href="{{ route('admin.users.index') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.users.*') ? 'bg-slate-700 text-white' : 'hover:bg-slate-800' }}">
                        👥 Người dùng
                    </a>
                </div>
                @endif
            </nav>

            <div class="px-4 py-4 border-t border-slate-800 space-y-3">
                <div class="flex items-center space-x-2">
                    <div class="w-9 h-9 bg-gradient-to-br {{ auth()->user()->isAdmin() ? 'from-amber-500 to-red-600' : 'from-sky-500 to-indigo-600' }} rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="leading-tight min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                        <div class="text-[11px] text-slate-400">{{ auth()->user()->roleLabel() }}</div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <a href="{{ route('posts.index') }}" class="text-xs text-slate-400 hover:text-white transition">🌐 Xem trang người dùng</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-red-400 hover:text-red-300 transition">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col">
            <!-- Top bar -->
            <header class="bg-white border-b sticky top-0 z-30">
                <div class="px-4 sm:px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <button type="button" onclick="document.getElementById('dashboard-sidebar').classList.toggle('-translate-x-full')"
                                class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <h1 class="text-lg font-bold text-gray-800">@yield('title', 'Quản trị')</h1>
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider {{ auth()->user()->isAdmin() ? 'bg-red-100 text-red-700' : 'bg-sky-100 text-sky-700' }}">
                            {{ auth()->user()->roleLabel() }}
                        </span>
                    </div>
                    <nav class="hidden md:flex items-center space-x-1 text-sm">
                        @yield('actions')
                    </nav>
                </div>
            </header>

            <!-- Flash messages -->
            @if(session('success'))
                <div class="px-4 sm:px-6 pt-4">
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                        <span class="mr-2">✅</span>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="px-4 sm:px-6 pt-4">
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <main class="flex-1 px-4 sm:px-6 py-6 w-full">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
