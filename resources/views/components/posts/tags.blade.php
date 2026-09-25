{{--
    Tag chips of a post, each linking to the public listing filtered by tag.

    @param  \Illuminate\Support\Collection<int, \App\Models\Tag>  $tags
    @param  string  $size  sm|md
--}}
@props(['tags' => collect(), 'size' => 'sm'])
@if($tags->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1.5']) }}>
        @foreach($tags as $tag)
            <a href="{{ route('posts.index', ['tag' => $tag->slug]) }}"
               class="inline-flex items-center rounded-full font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition {{ $size === 'md' ? 'px-3 py-1 text-sm' : 'px-2 py-0.5 text-xs' }}"
               title="Xem bài viết có tag {{ $tag->name }}">#{{ $tag->name }}</a>
        @endforeach
    </div>
@endif
