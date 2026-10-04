@php use App\Models\Post; @endphp

@extends('layouts.dashboard')

@section('title', 'Tạo bài viết mới')

@section('content')
<div class="max-w-4xl space-y-6">
    <a href="{{ route('dashboard.posts.index') }}" class="inline-flex items-center text-gray-600 hover:text-slate-600 transition">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Quay lại quản lý bài viết
    </a>

    <div class="bg-white rounded-xl shadow-sm border p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">📝 Tạo bài viết mới</h1>

        <form action="{{ route('dashboard.posts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400 text-lg"
                    placeholder="Nhập tiêu đề bài viết...">
            </div>

            <div>
                <label for="summary" class="block text-sm font-medium text-gray-700 mb-1">Tóm tắt <span class="text-red-500">*</span></label>
                <textarea id="summary" name="summary" rows="3" required
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="Tóm tắt ngắn gọn nội dung bài viết...">{{ old('summary') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại nội dung</label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="content_type" value="text" {{ old('content_type', request('content_type', Post::CONTENT_TYPE_TEXT)) === Post::CONTENT_TYPE_TEXT ? 'checked' : '' }} class="w-4 h-4 text-slate-600 border-gray-300 focus:ring-slate-400">
                        <span class="ml-2 text-sm text-gray-700">📝 Bài viết chữ</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="content_type" value="video" {{ old('content_type', request('content_type')) === Post::CONTENT_TYPE_VIDEO ? 'checked' : '' }} class="w-4 h-4 text-slate-600 border-gray-300 focus:ring-slate-400">
                        <span class="ml-2 text-sm text-gray-700">🎥 Bài viết video</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="content_type" value="audio" {{ old('content_type', request('content_type')) === Post::CONTENT_TYPE_AUDIO ? 'checked' : '' }} class="w-4 h-4 text-slate-600 border-gray-300 focus:ring-slate-400">
                        <span class="ml-2 text-sm text-gray-700">🎧 Bài viết audio</span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Bài video: dán link YouTube hoặc Vimeo. Bài audio: tải lên tệp MP3.
                    Cả hai loại chỉ cần phần nội dung làm mô tả (không bắt buộc).
                </p>
            </div>

            <div id="video-url-wrap" class="hidden">
                <label for="video_url" class="block text-sm font-medium text-gray-700 mb-1">Đường dẫn video <span class="text-red-500">*</span></label>
                <input type="url" id="video_url" name="video_url" value="{{ old('video_url') }}"
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="https://www.youtube.com/watch?v=...">
                @error('video_url')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="audio-wrap" class="hidden space-y-3">
                <div>
                    <label for="audio" class="block text-sm font-medium text-gray-700 mb-1">Tệp âm thanh <span class="text-red-500">*</span></label>
                    <input type="file" id="audio" name="audio" accept=".mp3,.m4a,.wav,.ogg,audio/mpeg,audio/mp4,audio/wav,audio/ogg"
                        class="w-full border-gray-300 rounded-lg px-4 py-2 border text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                    @error('audio')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">MP3, M4A, WAV hoặc OGG — tối đa 20MB.</p>
                </div>
                <div>
                    <label for="audio_title" class="block text-sm font-medium text-gray-700 mb-1">Tên đoạn âm thanh</label>
                    <input type="text" id="audio_title" name="audio_title" value="{{ old('audio_title') }}" maxlength="255"
                        class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                        placeholder="VD: Tập 1 — Mở đầu">
                    @error('audio_title')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Bỏ trống thì hiển thị tiêu đề bài viết.</p>
                </div>
            </div>

            <div data-mention-root>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                    <span id="content-label-text">Nội dung</span> <span id="content-required-mark" class="text-red-500">*</span>
                </label>
                <x-posts.editor name="content" :value="old('content', '')"
                    placeholder="Viết nội dung bài viết của bạn ở đây... Gõ @ để nhắc tên" />
                @error('content')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Ảnh đại diện</label>
                <div class="flex items-start gap-4">
                    <div class="w-40 sm:w-48 shrink-0">
                        <div id="image-preview" class="aspect-[4/3] rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden">
                            <span id="image-preview-empty" class="text-gray-400 text-xs px-2 text-center">Chưa chọn ảnh<br>(JPG, PNG, WEBP, GIF — tối đa 4MB)</span>
                            <img id="image-preview-img" src="" alt="Xem trước ảnh đại diện" class="hidden w-full h-full object-cover">
                        </div>
                    </div>
                    <div class="flex-1 space-y-2">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" data-image-preview
                            class="block w-full text-sm text-gray-600 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer border border-gray-300 rounded-lg px-3 py-2">
                        <input type="text" id="image_alt" name="image_alt" value="{{ old('image_alt') }}" maxlength="255"
                            class="w-full border-gray-300 rounded-lg px-4 py-2 border text-sm focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                            placeholder="Mô tả ảnh (alt) — tốt cho SEO và trình đọc màn hình">
                        @error('image')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('image_alt')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500">Ảnh này được dùng chung cho màn danh sách và màn chi tiết bài viết.</p>
                    </div>
                </div>
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                <input type="text" id="category" name="category" value="{{ old('category', $defaultCategory ?? 'general') }}" required list="category-list"
                    class="w-full border-gray-300 rounded-lg px-4 py-3 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                    placeholder="VD: cong-nghe, doi-song, hoc-tap...">
                <datalist id="category-list">
                    @foreach($categories as $catName)
                        <option value="{{ $catName }}">
                    @endforeach
                </datalist>
                <p class="text-xs text-gray-500 mt-1">Chọn danh mục có sẵn hoặc nhập danh mục mới — quản trị viên có thể sắp xếp tại mục Danh mục.</p>
            </div>

            <x-posts.tag-input :value="old('tags', '')" :suggestions="$tagSuggestions" />

            <!-- Bài viết dài kỳ (chuỗi) -->
            <div class="border border-indigo-100 bg-indigo-50/50 rounded-xl p-5 space-y-3">
                <div>
                    <label for="series_id" class="block text-sm font-medium text-gray-700 mb-1">📖 Chuỗi bài viết dài kỳ <span class="text-xs font-normal text-gray-400">(tùy chọn)</span></label>
                    <select id="series_id" name="series_id"
                        class="w-full border-gray-300 rounded-lg px-4 py-2.5 border focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        <option value="">— Không thuộc chuỗi nào —</option>
                        @foreach($series as $item)
                            <option value="{{ $item->id }}" @selected((int) old('series_id') === $item->id)>
                                {{ $item->title }} ({{ $item->posts_count }} phần)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Chọn chuỗi có sẵn để gom bài này vào đúng bộ theo ID. Chưa có chuỗi?
                        <a href="{{ route('dashboard.series.index') }}" class="text-indigo-600 underline">Tạo chuỗi mới</a>.
                    </p>
                    @error('series_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="max-w-xs">
                    <label for="series_part" class="block text-sm font-medium text-gray-700 mb-1">Số phần <span class="text-red-500">*</span></label>
                    <input type="number" id="series_part" name="series_part" value="{{ old('series_part') }}" min="1" step="1"
                        class="w-full border-gray-300 rounded-lg px-4 py-2.5 border focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"
                        placeholder="VD: 1">
                    <p class="text-xs text-gray-500 mt-1">Mỗi bài một số phần, không trùng nhau trong cùng chuỗi.</p>
                    @error('series_part')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <label class="flex items-start gap-2.5 pt-1">
                    <input type="checkbox" id="is_long_form" name="is_long_form" value="1" @checked(old('is_long_form'))
                        class="w-4 h-4 mt-0.5 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    <span class="text-sm text-gray-700">🕰 Đánh dấu là <strong>bài dài kỳ</strong></span>
                    <span class="block text-xs text-gray-500">Cờ thủ công: phần này được ghim nổi bật trong trang chuỗi bài viết.</span>
                </label>
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}
                    class="w-4 h-4 text-slate-600 border-gray-300 rounded focus:ring-slate-400">
                <label for="is_published" class="ml-2 text-sm text-gray-700">Xuất bản ngay</label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="{{ route('dashboard.posts.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Hủy
                </a>
                <button type="submit" class="px-6 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition font-medium">
                    💾 Lưu bài viết
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
    @include('components.posts.image-preview-script')
    <x-posts.mention-input />
    <script>
        // Ẩn / hiện ô link video và ô upload audio theo loại nội dung;
        // bài video / audio không bắt buộc nội dung chữ.
        (function () {
            const radios = document.querySelectorAll('input[name="content_type"]');
            const videoWrap = document.getElementById('video-url-wrap');
            const audioWrap = document.getElementById('audio-wrap');
            const audioInput = document.getElementById('audio');
            const contentMark = document.getElementById('content-required-mark');
            const contentLabelText = document.getElementById('content-label-text');
            const labels = { video: 'Mô tả video (không bắt buộc)', audio: 'Mô tả audio (không bắt buộc)' };
            // Bài video / audio mặc định đi kèm danh mục Video / MP3, nhưng chỉ
            // ghi đè khi tác giả chưa tự nhập danh mục khác.
            const categoryInput = document.getElementById('category');
            const defaultCategories = { text: 'general', video: 'Video', audio: 'MP3' };
            let categoryTouched = false;

            if (categoryInput) {
                categoryInput.addEventListener('input', function () { categoryTouched = true; });
            }

            function sync() {
                const checked = document.querySelector('input[name="content_type"]:checked');
                const type = checked ? checked.value : 'text';
                const isText = type === 'text';

                if (videoWrap) videoWrap.classList.toggle('hidden', type !== 'video');
                if (audioWrap) audioWrap.classList.toggle('hidden', type !== 'audio');
                if (audioInput) audioInput.required = type === 'audio';
                // Ô nội dung (bắt buộc / chiều cao) do component posts.editor tự lo.
                if (contentMark) contentMark.classList.toggle('hidden', !isText);
                if (contentLabelText) {
                    contentLabelText.textContent = isText ? 'Nội dung' : (labels[type] || 'Nội dung');
                }
                if (categoryInput && !categoryTouched && defaultCategories[type]) {
                    categoryInput.value = defaultCategories[type];
                }
            }

            radios.forEach(function (radio) { radio.addEventListener('change', sync); });
            sync();
        })();
    </script>
@endpush
