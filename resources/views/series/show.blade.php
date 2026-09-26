@extends('layouts.reader')

@section('title', $seriesTitle)
@section('description', 'Đọc chuỗi bài viết "'.$seriesTitle.'" theo thứ tự từ phần đầu đến phần cuối.')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-10">
    <!-- Back -->
    <a href="{{ route('series.index') }}" class="inline-flex items-center text-stone-600 hover:text-stone-900 text-sm font-medium transition fade-in">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Tất cả chuỗi bài viết
    </a>

    <!-- Header -->
    <header class="mt-5 fade-in">
        @if($longFormCount > 0)
            <span class="text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-full px-2.5 py-0.5">
                Có bài dài kỳ
            </span>
        @endif
        <h1 class="mt-3 text-3xl sm:text-4xl font-bold text-stone-900 tracking-tight leading-tight">
            {{ $seriesTitle }}
        </h1>

        <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-stone-500">
            <span>📖 {{ $parts->count() }} phần</span>
            <span>⏱ Khoảng {{ $totalMinutes }} phút đọc</span>
            @if($longFormCount > 0)
                <span>🕰 {{ $longFormCount }} bài dài kỳ</span>
            @endif
        </div>
    </header>

    <div class="mt-10 grid gap-10 lg:grid-cols-[260px_1fr] lg:items-start">
        <!-- Table of contents -->
        <aside class="fade-in lg:sticky lg:top-24 bg-white border border-stone-200 rounded-2xl p-5">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-stone-500">Mục lục</h2>
            <ol class="mt-3 space-y-2.5">
                @foreach($parts as $part)
                    <li>
                        <a href="#phan-{{ $part->series_part }}" class="group flex gap-2.5 text-sm">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-stone-100 text-stone-600 group-hover:bg-stone-800 group-hover:text-white text-xs font-semibold flex items-center justify-center transition">
                                {{ $part->series_part }}
                            </span>
                            <span class="text-stone-700 group-hover:text-stone-900 leading-snug transition">
                                {{ $part->title }}
                                <span class="block text-xs text-stone-400">{{ $part->readingMinutes() }} phút</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </aside>

        <!-- Parts -->
        <div class="space-y-10">
            @foreach($parts as $part)
                <article id="phan-{{ $part->series_part }}" class="fade-in scroll-mt-24 bg-white border border-stone-200 rounded-2xl p-6 sm:p-8">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="w-9 h-9 rounded-full bg-stone-800 text-white text-sm font-bold flex items-center justify-center">
                            {{ $part->series_part }}
                        </span>
                        @if($part->is_long_form)
                            <span class="inline-flex items-center text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-full px-2.5 py-0.5"
                                title="Phần này được đánh dấu thủ công là bài dài kỳ">
                                🕰 Bài dài kỳ
                            </span>
                        @endif
                        @if($part->tags->isNotEmpty())
                            <span class="text-xs text-stone-500">
                                🏷 {{ $part->tags->pluck('name')->implode(', ') }}
                            </span>
                        @endif
                    </div>

                    <h2 class="mt-4 text-2xl font-bold text-stone-900 leading-snug">
                        {{ $part->title }}
                    </h2>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-stone-500">
                        <span>✍️ {{ $part->user?->name ?? 'Admin' }}</span>
                        <span>📅 {{ $part->created_at->format('d/m/Y') }}</span>
                        <span>⏱ {{ $part->readingMinutes() }} phút đọc</span>
                    </div>

                    @if($part->summary)
                        <div class="mt-5 bg-stone-50 border-l-4 border-amber-500 rounded-r-lg p-4">
                            <p class="text-stone-700 font-medium leading-relaxed">{{ $part->summary }}</p>
                        </div>
                    @endif

                    <div class="prose mt-6 text-stone-700">
                        {!! \App\Support\HtmlSanitizer::sanitize($part->content) !!}
                    </div>

                    <div class="mt-6 pt-4 border-t border-stone-100">
                        <a href="{{ route('posts.show', $part) }}"
                            class="inline-flex items-center text-sm font-medium text-stone-700 hover:text-amber-800 transition">
                            Xem trang bài viết & bình luận →
                        </a>
                    </div>
                </article>
            @endforeach

            <div class="text-center pt-2">
                <a href="{{ route('series.index') }}" class="inline-flex items-center px-5 py-2.5 bg-stone-800 hover:bg-stone-900 text-white font-medium rounded-lg transition">
                    Xem chuỗi bài viết khác
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
