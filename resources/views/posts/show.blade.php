@extends('layouts.app')

@section('title', $post->title)

@push('meta')
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:description" content="{{ $post->summary }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ route('posts.show', $post) }}">
    <meta name="description" content="{{ $post->summary }}">
    <meta name="twitter:card" content="{{ $post->imageUrl() ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $post->title }}">
    <meta name="twitter:description" content="{{ $post->summary }}">
    @if($post->imageUrl())
        <meta property="og:image" content="{{ $post->imageUrl() }}">
        <meta property="og:image:alt" content="{{ $post->imageAlt() }}">
        <meta name="twitter:image" content="{{ $post->imageUrl() }}">
    @endif
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back button -->
    <a href="{{ route('posts.index') }}" class="inline-flex items-center text-gray-600 hover:text-slate-600 transition">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Quay lại danh sách
    </a>

    <!-- Article -->
    <article class="bg-white rounded-xl shadow-sm border overflow-hidden">
        @if($post->imageUrl())
            <div class="w-full bg-slate-100">
                <img src="{{ $post->imageUrl() }}" alt="{{ $post->imageAlt() }}"
                     class="w-full max-h-[440px] object-cover">
            </div>
        @endif

        <div class="p-8">
            <!-- Meta -->
            <div class="flex items-center space-x-2 mb-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $post->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $post->is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-slate-100 text-slate-700">
                    {{ ucfirst($post->category) }}
                </span>
            </div>

            <!-- Title -->
            <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $post->title }}</h1>

            <!-- Author & Date -->
            <div class="flex items-center space-x-4 text-sm text-gray-500 mb-6 pb-6 border-b">
                <span class="flex items-center">
                    <span class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-slate-600 font-medium text-sm mr-2">
                        {{ strtoupper(substr($post->user?->name ?? 'A', 0, 1)) }}
                    </span>
                    {{ $post->user?->name ?? 'Admin' }}
                </span>
                <span>📅 {{ $post->created_at->format('d/m/Y H:i') }}</span>
                @if($post->updated_at != $post->created_at)
                    <span>🔄 Cập nhật: {{ $post->updated_at->format('d/m/Y H:i') }}</span>
                @endif
                <span title="Số lượt xem">👁️ {{ number_format($post->views_count) }}</span>
                <span title="Số lượt chia sẻ">🔗 <span data-shares-count>{{ number_format($post->shares_count) }}</span></span>
                <span title="Số bình luận">💬 {{ number_format($commentsCount) }}</span>
            </div>

            <!-- Summary -->
            <div class="bg-slate-50 border-l-4 border-slate-400 p-4 rounded-r-lg mb-6">
                <p class="text-slate-700 font-medium">{{ $post->summary }}</p>
            </div>

            <!-- Content -->
            <div class="prose text-gray-700">
                {!! \App\Support\HtmlSanitizer::sanitize($post->content) !!}
            </div>
        </div>

        @php
            $shareUrl = urlencode(route('posts.show', $post));
            $shareTitle = urlencode($post->title);
            $canEdit = auth()->check() && (auth()->user()->isAdmin() || (auth()->user()->isCreator() && $post->user_id === auth()->id()));
            $canDelete = auth()->check() && auth()->user()->isAdmin();
        @endphp

        <!-- Social Share bar -->
        <div class="bg-gradient-to-r from-slate-50 to-white px-8 py-3 border-b flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500 mr-1">🔗 Chia sẻ:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-[#1877F2] text-white hover:bg-[#166FE5] transition" data-share-platform="facebook" title="Chia sẻ lên Facebook">
                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-black text-white hover:bg-gray-800 transition" data-share-platform="x" title="Chia sẻ lên X (Twitter)">
                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                X
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-[#0A66C2] text-white hover:bg-[#004182] transition" data-share-platform="linkedin" title="Chia sẻ lên LinkedIn">
                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                LinkedIn
            </a>
            <a href="https://t.me/share/url?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-[#229ED9] text-white hover:bg-[#1a88bc] transition" data-share-platform="telegram" title="Chia sẻ lên Telegram">
                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                Telegram
            </a>
            <a href="mailto:?subject={{ $shareTitle }}&body={{ $shareUrl }}"
               class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-600 text-white hover:bg-gray-700 transition" title="Chia sẻ qua Email">
                ✉️ Email
            </a>
            <button type="button" data-share-platform="copy" onclick="navigator.clipboard.writeText('{{ route('posts.show', $post) }}').then(()=>{this.textContent='✅ Đã copy';setTimeout(()=>{this.innerHTML='🔗 Copy link'},2000)})"
               class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 transition" title="Sao chép liên kết">
                🔗 Copy link
            </button>
        </div>

        <!-- Actions -->
        <div class="bg-gray-50 px-8 py-4 border-t flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center space-x-4 text-sm text-gray-500 flex-wrap">
                @auth
                <form action="{{ route('posts.favorite', $post) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-lg transition text-sm font-medium {{ $isFavorited ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                        @if($isFavorited)
                            ❤️ Đã yêu thích
                        @else
                            🤍 Yêu thích
                        @endif
                        <span class="ml-2 px-1.5 py-0.5 rounded text-xs {{ $isFavorited ? 'bg-red-200 text-red-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $post->favorites_count ?? 0 }}
                        </span>
                    </button>
                </form>
                @else
                <a href="{{ route('login') }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg transition text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-100"
                    title="Đăng nhập để yêu thích">
                    🤍 Yêu thích
                    <span class="ml-2 px-1.5 py-0.5 rounded text-xs bg-gray-200 text-gray-700">
                        {{ $post->favorites_count ?? 0 }}
                    </span>
                </a>
                @endauth
                <span class="hidden sm:inline">Slug: <code class="bg-gray-200 px-2 py-1 rounded">{{ $post->slug }}</code></span>
            </div>
            @auth
            <div class="flex space-x-3">
                @if($canEdit)
                <a href="{{ route('posts.edit', $post) }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                    ✏️ Chỉnh sửa
                </a>
                @endif
                @if($canDelete)
                <form action="{{ route('posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">
                        🗑️ Xóa
                    </button>
                </form>
                @endif
            </div>
            @else
            <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                🔐 Đăng nhập để tương tác
            </a>
            @endauth
        </div>
    </article>

    @auth
    <!-- Related: Ask Chatbot -->
    <div class="bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl border p-6 text-center">
        <p class="text-gray-700 mb-3">💬 Muốn tìm hiểu thêm về bài viết này?</p>
        <div class="flex flex-wrap items-center justify-center gap-2">
            <a href="{{ route('chatbot.index', ['post_id' => $post->id, 'q' => 'Giải thích chi tiết bài viết: ' . $post->title]) }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                🤖 Hỏi Chatbot
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('chatbot.index', ['post_id' => $post->id, 'q' => 'Bài viết "' . $post->title . '" có bao nhiêu lượt xem, lượt chia sẻ và lượt yêu thích?']) }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-medium">
                📊 Hỏi AI về tương tác bài này
            </a>
            <a href="{{ route('admin.analytics.posts.show', $post) }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-medium">
                📈 Mở phân tích chi tiết
            </a>
            @endif
        </div>
    </div>
    @else
    <div class="bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl border p-6 text-center">
        <p class="text-gray-700 mb-3">🔐 <a href="{{ route('login') }}" class="text-slate-700 underline hover:text-slate-900">Đăng nhập</a> để bình luận, yêu thích và hỏi AI về bài viết này.</p>
    </div>
    @endauth

    <!-- Comments section -->
    <section class="bg-white rounded-xl shadow-sm border p-6 sm:p-8 space-y-6">
        <h2 class="text-xl font-bold text-gray-800 flex items-center space-x-2">
            <span>💬 Bình luận</span>
            <span class="text-sm font-normal text-gray-500">({{ $commentsCount }})</span>
        </h2>

        @auth
        <!-- New comment form (root level) -->
        <form action="{{ route('posts.comments.store', $post) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <textarea name="content" rows="3" required maxlength="2000"
                    placeholder="Viết bình luận của bạn..."
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"></textarea>
                @error('content')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
                @error('parent_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-5 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                    📝 Đăng bình luận
                </button>
            </div>
        </form>
        @else
        <div class="bg-slate-50 rounded-lg border p-4 text-center text-sm text-gray-600">
            💬 Vui lòng <a href="{{ route('login') }}" class="text-slate-700 underline font-medium hover:text-slate-900">đăng nhập</a> để đăng bình luận hoặc thích bình luận. Bạn vẫn có thể xem các bình luận bên dưới.
        </div>
        @endauth

        <!-- Comments list -->
        <div class="divide-y divide-gray-100">
            @if($comments->count() > 0)
                @foreach($comments as $comment)
                    <x-posts.partials.comment :comment="$comment" :post="$post" />
                @endforeach
            @else
                <div class="text-center py-8 text-gray-500">
                    <div class="text-4xl mb-2">💭</div>
                    <p class="text-sm">Chưa có bình luận nào{!! auth()->check() ? '. Hãy là người đầu tiên bình luận!' : '.' !!}</p>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    // Record share clicks before the browser opens the social network.
    (function () {
        const shareEndpoint = @json(route('posts.shares.track', $post));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        document.querySelectorAll('[data-share-platform]').forEach(function (element) {
            element.addEventListener('click', function () {
                if (!csrfToken) {
                    return;
                }

                fetch(shareEndpoint, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ platform: element.getAttribute('data-share-platform') }),
                })
                    .then(function (response) {
                        return response.ok ? response.json() : null;
                    })
                    .then(function (payload) {
                        if (payload && typeof payload.shares_count === 'number') {
                            document.querySelectorAll('[data-shares-count]').forEach(function (node) {
                                node.textContent = payload.shares_count.toLocaleString('vi-VN');
                            });
                        }
                    })
                    .catch(function () {
                        /* tracking must never break sharing */
                    });
            });
        });
    })();

    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-reply-toggle]');
        const cancel = e.target.closest('[data-reply-cancel]');
        if (toggle) {
            const id = toggle.getAttribute('data-reply-toggle');
            const form = document.getElementById('reply-form-' + id);
            if (form) {
                form.classList.toggle('hidden');
                if (!form.classList.contains('hidden')) {
                    const ta = form.querySelector('textarea');
                    if (ta) ta.focus();
                }
            }
        }
        if (cancel) {
            const id = cancel.getAttribute('data-reply-cancel');
            const form = document.getElementById('reply-form-' + id);
            if (form) form.classList.add('hidden');
        }
    });
</script>
@endpush
