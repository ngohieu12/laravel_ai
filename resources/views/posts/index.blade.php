@extends('layouts.app')

@section('title', 'Danh sách Bài viết')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">📝 Danh sách Bài viết</h1>
        <span class="text-sm text-gray-500">{{ $posts->total() }} bài viết</span>
    </div>

    <!-- Search & Filter -->
    <form method="GET" action="{{ route('posts.index') }}" class="bg-white rounded-xl shadow-sm p-4 border">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm bài viết..."
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
            </div>
            <div>
                <select name="category" class="border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                            {{ ucfirst($category) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                    <option value="">Tất cả trạng thái</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Đã xuất bản</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Bản nháp</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg transition">
                Tìm kiếm
            </button>
            @if(request('search') || request('category') || request('status'))
                <a href="{{ route('posts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">
                    Xóa bộ lọc
                </a>
            @endif
        </div>
    </form>

    <!-- Posts List -->
    @if($posts->count() > 0)
        <div class="space-y-4">
            @foreach($posts as $post)
                <div class="bg-white rounded-xl shadow-sm border hover:shadow-md transition">
                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $post->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $post->is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ ucfirst($post->category) }}
                                    </span>
                                </div>
                                <a href="{{ route('posts.show', $post) }}" class="text-xl font-semibold text-gray-800 hover:text-slate-600 transition">
                                    {{ $post->title }}
                                </a>
                                <p class="text-gray-600 mt-2 line-clamp-2">{{ $post->summary }}</p>
                                <div class="flex items-center space-x-4 mt-3 text-sm text-gray-500">
                                    <span>✍️ {{ $post->author }}</span>
                                    <span>📅 {{ $post->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <a href="{{ route('posts.edit', $post) }}" class="p-2 text-gray-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition" title="Sửa">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Xóa">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
            <div class="text-6xl mb-4">📝</div>
            <h3 class="text-lg font-medium text-gray-800 mb-2">Chưa có bài viết nào</h3>
            <p class="text-gray-500 mb-4">Bắt đầu tạo bài viết đầu tiên của bạn!</p>
            <a href="{{ route('posts.create') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                + Tạo bài viết mới
            </a>
        </div>
    @endif
</div>
@endsection
