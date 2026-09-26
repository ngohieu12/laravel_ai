@extends('layouts.app')

@section('title', 'Chuỗi bài viết dài kỳ')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-800">📚 Chuỗi bài viết dài kỳ</h1>
        <p class="text-gray-500 mt-1">
            Gom nhiều bài viết về một chủ đề thành một chuỗi có thứ tự đọc. Mỗi chuỗi có ID riêng —
            đổi tên không làm tách chuỗi.
        </p>
    </div>

    <!-- Create -->
    <form action="{{ route('dashboard.series.store') }}" method="POST" class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
        @csrf
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Tạo chuỗi mới</h2>

        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1.5">Tên chuỗi <span class="text-red-500">*</span></label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required maxlength="150"
                placeholder="VD: Học Laravel từ đầu"
                class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
            @error('title')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Mô tả</label>
            <textarea name="description" id="description" rows="2" maxlength="1000"
                class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400">{{ old('description') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Hiển thị trên trang chuỗi bài viết công khai.</p>
            @error('description')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-slate-600 hover:bg-slate-700 text-white px-5 py-2 rounded-lg font-medium transition">
                Tạo chuỗi
            </button>
        </div>
    </form>

    <!-- List -->
    <div class="space-y-4">
        @forelse($series as $item)
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <div class="flex flex-wrap justify-between items-start gap-3">
                    <div class="min-w-[220px] flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-semibold text-gray-800">{{ $item->title }}</h3>
                            <span class="text-xs text-gray-400 font-mono">#{{ $item->id }}</span>
                            @if($item->published_parts_count > 0)
                                <span class="text-xs font-medium text-green-800 bg-green-100 rounded-full px-2 py-0.5">
                                    {{ $item->published_parts_count }} phần đã xuất bản
                                </span>
                            @else
                                <span class="text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full px-2 py-0.5">
                                    Chưa có phần công khai
                                </span>
                            @endif
                            @if($item->long_form_parts_count > 0)
                                <span class="text-xs font-medium text-amber-800 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5">
                                    🕰 {{ $item->long_form_parts_count }} bài dài kỳ
                                </span>
                            @endif
                        </div>
                        @if($item->description)
                            <p class="text-sm text-gray-500 mt-1">{{ $item->description }}</p>
                        @endif
                        <a href="{{ route('series.show', $item) }}"
                            class="inline-block mt-2 text-sm text-slate-600 hover:text-slate-800 transition">
                            Xem trang công khai →
                        </a>
                    </div>

                    <div class="flex items-center gap-2">
                        <form action="{{ route('dashboard.series.update', $item) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="title" value="{{ $item->title }}" required maxlength="150"
                                class="w-44 border-gray-300 rounded-lg px-3 py-1.5 border text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                            <button type="submit" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm transition">
                                Đổi tên
                            </button>
                        </form>

                        <form action="{{ route('dashboard.series.destroy', $item) }}" method="POST"
                            onsubmit="return confirm('Xoá chuỗi &quot;{{ $item->title }}&quot;? Các bài viết sẽ được giữ nguyên, chỉ mất liên kết với chuỗi.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 text-sm text-red-500 hover:bg-red-50 rounded-lg transition">
                                Xoá
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                <div class="text-5xl mb-4">📖</div>
                <p class="text-gray-500">Chưa có chuỗi bài viết nào. Tạo chuỗi đầu tiên ở trên.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
