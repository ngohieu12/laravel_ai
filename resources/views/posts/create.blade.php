@extends('layouts.app')

@section('title', 'Tạo bài viết mới')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back button -->
    <a href="{{ route('posts.index') }}" class="inline-flex items-center text-gray-600 hover:text-blue-600 transition">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Quay lại danh sách
    </a>

    <div class="bg-white rounded-xl shadow-sm border p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">📝 Tạo bài viết mới</h1>

        <form action="{{ route('posts.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                    placeholder="Nhập tiêu đề bài viết...">
            </div>

            <!-- Summary -->
            <div>
                <label for="summary" class="block text-sm font-medium text-gray-700 mb-1">Tóm tắt <span class="text-red-500">*</span></label>
                <textarea id="summary" name="summary" rows="3" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Tóm tắt ngắn gọn nội dung bài viết...">{{ old('summary') }}</textarea>
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Nội dung <span class="text-red-500">*</span></label>
                <textarea id="content" name="content" rows="15" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                    placeholder="Viết nội dung bài viết của bạn ở đây...">{{ old('content') }}</textarea>
            </div>

            <!-- Category & Author -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                    <input type="text" id="category" name="category" value="{{ old('category', 'general') }}" required
                        class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="VD: cong-nghe, doi-song, hoc-tap...">
                </div>
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-1">Tác giả <span class="text-red-500">*</span></label>
                    <input type="text" id="author" name="author" value="{{ old('author', 'Admin') }}" required
                        class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Tên tác giả">
                </div>
            </div>

            <!-- Published -->
            <div class="flex items-center">
                <input type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="is_published" class="ml-2 text-sm text-gray-700">Xuất bản ngay</label>
            </div>

            <!-- Submit -->
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="{{ route('posts.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Hủy
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    💾 Lưu bài viết
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
