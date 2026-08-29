<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý Bài viết')</title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('posts.index') }}" class="flex items-center space-x-2">
                        <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span class="text-xl font-bold text-gray-800">Blog Manager</span>
                    </a>
                    <a href="{{ route('posts.index') }}" class="text-gray-600 hover:text-slate-600 transition {{ request()->routeIs('posts.*') ? 'text-slate-600 font-medium' : '' }}">
                        📝 Bài viết
                    </a>
                    <a href="{{ route('chatbot.index') }}" class="text-gray-600 hover:text-slate-600 transition {{ request()->routeIs('chatbot.*') ? 'text-slate-600 font-medium' : '' }}">
                        🤖 Chatbot
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="{{ route('posts.create') }}" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        + Bài viết mới
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
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
    <main class="max-w-6xl mx-auto px-4 py-8">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
