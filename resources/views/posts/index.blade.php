@extends('layouts.app')

@section('title', 'Danh sách Bài viết')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center flex-wrap gap-2">
        <div>
            @php
                $isSortFav = ($sort ?? 'newest') === 'favorites';
                $currentCat = request('category');
            @endphp
            <h1 class="text-2xl font-bold text-gray-800">
                @if($isSortFav && $currentCat)
                    ⭐ Bài viết yêu thích nhất — 📂 {{ ucfirst($currentCat) }}
                @elseif(($sort ?? '') === 'views')
                    👁️ Bài viết xem nhiều nhất
                @elseif(($sort ?? '') === 'shares')
                    🔗 Bài viết chia sẻ nhiều nhất
                @elseif(($sort ?? '') === 'saves')
                    📌 Bài viết được lưu nhiều nhất
                @elseif($isSortFav)
                    ⭐ Bài viết được yêu thích nhiều nhất
                @elseif($currentCat)
                    📂 Danh mục: {{ ucfirst($currentCat) }}
                @elseif(request()->filled('tag'))
                    🔖 Tag: #{{ $activeTag?->name ?? request('tag') }}
                @else
                    📝 Danh sách Bài viết
                @endif
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                @if($isSortFav)
                    Sắp xếp theo số lượt ❤️ yêu thích giảm dần.
                @elseif(($sort ?? 'newest') === 'oldest') Cũ nhất trước.
                @else Mới nhất trước.
                @endif
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-sm text-gray-500">{{ $posts->total() }} bài viết</span>
            <!-- Grid / list view toggle -->
            <div class="inline-flex rounded-lg border bg-white overflow-hidden shadow-sm" role="group" aria-label="Chế độ xem">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                   class="px-3 py-1.5 text-sm font-medium transition {{ ($view ?? 'list') === 'list' ? 'bg-slate-700 text-white' : 'text-gray-600 hover:bg-slate-50' }}"
                   title="Xem dạng danh sách">
                    ☰ Danh sách
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                   class="px-3 py-1.5 text-sm font-medium transition {{ ($view ?? 'list') === 'grid' ? 'bg-slate-700 text-white' : 'text-gray-600 hover:bg-slate-50' }}"
                   title="Xem dạng lưới">
                    ▦ Lưới
                </a>
            </div>
        </div>
    </div>

    <!-- Quick category chips -->
    <div class="flex flex-wrap gap-2 items-center">
        <span class="text-sm text-gray-500">📂 Lọc nhanh theo danh mục:</span>
        @php
            $activeCategory = request('category');
            $baseQuery = request()->except(['category', 'page']);
        @endphp
        <a href="{{ route('posts.index', array_merge($baseQuery, ['category' => ''])) }}"
            class="px-3 py-1 rounded-full text-xs font-medium transition {{ $activeCategory === null || $activeCategory === '' ? 'bg-slate-700 text-white' : 'bg-white border text-gray-600 hover:bg-slate-50' }}">
            Tất cả
        </a>
        @foreach($categories as $cat)
            @if($cat)
            <a href="{{ route('posts.index', array_merge($baseQuery, ['category' => $cat])) }}"
                class="px-3 py-1 rounded-full text-xs font-medium transition {{ $activeCategory === $cat ? 'bg-slate-700 text-white' : 'bg-white border text-gray-600 hover:bg-slate-50' }}">
                {{ ucfirst($cat) }}
            </a>
            @endif
        @endforeach
    </div>

    <!-- Quick tag chips -->
    @if($popularTags->isNotEmpty() || request()->filled('tag'))
        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-sm text-gray-500">🔖 Tag phổ biến:</span>
            @php
                $activeTagSlug = request('tag');
                $tagBaseQuery = request()->except(['tag', 'page']);
            @endphp
            @if($activeTagSlug)
                <a href="{{ route('posts.index', $tagBaseQuery) }}"
                    class="px-3 py-1 rounded-full text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700 transition" title="Bỏ lọc theo tag">
                    #{{ $activeTag?->name ?? $activeTagSlug }} ✕
                </a>
            @endif
            @foreach($popularTags as $popularTag)
                @continue($popularTag->slug === $activeTagSlug)
                <a href="{{ route('posts.index', array_merge($tagBaseQuery, ['tag' => $popularTag->slug])) }}"
                    class="px-3 py-1 rounded-full text-xs font-medium bg-white border border-indigo-100 text-indigo-700 hover:bg-indigo-50 transition">
                    #{{ $popularTag->name }} <span class="text-indigo-400">{{ $popularTag->posts_count }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <!-- Search & Filter (khách cũng tìm kiếm được) -->
    <form method="GET" action="{{ route('posts.index') }}" class="bg-white rounded-xl shadow-sm p-4 border">
        @if(($view ?? 'list') === 'grid')
            <input type="hidden" name="view" value="grid">
        @endif
        @if(request()->filled('tag'))
            <input type="hidden" name="tag" value="{{ request('tag') }}">
        @endif
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm bài viết..."
                    class="w-full border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
            </div>
            <div>
                <select name="category" class="border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                            {{ ucfirst($category) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="sort" class="border-gray-300 rounded-lg px-4 py-2 border focus:ring-2 focus:ring-slate-400">
                    <option value="newest" {{ ($sort ?? 'newest') === 'newest' ? 'selected' : '' }}>🕒 Mới nhất</option>
                    <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>🕒 Cũ nhất</option>
                    <option value="favorites" {{ ($sort ?? '') === 'favorites' ? 'selected' : '' }}>❤️ Yêu thích nhiều</option>
                    <option value="views" {{ ($sort ?? '') === 'views' ? 'selected' : '' }}>👁️ Xem nhiều</option>
                    <option value="shares" {{ ($sort ?? '') === 'shares' ? 'selected' : '' }}>🔗 Chia sẻ nhiều</option>
                    <option value="saves" {{ ($sort ?? '') === 'saves' ? 'selected' : '' }}>📌 Lưu nhiều</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg transition">
                Tìm kiếm
            </button>
            @if(request('search') || request('category') || request('sort') || request('tag'))
                <a href="{{ route('posts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">
                    Xóa bộ lọc
                </a>
            @endif
        </div>
    </form>

    @if($posts->count() > 0)
        @if(($view ?? 'list') === 'grid')
            <!-- GRID VIEW — chỉ xem / tìm kiếm, không có sửa–xóa -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($posts as $post)
                    <article class="bg-white rounded-xl shadow-sm border hover:shadow-md transition flex flex-col overflow-hidden">
                        <x-posts.thumbnail :post="$post" size="lg" />
                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ ucfirst($post->category) }}
                                </span>
                                @if($post->isVideo())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">🎥 Video</span>
                                @endif
                                @if($post->isSeries())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800" title="Chuỗi: {{ $post->series_title }}">📖 Phần {{ $post->series_part }}</span>
                                @endif
                            </div>
                            <a href="{{ route('posts.show', $post) }}" class="text-lg font-semibold text-gray-800 hover:text-slate-600 transition line-clamp-2">
                                {{ $post->title }}
                            </a>
                            <p class="text-gray-600 mt-2 text-sm line-clamp-3">{{ $post->summary }}</p>
                            <x-posts.tags :tags="$post->tags" class="mt-3" />
                            <div class="mt-auto pt-4 flex items-center justify-between text-xs text-gray-500">
                                <span>✍️ {{ $post->user?->name ?? 'Admin' }} · 📅 {{ $post->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="mt-3 pt-3 border-t flex items-center justify-between text-xs text-gray-500">
                                <div class="flex items-center space-x-3">
                                    <span title="Lượt xem">👁️ {{ number_format($post->views_count ?? 0) }}</span>
                                    <span title="Lượt yêu thích">❤️ {{ number_format($post->favorites_count ?? 0) }}</span>
                                    <span title="Lượt lưu">📌 {{ number_format($post->saves_count ?? 0) }}</span>
                                </div>
                                @auth
                                    <form action="{{ route('posts.pin', $post) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="transition {{ in_array($post->id, $pinnedIds ?? []) ? 'text-slate-700 hover:text-slate-900' : 'text-gray-400 hover:text-slate-700' }}"
                                            title="{{ in_array($post->id, $pinnedIds ?? []) ? 'Bỏ ghim' : 'Ghim bài' }}">
                                            {{ in_array($post->id, $pinnedIds ?? []) ? '📌' : '📍' }}
                                        </button>
                                    </form>
                                @endauth
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <!-- LIST VIEW — chỉ xem / tìm kiếm, không có sửa–xóa -->
            <div class="space-y-4">
                @foreach($posts as $post)
                    <div class="bg-white rounded-xl shadow-sm border hover:shadow-md transition">
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row gap-5">
                                <x-posts.thumbnail :post="$post" />

                                <div class="flex flex-1 flex-col sm:flex-row justify-between items-start gap-3">
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                {{ ucfirst($post->category) }}
                                            </span>
                                            @if($post->isVideo())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">🎥 Video</span>
                                            @endif
                                            @if($post->isSeries())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800" title="Chuỗi: {{ $post->series_title }}">📖 Phần {{ $post->series_part }}</span>
                                            @endif
                                        </div>
                                        <a href="{{ route('posts.show', $post) }}" class="text-xl font-semibold text-gray-800 hover:text-slate-600 transition">
                                            {{ $post->title }}
                                        </a>
                                        <p class="text-gray-600 mt-2 line-clamp-2">{{ $post->summary }}</p>
                                        <x-posts.tags :tags="$post->tags" class="mt-2" />
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-sm text-gray-500">
                                            <span>✍️ {{ $post->user?->name ?? 'Admin' }}</span>
                                            <span>📅 {{ $post->created_at->format('d/m/Y H:i') }}</span>
                                            <span title="Lượt yêu thích">❤️ {{ number_format($post->favorites_count ?? 0) }} lượt yêu thích</span>
                                            <span title="Lượt xem">👁️ {{ number_format($post->views_count ?? 0) }}</span>
                                            <span title="Lượt chia sẻ">🔗 {{ number_format($post->shares_count ?? 0) }}</span>
                                            <span title="Lượt lưu bài">📌 {{ number_format($post->saves_count ?? 0) }}</span>
                                        </div>
                                    </div>

                                    @auth
                                        <div class="flex items-center space-x-2 sm:ml-4">
                                            <form action="{{ route('posts.favorite', $post) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="p-2 rounded-lg transition {{ in_array($post->id, $favoritedIds ?? []) ? 'text-red-500 hover:text-red-700 bg-red-50' : 'text-gray-400 hover:text-red-500 hover:bg-red-50' }}"
                                                    title="{{ in_array($post->id, $favoritedIds ?? []) ? 'Bỏ yêu thích' : 'Yêu thích' }}">
                                                    {{ in_array($post->id, $favoritedIds ?? []) ? '❤️' : '🤍' }}
                                                    <span class="text-xs ml-0.5">{{ $post->favorites_count ?? 0 }}</span>
                                                </button>
                                            </form>
                                            <form action="{{ route('posts.pin', $post) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="p-2 rounded-lg transition {{ in_array($post->id, $pinnedIds ?? []) ? 'text-slate-700 hover:text-slate-900 bg-slate-100' : 'text-gray-400 hover:text-slate-700 hover:bg-slate-50' }}"
                                                    title="{{ in_array($post->id, $pinnedIds ?? []) ? 'Bỏ ghim' : 'Ghim bài' }}">
                                                    {{ in_array($post->id, $pinnedIds ?? []) ? '📌' : '📍' }}
                                                    <span class="text-xs ml-0.5">{{ $post->saves_count ?? 0 }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Pagination -->
        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
            <div class="text-6xl mb-4">📝</div>
            <h3 class="text-lg font-medium text-gray-800 mb-2">
                {{ request('search') ? 'Không tìm thấy bài viết phù hợp' : 'Chưa có bài viết nào' }}
            </h3>
            <p class="text-gray-500 mb-4">
                @if(request('search'))
                    Hãy thử từ khoá khác hoặc xóa bộ lọc tìm kiếm.
                @else
                    Các bài viết mới sẽ sớm xuất hiện tại đây.
                @endif
            </p>
            @auth
                @if(auth()->user()->hasRole('admin', 'creator'))
                    <a href="{{ route('dashboard.posts.create') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                        + Tạo bài viết mới
                    </a>
                @endif
            @endauth
        </div>
    @endif
</div>
@endsection
