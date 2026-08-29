@extends('layouts.app')

@section('title', $post->title)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back button -->
    <a href="{{ route('posts.index') }}" class="inline-flex items-center text-gray-600 hover:text-slate-600 transition">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Quay lại danh sách
    </a>

    <!-- Article -->
    <article class="bg-white rounded-xl shadow-sm border overflow-hidden">
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
                        {{ strtoupper(substr($post->author, 0, 1)) }}
                    </span>
                    {{ $post->author }}
                </span>
                <span>📅 {{ $post->created_at->format('d/m/Y H:i') }}</span>
                @if($post->updated_at != $post->created_at)
                    <span>🔄 Cập nhật: {{ $post->updated_at->format('d/m/Y H:i') }}</span>
                @endif
            </div>

            <!-- Summary -->
            <div class="bg-slate-50 border-l-4 border-slate-400 p-4 rounded-r-lg mb-6">
                <p class="text-slate-700 font-medium">{{ $post->summary }}</p>
            </div>

            <!-- Content -->
            <div class="prose text-gray-700">
                {!! nl2br(e($post->content)) !!}
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-gray-50 px-8 py-4 border-t flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Slug: <code class="bg-gray-200 px-2 py-1 rounded">{{ $post->slug }}</code>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('posts.edit', $post) }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                    ✏️ Chỉnh sửa
                </a>
                <form action="{{ route('posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">
                        🗑️ Xóa
                    </button>
                </form>
            </div>
        </div>
    </article>

    <!-- Related: Ask Chatbot -->
    <div class="bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl border p-6 text-center">
        <p class="text-gray-700 mb-3">💬 Muốn tìm hiểu thêm về bài viết này?</p>
        <a href="{{ route('chatbot.index', ['q' => 'Cho tôi biết về bài viết: ' . $post->title]) }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
            🤖 Hỏi Chatbot
        </a>
    </div>
</div>
@endsection
