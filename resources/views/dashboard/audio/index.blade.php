@extends('layouts.dashboard')

@section('title', 'Thư viện MP3')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap gap-2 items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🎧 Thư viện MP3</h1>
            <p class="text-gray-500 text-sm mt-1">
                Toàn bộ file âm thanh đã tải lên, tách riêng khỏi danh sách bài viết.
                <span class="text-gray-400">Trang này hiện {{ number_format($posts->total()) }} file
                    @if($pageBytes !== '0 B') — tổng {{ $pageBytes }} trên trang này @endif.
                </span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dashboard.posts.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                📚 Danh sách bài viết
            </a>
            <a href="{{ route('dashboard.posts.create') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                ✏️ Tạo bài audio
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('dashboard.audio.index') }}" class="bg-white rounded-xl shadow-sm p-4 border flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[220px]">
            <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Tìm kiếm</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}"
                class="w-full border-gray-300 rounded-lg px-3 py-2 border text-sm focus:ring-2 focus:ring-slate-400"
                placeholder="Tiêu đề bài hoặc tóm tắt...">
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
            <label for="sort" class="block text-xs font-medium text-gray-500 mb-1">Sắp xếp</label>
            <select name="sort" id="sort" class="border-gray-300 rounded-lg px-3 py-2 border text-sm" onchange="this.form.submit()">
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Mới nhất</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>A → Z</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg text-sm">Lọc</button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('dashboard.audio.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Xóa lọc</a>
        @endif
    </form>

    @if($posts->count() > 0)
        <div class="space-y-3">
            @foreach($posts as $post)
                <div class="bg-white rounded-xl shadow-sm border p-5 space-y-3">
                    <div class="flex flex-wrap gap-4 items-start justify-between">
                        <div class="flex-1 min-w-[260px]">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $post->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $post->is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">🎧 Audio</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ ucfirst($post->category) }}</span>
                                @if($post->isSeries())
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800" title="{{ $post->series?->title }}">📖 Phần {{ $post->series_part }}</span>
                                @endif
                            </div>
                            <a href="{{ route('posts.show', $post) }}" class="text-lg font-semibold text-gray-800 hover:text-slate-600">{{ $post->title }}</a>
                            <p class="text-gray-500 text-sm line-clamp-1">{{ $post->summary }}</p>
                            <div class="flex flex-wrap gap-3 text-xs text-gray-500 mt-2">
                                <span title="Tác giả">👤 {{ $post->user?->name ?? '—' }}</span>
                                <span title="Dung lượng">💾 {{ $sizes[$post->id] ?? 'Không xác định' }}</span>
                                <span title="Tên file">📄 {{ basename($post->audio) }}</span>
                                <span title="Ngày tải lên">📅 {{ $post->created_at?->format('d/m/Y') }}</span>
                                <span>👁️ {{ number_format($post->views_count ?? 0) }}</span>
                                <span>💬 {{ number_format($post->comments()->count()) }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <a href="{{ route('dashboard.posts.edit', $post) }}" class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">✏️ Sửa</a>
                            <form action="{{ route('dashboard.audio.destroy', $post) }}" method="POST" onsubmit="return confirm('Xoá file MP3 của bài viết này? Bài viết vẫn được giữ lại.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 border border-red-300 text-red-600 rounded-lg hover:bg-red-50">🗑️ Xoá file</button>
                            </form>
                        </div>
                    </div>

                    @if($post->audioUrl())
                        <div class="rounded-lg bg-slate-50 border border-slate-200 p-3">
                            <div class="text-xs font-medium text-gray-500 mb-1">🎵 {{ $post->audioTitle() }}</div>
                            <audio controls preload="none" src="{{ $post->audioUrl() }}" class="w-full"></audio>
                        </div>
                    @else
                        <p class="text-sm text-red-600">⚠️ File âm thanh không còn tồn tại trên máy chủ.</p>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $posts->links() }}</div>
    @else
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
            <div class="text-6xl mb-4">🎧</div>
            <h3 class="text-lg font-medium text-gray-800 mb-2">Thư viện chưa có file MP3 nào</h3>
            <p class="text-gray-500 text-sm mb-4">Tạo một bài viết dạng audio và tải file mp3 lên, file sẽ xuất hiện ở đây.</p>
            <a href="{{ route('dashboard.posts.create', ['content_type' => 'audio']) }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">🎧 Tạo bài audio</a>
        </div>
    @endif
</div>
@endsection
