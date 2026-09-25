@extends('layouts.dashboard')

@section('title', 'Quản lý bài viết')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap gap-2 items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">🛠️ Quản lý bài viết</h1>
        <div class="flex gap-2">
            <a href="{{ route('posts.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                🌐 Xem trang công khai
            </a>
            <a href="{{ route('dashboard.posts.create') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                ✏️ Tạo bài viết mới
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('dashboard.posts.index') }}" class="bg-white rounded-xl shadow-sm p-4 border flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[220px]">
            <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Tìm kiếm</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}"
                class="w-full border-gray-300 rounded-lg px-3 py-2 border text-sm focus:ring-2 focus:ring-slate-400"
                placeholder="Tiêu đề hoặc tóm tắt...">
        </div>
        <div>
            <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Trạng thái</label>
            <select name="status" id="status" class="border-gray-300 rounded-lg px-3 py-2 border text-sm" onchange="this.form.submit()">
                <option value="">Tất cả</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Đã xuất bản</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Bản nháp</option>
            </select>
        </div>
        <div>
            <label for="category" class="block text-xs font-medium text-gray-500 mb-1">Danh mục</label>
            <select name="category" id="category" class="border-gray-300 rounded-lg px-3 py-2 border text-sm" onchange="this.form.submit()">
                <option value="">Tất cả</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                @endforeach
            </select>
        </div>
        @if(request()->filled('tag'))
            <input type="hidden" name="tag" value="{{ request('tag') }}">
            <div>
                <span class="block text-xs font-medium text-gray-500 mb-1">Tag</span>
                <a href="{{ request()->fullUrlWithQuery(['tag' => null, 'page' => null]) }}"
                   class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm bg-indigo-50 text-indigo-700 hover:bg-indigo-100" title="Bỏ lọc theo tag">
                    #{{ $activeTag?->name ?? request('tag') }} <span aria-hidden="true">✕</span>
                </a>
            </div>
        @endif
        <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg text-sm">Lọc</button>
        @if(request()->hasAny(['search', 'status', 'category', 'tag']))
            <a href="{{ route('dashboard.posts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Xóa lọc</a>
        @endif
    </form>

    @if($posts->count() > 0)
        <div class="space-y-3">
            @foreach($posts as $post)
                <div class="bg-white rounded-xl shadow-sm border p-5 flex flex-wrap gap-4 items-start justify-between">
                    <div class="flex-1 min-w-[260px]">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $post->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $post->is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ ucfirst($post->category) }}</span>
                        </div>
                        <a href="{{ route('posts.show', $post) }}" class="text-lg font-semibold text-gray-800 hover:text-slate-600">{{ $post->title }}</a>
                        <p class="text-gray-500 text-sm line-clamp-1">{{ $post->summary }}</p>
                        @if($post->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach($post->tags as $tag)
                                    <a href="{{ request()->fullUrlWithQuery(['tag' => $tag->slug, 'page' => null]) }}"
                                       class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100">#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        @endif
                        <div class="flex flex-wrap gap-3 text-xs text-gray-500 mt-2">
                            <span>👁️ {{ number_format($post->views_count ?? 0) }}</span>
                            <span>🔗 {{ number_format($post->shares_count ?? 0) }}</span>
                            <span>❤️ {{ number_format($post->favorites_count ?? 0) }}</span>
                            <span>📌 {{ number_format($post->savesCount()) }}</span>
                            <span>💬 {{ number_format($post->comments()->count()) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <a href="{{ route('dashboard.analytics.posts.show', $post) }}" class="text-slate-700 hover:underline">📊 Phân tích</a>
                        <a href="{{ route('dashboard.posts.edit', $post) }}" class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">✏️ Sửa</a>
                        <form action="{{ route('dashboard.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn bài viết này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 border border-red-300 text-red-600 rounded-lg hover:bg-red-50">🗑️ Xóa</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $posts->links() }}</div>
    @else
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
            <div class="text-6xl mb-4">📝</div>
            <h3 class="text-lg font-medium text-gray-800 mb-2">Chưa có bài viết nào</h3>
            <a href="{{ route('dashboard.posts.create') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">✏️ Tạo bài viết mới</a>
        </div>
    @endif
</div>
@endsection
