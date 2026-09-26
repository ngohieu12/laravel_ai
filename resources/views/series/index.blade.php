@extends('layouts.reader')

@section('title', 'Chuỗi bài viết dài kỳ')
@section('description', 'Các chuỗi bài viết dài kỳ được sắp xếp theo thứ tự đọc, từ nền tảng đến chuyên sâu.')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12">
    <!-- Hero -->
    <div class="fade-in text-center max-w-2xl mx-auto">
        <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-3 py-1">
            📚 Đọc nhiều kỳ
        </span>
        <h1 class="mt-4 text-3xl sm:text-4xl font-bold text-stone-900 tracking-tight">
            Chuỗi bài viết dài kỳ
        </h1>
        <p class="mt-3 text-stone-600 leading-relaxed">
            Mỗi chuỗi gom một chủ đề thành những phần bài có thứ tự. Đọc từ phần đầu đến phần cuối để có cái nhìn đầy đủ nhất —
            hoàn toàn miễn phí và không cần đăng nhập.
        </p>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('series.index') }}" class="mt-8 max-w-xl mx-auto flex gap-3">
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm chuỗi bài viết..."
            class="flex-1 bg-white border border-stone-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-stone-400 focus:border-stone-400 outline-none">
        <button type="submit" class="bg-stone-800 hover:bg-stone-900 text-white px-5 py-2.5 rounded-lg font-medium transition">
            Tìm
        </button>
        @if($search !== '')
            <a href="{{ route('series.index') }}" class="bg-stone-200 hover:bg-stone-300 text-stone-700 px-4 py-2.5 rounded-lg transition">
                Xóa
            </a>
        @endif
    </form>

    <!-- Series grid -->
    @if($series->count() > 0)
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($series as $item)
                <div class="fade-in flex flex-col bg-white border border-stone-200 rounded-2xl p-6 hover:border-stone-300 hover:shadow-lg hover:-translate-y-0.5 transition">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center text-xs font-semibold text-stone-500 bg-stone-100 rounded-full px-2.5 py-0.5">
                            {{ $item['parts_count'] }} phần
                        </span>
                        @if($item['long_form_count'] > 0)
                            <span class="inline-flex items-center text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-full px-2.5 py-0.5"
                                title="Số phần được đánh dấu là bài dài kỳ">
                                🕰 {{ $item['long_form_count'] }} bài dài
                            </span>
                        @endif
                    </div>

                    <h2 class="mt-4 text-lg font-bold text-stone-900 leading-snug">
                        <a href="{{ route('series.show', $item['id']) }}" class="hover:text-amber-800 transition">
                            {{ $item['title'] }}
                        </a>
                    </h2>

                    <p class="mt-2 text-sm text-stone-600 leading-relaxed flex-1 line-clamp-3">
                        {{ $item['description'] }}
                    </p>

                    <div class="mt-4 pt-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
                        <span>⏱ {{ $item['minutes'] }} phút đọc</span>
                        <a href="{{ route('series.show', $item['id']) }}" class="font-semibold text-stone-800 hover:text-amber-800 transition">
                            Bắt đầu đọc →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="mt-10 bg-white border border-stone-200 rounded-2xl p-12 text-center">
            <div class="text-5xl mb-4">📖</div>
            <h3 class="text-lg font-semibold text-stone-800">
                {{ $search !== '' ? 'Không tìm thấy chuỗi bài viết nào' : 'Chưa có chuỗi bài viết nào' }}
            </h3>
            <p class="text-stone-500 mt-2">
                {{ $search !== '' ? 'Thử tìm với từ khóa khác.' : 'Các chuỗi bài viết sẽ xuất hiện ở đây khi có phần đã xuất bản.' }}
            </p>
        </div>
    @endif
</div>
@endsection
