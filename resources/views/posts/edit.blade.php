@extends('layouts.app')

@section('title', 'Chỉnh sửa: ' . $post->title)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back button -->
    <a href="{{ route('posts.show', $post) }}" class="inline-flex items-center text-gray-600 hover:text-slate-600 transition">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Quay lại bài viết
    </a>

    <div class="bg-white rounded-xl shadow-sm border p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">✏️ Chỉnh sửa bài viết</h1>

        <form action="{{ route('posts.update', $post) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400 text-lg"
                    placeholder="Nhập tiêu đề bài viết...">
            </div>

            <!-- Summary -->
            <div>
                <label for="summary" class="block text-sm font-medium text-gray-700 mb-1">Tóm tắt <span class="text-red-500">*</span></label>
                <textarea id="summary" name="summary" rows="3" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="Tóm tắt ngắn gọn nội dung bài viết...">{{ old('summary', $post->summary) }}</textarea>
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Nội dung <span class="text-red-500">*</span></label>
                <textarea id="content" name="content" rows="15" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400 font-mono text-sm"
                    placeholder="Viết nội dung bài viết của bạn ở đây...">{{ old('content', $post->content) }}</textarea>
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                <input type="text" id="category" name="category" value="{{ old('category', $post->category) }}" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="VD: cong-nghe, doi-song, hoc-tap...">
            </div>

            <!-- Published -->
            <div class="flex items-center">
                <input type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $post->is_published) ? 'checked' : '' }}
                    class="w-4 h-4 text-slate-600 border-gray-300 rounded focus:ring-slate-400">
                <label for="is_published" class="ml-2 text-sm text-gray-700">Xuất bản ngay</label>
            </div>

            <!-- Submit -->
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="{{ route('posts.show', $post) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Hủy
                </a>
                <button type="submit" class="px-6 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition font-medium">
                    💾 Cập nhật bài viết
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
