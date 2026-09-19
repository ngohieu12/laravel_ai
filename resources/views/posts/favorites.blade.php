@extends('layouts.app')

@section('title', 'Bài viết Yêu thích')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">⭐ Bài viết Yêu thích</h1>
        <span class="text-sm text-gray-500">{{ $posts->total() }} bài viết</span>
    </div>

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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700" title="Yêu thích lúc">
                                        ⭐ {{ $post->pivot->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <a href="{{ route('posts.show', $post) }}" class="text-xl font-semibold text-gray-800 hover:text-slate-600 transition">
                                    {{ $post->title }}
                                </a>
                                <p class="text-gray-600 mt-2 line-clamp-2">{{ $post->summary }}</p>
                                <div class="flex items-center space-x-4 mt-3 text-sm text-gray-500">
                                    <span>✍️ {{ $post->user?->name ?? 'Admin' }}</span>
                                    <span>📅 {{ $post->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <form action="{{ route('posts.favorite', $post) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition" title="Bỏ yêu thích">
                                        ❤️
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
            <div class="text-6xl mb-4">⭐</div>
            <h3 class="text-lg font-medium text-gray-800 mb-2">Chưa có bài viết yêu thích nào</h3>
            <p class="text-gray-500 mb-4">Hãy bắt đầu đánh dấu yêu thích cho các bài viết bạn quan tâm!</p>
            <a href="{{ route('posts.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                📝 Xem danh sách bài viết
            </a>
        </div>
    @endif
</div>
@endsection
