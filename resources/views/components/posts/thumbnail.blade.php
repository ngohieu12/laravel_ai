{{--
    Post cover thumbnail used on listing screens.

    @param  \App\Models\Post  $post
    @param  string  $size  sm|md|lg
--}}
@php
    $thumbUrl = $post->imageUrl();
    $thumbAlt = $post->imageAlt();
    $thumbSizes = [
        'sm' => 'w-16 h-16 text-lg',
        'md' => 'w-40 sm:w-48 aspect-[4/3] text-4xl',
        'lg' => 'w-full aspect-[16/9] text-6xl',
    ];
    $thumbClass = $thumbSizes[$size ?? 'md'] ?? $thumbSizes['md'];
@endphp

@if($thumbUrl)
    <a href="{{ route('posts.show', $post) }}" class="block shrink-0 overflow-hidden rounded-lg border border-gray-100 bg-gray-100" tabindex="-1">
        <img src="{{ $thumbUrl }}" alt="{{ $thumbAlt }}" loading="lazy"
             class="{{ $thumbClass }} object-cover transition duration-300 hover:scale-105">
    </a>
@else
    <a href="{{ route('posts.show', $post) }}" tabindex="-1"
       class="{{ $thumbClass }} shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-400 select-none"
       title="Bài viết chưa có ảnh đại diện">
        📄
    </a>
@endif
