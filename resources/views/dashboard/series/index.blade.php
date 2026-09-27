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
    <form action="{{ route('dashboard.series.store') }}" method="POST" enctype="multipart/form-data"
        class="bg-white rounded-xl shadow-sm border p-6 space-y-4" data-image-preview-scope>
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

        <div>
            <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Ảnh đại diện</label>
            <div class="flex items-start gap-4">
                <div class="w-32 sm:w-40 shrink-0">
                    <div class="aspect-[4/3] rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <span data-image-preview-empty class="text-gray-400 text-xs px-2 text-center">Chưa chọn ảnh<br>(JPG, PNG, WEBP, GIF — tối đa 4MB)</span>
                        <img data-image-preview-img src="" alt="Xem trước ảnh đại diện" class="hidden w-full h-full object-cover">
                    </div>
                </div>
                <div class="flex-1 space-y-2">
                    <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp,image/gif" data-image-preview
                        class="block w-full text-sm text-gray-600 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer border border-gray-300 rounded-lg px-3 py-2">
                    <input type="text" name="image_alt" value="{{ old('image_alt') }}" maxlength="255"
                        class="w-full border-gray-300 rounded-lg px-4 py-2 border text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                        placeholder="Mô tả ảnh (alt) — tốt cho SEO và trình đọc màn hình">
                    @error('image')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('image_alt')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500">Ảnh riêng của chuỗi, không lấy từ bài viết nào.</p>
                </div>
            </div>
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
            <div class="bg-white rounded-xl shadow-sm border p-5" data-image-preview-scope>
                <div class="flex flex-wrap justify-between items-start gap-3">
                    <!-- Cover -->
                    <div class="w-28 sm:w-36 shrink-0">
                        <div class="aspect-[4/3] rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden">
                            @if($item->image)
                                <img data-image-preview-img src="{{ $item->imageUrl() }}" alt="{{ $item->imageAlt() }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-gray-400 text-xs px-2 text-center">Chuỗi chưa có ảnh đại diện</span>
                            @endif
                        </div>
                    </div>

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

                <!-- Edit -->
                <form action="{{ route('dashboard.series.update', $item) }}" method="POST" enctype="multipart/form-data"
                    class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="title-{{ $item->id }}" class="block text-sm font-medium text-gray-700 mb-1.5">Tên chuỗi</label>
                            <input type="text" name="title" id="title-{{ $item->id }}" value="{{ $item->title }}" required maxlength="150"
                                class="w-full border-gray-300 rounded-lg px-3 py-1.5 border text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                            @error('title')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="description-{{ $item->id }}" class="block text-sm font-medium text-gray-700 mb-1.5">Mô tả</label>
                            <textarea name="description" id="description-{{ $item->id }}" rows="2" maxlength="1000"
                                class="w-full border-gray-300 rounded-lg px-3 py-1.5 border text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400">{{ $item->description }}</textarea>
                            @error('description')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex-1 min-w-[220px] space-y-2">
                            <input type="file" name="image" id="image-{{ $item->id }}" accept="image/jpeg,image/png,image/webp,image/gif" data-image-preview
                                class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer border border-gray-300 rounded-lg px-3 py-1.5">
                            <input type="text" name="image_alt" value="{{ $item->image_alt }}" maxlength="255"
                                class="w-full border-gray-300 rounded-lg px-3 py-1.5 border text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                                placeholder="Mô tả ảnh (alt)">
                            @if($item->image)
                                <label class="flex items-center text-sm text-gray-600">
                                    <input type="checkbox" name="remove_image" value="1" class="mr-2 rounded border-gray-300">
                                    Xóa ảnh đại diện hiện tại
                                </label>
                            @endif
                            @error('image')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('image_alt')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="px-4 py-1.5 bg-slate-600 hover:bg-slate-700 text-white rounded-lg text-sm transition">
                            Lưu
                        </button>
                    </div>
                </form>
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

@push('scripts')
    @include('components.series.image-preview-script')
@endpush
