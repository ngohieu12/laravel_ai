@extends('layouts.dashboard')

@use('App\Models\Category')

@section('title', 'Danh mục Video & MP3')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap gap-2 items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🎬 Danh mục Video &amp; MP3</h1>
            <p class="text-gray-500 text-sm mt-1">
                Hai danh mục mặc định của hệ thống: bài video mặc định nằm trong <strong>Video</strong>,
                bài audio (mp3) nằm trong <strong>MP3</strong>. Danh mục bị xoá nhầm sẽ được tạo lại
                khi mở màn này.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                🏷️ Toàn bộ danh mục
            </a>
            <a href="{{ route('dashboard.posts.create', ['content_type' => 'video']) }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition text-sm font-medium">
                ✏️ Tạo bài video
            </a>
        </div>
    </div>

    <!-- Bucket switcher -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.media-categories.index') }}"
           class="px-4 py-2 rounded-lg text-sm font-medium border transition {{ $selected === null ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
            Tất cả
        </a>
        @foreach(Category::DEFAULTS as $default)
            <a href="{{ route('admin.media-categories.index', ['bucket' => $default]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium border transition {{ $selected === $default ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                {{ $default === Category::VIDEO ? '🎥' : '🎧' }} {{ $default }}
            </a>
        @endforeach
    </div>

    @foreach($buckets as $bucket)
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-5 py-4 border-b flex flex-wrap gap-4 items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        {{ $bucket['name'] === Category::VIDEO ? '🎥' : '🎧' }} {{ $bucket['name'] }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ number_format($bucket['total']) }} bài viết ·
                        {{ number_format($bucket['published']) }} đã xuất bản ·
                        {{ number_format($bucket['matching_type']) }} bài đúng loại nội dung
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <a href="{{ route('posts.index', ['category' => $bucket['name']]) }}" class="text-slate-700 hover:underline">
                        🌐 Xem trang công khai
                    </a>
                    <form action="{{ route('admin.categories.update', $bucket['name']) }}" method="POST" class="flex gap-2 items-center">
                        @csrf
                        @method('PUT')
                        <input type="text" name="new_name" value="{{ old('new_name', $bucket['name']) }}" required maxlength="80"
                            class="border-gray-300 rounded-lg px-3 py-1.5 border text-sm w-40" placeholder="{{ $bucket['name'] }}">
                        <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Đổi tên</button>
                    </form>
                </div>
            </div>

            @if($bucket['posts']->isEmpty())
                <p class="px-5 py-6 text-sm text-gray-500">
                    Chưa có bài viết nào trong danh mục {{ $bucket['name'] }}.
                </p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Bài viết</th>
                            <th class="px-5 py-3">Loại nội dung</th>
                            <th class="px-5 py-3">Tác giả</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3">Cập nhật</th>
                            <th class="px-5 py-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($bucket['posts'] as $post)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('posts.show', $post) }}" class="font-medium text-gray-800 hover:text-slate-600 hover:underline">{{ $post->title }}</a>
                                </td>
                                <td class="px-5 py-3">
                                    @if($post->isVideo())
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">🎥 Video</span>
                                    @elseif($post->isAudio())
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">🎧 Audio</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">📝 Chữ</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $post->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $post->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $post->is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-500">{{ $post->updated_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex gap-2">
                                        <a href="{{ route('dashboard.posts.edit', $post) }}" class="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">✏️ Sửa</a>
                                        @if($post->isAudio() && $post->audio)
                                            <a href="{{ route('dashboard.audio.index') }}" class="px-3 py-1.5 border border-amber-300 text-amber-800 rounded-lg hover:bg-amber-50">🎧 MP3</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($bucket['total'] > count($bucket['posts']))
                    <p class="px-5 py-3 text-xs text-gray-500 border-t">
                        Đang hiện {{ count($bucket['posts']) }}/{{ number_format($bucket['total']) }} bài —
                        <a href="{{ route('posts.index', ['category' => $bucket['name']]) }}" class="text-slate-700 hover:underline">xem tất cả</a>
                    </p>
                @endif
            @endif
        </div>
    @endforeach
</div>
@endsection
