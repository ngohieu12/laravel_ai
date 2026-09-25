@extends('layouts.app')

@section('title', 'Bài viết Đã ghim')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center flex-wrap gap-2">
        <div>
            @php
                $currentCat = request('category');
                $currentSort = $sort ?? 'recent';
            @endphp
            <h1 class="text-2xl font-bold text-gray-800">
                @if($currentCat)
                    📌 Bài viết Đã ghim — 📂 {{ ucfirst($currentCat) }}
                @else
                    📌 Bài viết Đã ghim
                @endif
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                @if($currentSort === 'saves')
                    Sắp xếp theo số lượt 📌 lưu chung giảm dần.
                @else
                    Sắp xếp theo thời gian bạn ghim bài.
                @endif
            </p>
        </div>
        <span class="text-sm text-gray-500">{{ $posts->total() }} bài viết</span>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('pins.index') }}" class="bg-white rounded-xl shadow-sm p-4 border space-y-3">
        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-sm text-gray-500">📂 Danh mục:</span>
            @php $baseQ = request()->except(['category', 'page']); @endphp
            <a href="{{ route('pins.index', array_merge($baseQ, ['category' => ''])) }}"
                class="px-3 py-1 rounded-full text-xs font-medium transition {{ !request('category') ? 'bg-slate-700 text-white' : 'bg-white border text-gray-600 hover:bg-slate-50' }}">
                Tất cả
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('pins.index', array_merge($baseQ, ['category' => $cat])) }}"
                    class="px-3 py-1 rounded-full text-xs font-medium transition {{ request('category') === $cat ? 'bg-slate-700 text-white' : 'bg-white border text-gray-600 hover:bg-slate-50' }}">
                    {{ ucfirst($cat) }}
                </a>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-sm text-gray-500">🔀 Sắp xếp:</span>
            <select name="sort" class="border-gray-300 rounded-lg px-3 py-1.5 border text-sm focus:ring-2 focus:ring-slate-400" onchange="this.form.submit()">
                <option value="recent" {{ ($sort ?? 'recent') === 'recent' ? 'selected' : '' }}>🕒 Vừa ghim</option>
                <option value="saves" {{ ($sort ?? '') === 'saves' ? 'selected' : '' }}>📌 Nhiều lượt lưu</option>
            </select>
            @if(request('category'))
                <a href="{{ route('pins.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-2">Xóa lọc</a>
            @endif
        </div>
    </form>

    <!-- Posts List -->
    @if($posts->count() > 0)
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
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-50 text-slate-700" title="Ghim lúc">
                                            📌 {{ $post->pivot->created_at->format('d/m/Y H:i') }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                            ❤️ {{ $post->favorites_count ?? 0 }}
                                        </span>
                                    </div>
                                    <a href="{{ route('posts.show', $post) }}" class="text-xl font-semibold text-gray-800 hover:text-slate-600 transition">
                                        {{ $post->title }}
                                    </a>
                                    <p class="text-gray-600 mt-2 line-clamp-2">{{ $post->summary }}</p>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-sm text-gray-500">
                                        <span>✍️ {{ $post->user?->name ?? 'Admin' }}</span>
                                        <span>📅 {{ $post->created_at->format('d/m/Y H:i') }}</span>
                                        <span title="Lượt xem">👁️ {{ number_format($post->views_count ?? 0) }}</span>
                                        <span title="Lượt lưu">📌 {{ number_format($post->saves_count ?? $post->pinnedBy()->count()) }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2 sm:ml-4">
                                    <form action="{{ route('posts.pin', $post) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-2 text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition" title="Bỏ ghim">
                                            📌
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
            <div class="text-6xl mb-4">📌</div>
            <h3 class="text-lg font-medium text-gray-800 mb-2">
                @if(request('category'))
                    Chưa có bài viết ghim nào trong danh mục "{{ request('category') }}"
                @else
                    Chưa có bài viết nào được ghim
                @endif
            </h3>
            <p class="text-gray-500 mb-4">Ghim những bài viết bạn muốn đọc lại sau!</p>
            <a href="{{ route('posts.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                📝 Xem danh sách bài viết
            </a>
        </div>
    @endif
</div>
@endsection
