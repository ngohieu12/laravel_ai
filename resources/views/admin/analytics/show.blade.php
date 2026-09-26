@extends('layouts.dashboard')

@section('title', 'Phân tích: ' . $post->title)

@section('content')


@php
    use App\Services\AnalyticsService;

    $totals = $summary['totals'];
    $periodTotals = $summary['period_totals'];
    $previousTotals = $summary['previous_period_totals'];
    $trendData = $summary['trend']->all();
    $keywords = $summary['keywords'];
    $platforms = $summary['platforms'];
    $recentEvents = $summary['recent_events'];

    $changeBadge = function (string $key) use ($periodTotals, $previousTotals): string {
        if (! $previousTotals) {
            return '<span class="text-xs text-gray-400">không có kỳ trước</span>';
        }

        $now = (float) ($periodTotals[$key] ?? 0);
        $before = (float) ($previousTotals[$key] ?? 0);
        $delta = $now - $before;

        $classes = $delta > 0 ? 'bg-green-100 text-green-700' : ($delta < 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600');
        $arrow = $delta > 0 ? '▲' : ($delta < 0 ? '▼' : '■');
        $sign = $delta > 0 ? '+' : '';
        $percentage = $before > 0 ? round(($delta / $before) * 100, 1) : ($now > 0 ? 100.0 : 0.0);

        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium '.$classes.'">'
            .$arrow.' '.$sign.number_format($percentage, 1, ',', '.').'%</span>';
    };

    $maxPlatformShares = max(1, (int) collect($platforms)->max('shares'));

    $cards = [
        ['label' => 'Lượt xem', 'icon' => '👁️', 'key' => 'views', 'color' => 'from-sky-50 to-white'],
        ['label' => 'Lượt chia sẻ', 'icon' => '🔗', 'key' => 'shares', 'color' => 'from-indigo-50 to-white'],
        ['label' => 'Lượt yêu thích', 'icon' => '❤️', 'key' => 'favorites', 'color' => 'from-rose-50 to-white'],
        ['label' => 'Bình luận', 'icon' => '💬', 'key' => 'comments', 'color' => 'from-amber-50 to-white'],
        ['label' => 'Lưu bài', 'icon' => '📌', 'key' => 'saves', 'color' => 'from-slate-50 to-white'],
    ];
@endphp

<div class="space-y-6">
    <a href="{{ route('admin.analytics.index', ['period' => $period]) }}" class="inline-flex items-center text-gray-600 hover:text-slate-700 transition text-sm">
        ← Quay lại bảng phân tích
    </a>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            @if($post->imageUrl())
                <img src="{{ $post->imageUrl() }}" alt="{{ $post->imageAlt() }}"
                     class="w-40 sm:w-52 aspect-[4/3] object-cover rounded-lg border border-gray-100">
            @endif
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $post->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $post->is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">📂 {{ ucfirst($post->category) }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $post->title }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    ✍️ {{ $post->user?->name ?? 'Không rõ' }} · 📅 {{ $post->created_at->format('d/m/Y H:i') }} ·
                    🧮 {{ number_format($totals['engagement'], 0, ',', '.') }} điểm tương tác
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('posts.show', $post) }}" class="px-3 py-2 rounded-lg text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition">👀 Xem bài viết</a>
                <a href="{{ route('chatbot.index', ['post_id' => $post->id, 'q' => 'Phân tích tương tác của bài viết "'.$post->title.'"']) }}"
                   class="px-3 py-2 rounded-lg text-sm font-medium bg-slate-700 text-white hover:bg-slate-800 transition">🤖 Hỏi chatbot về bài này</a>
            </div>
        </div>
    </div>

    <!-- Period tabs -->
    <div class="bg-white rounded-xl shadow-sm border p-3 flex flex-wrap items-center gap-2">
        <span class="text-sm text-gray-500 mr-1">🗓️ Khoảng thời gian:</span>
        @foreach(AnalyticsService::PERIODS as $key => $days)
            <a href="{{ route('admin.analytics.posts.show', [$post, 'period' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $period === $key ? 'bg-slate-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $days === null ? 'Tất cả' : $days.' ngày' }}
            </a>
        @endforeach
        <span class="ml-auto text-xs text-gray-400">{{ $summary['period']['label'] }}</span>
    </div>

    <!-- KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($cards as $card)
            <div class="bg-gradient-to-br {{ $card['color'] }} rounded-xl shadow-sm border p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600">{{ $card['icon'] }} {{ $card['label'] }}</span>
                    {!! $changeBadge($card['key']) !!}
                </div>
                <div class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($periodTotals[$card['key']] ?? 0) }}</div>
                <div class="text-xs text-gray-500 mt-1">luỹ kế {{ number_format($totals[$card['key']] ?? 0) }}</div>
            </div>
        @endforeach
    </div>

    <!-- Trend -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📈 Xu hướng theo ngày</h2>
        <x-analytics.trend-chart
            :series="$trendData"
            :lines="['views' => '#0284c7', 'shares' => '#6366f1', 'favorites' => '#e11d48', 'comments' => '#d97706']"
            :height="200"
            empty-message="Chưa có tương tác nào cho bài viết này."
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Keywords -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-1">🔑 Từ khoá của bài viết</h2>
            <p class="text-xs text-gray-500 mb-4">Trích tự động từ tiêu đề, tóm tắt, danh mục và nội dung.</p>
            @if($keywords !== [])
                <div class="flex flex-wrap gap-2">
                    @foreach($keywords as $keyword)
                        <a href="{{ route('posts.index', ['search' => $keyword]) }}"
                           class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition text-sm">
                            {{ $keyword }}
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">Không trích được từ khoá nào từ bài viết này.</p>
            @endif

            @if($topKeywords->count() > 0)
                <h3 class="text-sm font-semibold text-gray-700 mt-6 mb-2">🔥 Từ khoá hot toàn blog</h3>
                <ol class="text-sm text-gray-600 space-y-1 list-decimal list-inside">
                    @foreach($topKeywords->take(5) as $hotKeyword)
                        <li>
                            <span class="font-medium text-gray-800">{{ $hotKeyword['keyword'] }}</span>
                            <span class="text-xs text-gray-400">— {{ number_format($hotKeyword['engagement'], 0, ',', '.') }} điểm · {{ $hotKeyword['posts'] }} bài</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <!-- Platforms -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-3">📣 Lượt chia sẻ theo nền tảng</h2>
            <div class="space-y-3">
                @forelse($platforms as $platformRow)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 font-medium">{{ $platformRow['label'] }}</span>
                            <span class="text-xs text-gray-500 tabular-nums">{{ number_format($platformRow['shares']) }}</span>
                        </div>
                        <x-analytics.bar :value="$platformRow['shares']" :max="$maxPlatformShares" color="#6366f1" />
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Chưa có lượt chia sẻ nào trong kỳ này.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent events -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-3">🕒 Nhật ký tương tác gần đây</h2>
        <ul class="divide-y divide-gray-50 text-sm">
            @forelse($recentEvents as $event)
                <li class="py-2 flex items-center justify-between gap-3">
                    <span>
                        <span class="mr-1">{{ $event->icon() }}</span>
                        {{ $event->label() }}
                        @if($event->user)
                            <span class="text-gray-500">— {{ $event->user->name }}</span>
                        @endif
                        @if(($event->meta['platform'] ?? null))
                            <span class="text-xs text-gray-400">({{ \App\Services\AnalyticsService::platformLabel($event->meta['platform']) }})</span>
                        @endif
                        @if(($event->meta['source'] ?? null))
                            <span class="text-xs text-gray-400">nguồn: {{ $event->meta['source'] }}</span>
                        @endif
                    </span>
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $event->created_at?->format('d/m/Y H:i') }}</span>
                </li>
            @empty
                <li class="py-2 text-sm text-gray-500">Chưa có tương tác nào được ghi nhận.</li>
            @endforelse
        </ul>
    </div>
</div>


@endsection
