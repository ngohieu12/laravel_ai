@extends('layouts.app')

@section('title', 'Phân tích tương tác — Admin')

@php
    use App\Services\AnalyticsService;

    /**
     * Render a small +/- badge comparing this period with the previous one.
     *
     * @param  array{absolute: float, percentage: float}|null  $change
     */
    $renderChange = function (?array $change): string {
        if (! $change) {
            return '<span class="text-xs text-gray-400">chưa có kỳ trước để so sánh</span>';
        }

        $absolute = (float) $change['absolute'];
        $percentage = (float) $change['percentage'];

        $classes = $absolute > 0
            ? 'bg-green-100 text-green-700'
            : ($absolute < 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600');
        $arrow = $absolute > 0 ? '▲' : ($absolute < 0 ? '▼' : '■');
        $sign = $absolute > 0 ? '+' : '';

        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium '.$classes.'">'
            .$arrow.' '.$sign.number_format($percentage, 1, ',', '.').'%</span>';
    };

    $trendData = $trend->all();
    $maxCategoryEngagement = max(1.0, (float) $topCategories->max('engagement'));
    $maxKeywordEngagement = max(1.0, (float) $topKeywords->max('engagement'));
    $maxPlatformShares = max(1, (int) $platforms->max('shares'));
    $maxSourceViews = max(1, (int) $sources->max('views'));
    $periodTotals = $overview['period_totals'];
    $totals = $overview['totals'];
    $changes = $overview['changes'];

    $kpiCards = [
        ['label' => 'Lượt xem', 'icon' => '👁️', 'period' => $periodTotals['views'], 'total' => $totals['views'], 'change' => $changes['views'] ?? null, 'color' => 'from-sky-50 to-white'],
        ['label' => 'Lượt chia sẻ', 'icon' => '🔗', 'period' => $periodTotals['shares'], 'total' => $totals['shares'], 'change' => $changes['shares'] ?? null, 'color' => 'from-indigo-50 to-white'],
        ['label' => 'Lượt yêu thích', 'icon' => '❤️', 'period' => $periodTotals['favorites'], 'total' => $totals['favorites'], 'change' => $changes['favorites'] ?? null, 'color' => 'from-rose-50 to-white'],
        ['label' => 'Bình luận', 'icon' => '💬', 'period' => $periodTotals['comments'], 'total' => $totals['comments'], 'change' => $changes['comments'] ?? null, 'color' => 'from-amber-50 to-white'],
    ];
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📊 Phân tích tương tác</h1>
            <p class="text-sm text-gray-500 mt-1">
                Lượt xem · lượt chia sẻ · lượt yêu thích · chủ đề &amp; từ khoá đang có tương tác cao
                — khoảng thời gian: <strong>{{ $overview['period']['label'] }}</strong>.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('chatbot.index', ['q' => 'Chủ đề nào đang có lượt tương tác cao nhất?']) }}"
               class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                🤖 Hỏi chatbot
            </a>
            <a href="{{ route('posts.index') }}"
               class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                📝 Danh sách bài viết
            </a>
        </div>
    </div>

    <!-- Period tabs -->
    <div class="bg-white rounded-xl shadow-sm border p-3 flex flex-wrap items-center gap-2">
        <span class="text-sm text-gray-500 mr-1">🗓️ Khoảng thời gian:</span>
        @foreach(AnalyticsService::PERIODS as $key => $days)
            @php
                $label = $days === null ? 'Tất cả' : $days.' ngày';
            @endphp
            <a href="{{ route('admin.analytics.index', ['period' => $key, 'metric' => $metric, 'category' => $category]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $period === $key ? 'bg-slate-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
        <span class="ml-auto text-xs text-gray-400">
            {{ number_format($overview['posts']['published']) }} bài đã xuất bản ·
            {{ number_format($overview['authors']['active_in_period']) }} người dùng tương tác trong kỳ
        </span>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($kpiCards as $card)
            <div class="bg-gradient-to-br {{ $card['color'] }} rounded-xl shadow-sm border p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600">{{ $card['icon'] }} {{ $card['label'] }}</span>
                    {!! $renderChange($card['change']) !!}
                </div>
                <div class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($card['period']) }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    trong {{ $overview['period']['label'] }} · luỹ kế {{ number_format($card['total']) }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- Trend chart -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-800">📈 Xu hướng tương tác theo ngày</h2>
            <div class="flex items-center gap-4 text-xs text-gray-600">
                @foreach(['views' => ['Lượt xem', '#0284c7'], 'shares' => ['Chia sẻ', '#6366f1'], 'favorites' => ['Yêu thích', '#e11d48'], 'comments' => ['Bình luận', '#d97706']] as $key => [$name, $color])
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-3 h-1 rounded-full inline-block" style="background:{{ $color }}"></span>{{ $name }}
                    </span>
                @endforeach
            </div>
        </div>

        <x-analytics.trend-chart
            :series="$trendData"
            :lines="['views' => '#0284c7', 'shares' => '#6366f1', 'favorites' => '#e11d48', 'comments' => '#d97706']"
            empty-message="Chưa có dữ liệu tương tác trong khoảng thời gian này. Chạy `php artisan db:seed --class=EngagementSeeder` để sinh dữ liệu mẫu."
        />

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 text-sm">
            @foreach([
                ['👁️ Bài xem nhiều nhất', $overview['most_viewed_post']],
                ['🔗 Bài chia sẻ nhiều nhất', $overview['most_shared_post']],
                ['❤️ Bài yêu thích nhất', $overview['most_favorited_post']],
            ] as [$title, $highlight])
                <div class="bg-slate-50 rounded-lg border p-3">
                    <div class="text-xs text-gray-500 mb-1">{{ $title }}</div>
                    @if($highlight)
                        <a href="{{ route('admin.analytics.posts.show', [$highlight, 'period' => $period]) }}" class="font-medium text-gray-800 hover:text-slate-600 line-clamp-1">
                            {{ $highlight->title }}
                        </a>
                        <div class="text-xs text-gray-500 mt-1">
                            👁️ {{ number_format($highlight->views_count) }} ·
                            🔗 {{ number_format($highlight->shares_count) }} ·
                            ❤️ {{ number_format($highlight->favorites_count) }}
                        </div>
                    @else
                        <span class="text-gray-400">Chưa có dữ liệu</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Top posts -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-6 pb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">🏆 Bài viết có tương tác cao</h2>
                <p class="text-xs text-gray-500 mt-0.5">Xếp hạng theo {{ $metric === 'engagement' ? 'điểm tương tác' : $metric }} trên toàn bộ thời gian{{ $category !== '' ? ' · danh mục: '.$category : '' }}</p>
            </div>
            <form method="GET" action="{{ route('admin.analytics.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="period" value="{{ $period }}">
                <select name="metric" class="border-gray-300 rounded-lg px-3 py-1.5 border text-sm focus:ring-2 focus:ring-slate-400">
                    @foreach(['engagement' => '🧮 Điểm tương tác', 'views' => '👁️ Lượt xem', 'shares' => '🔗 Lượt chia sẻ', 'favorites' => '❤️ Lượt yêu thích', 'comments' => '💬 Bình luận'] as $value => $label)
                        <option value="{{ $value }}" {{ $metric === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="category" class="border-gray-300 rounded-lg px-3 py-1.5 border text-sm focus:ring-2 focus:ring-slate-400">
                    <option value="">Tất cả chủ đề</option>
                    @foreach($categories as $categoryOption)
                        <option value="{{ $categoryOption }}" {{ $category === $categoryOption ? 'selected' : '' }}>{{ ucfirst($categoryOption) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-700 text-white hover:bg-slate-800 transition">Lọc</button>
            </form>
        </div>

        @if($topPosts->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-gray-600 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-6 py-3 font-medium">#</th>
                            <th class="text-left px-3 py-3 font-medium">Ảnh</th>
                            <th class="text-left px-3 py-3 font-medium">Bài viết</th>
                            <th class="text-right px-3 py-3 font-medium">👁️ Xem</th>
                            <th class="text-right px-3 py-3 font-medium">🔗 Chia sẻ</th>
                            <th class="text-right px-3 py-3 font-medium">❤️ Yêu thích</th>
                            <th class="text-right px-3 py-3 font-medium">💬 Bình luận</th>
                            <th class="text-right px-3 py-3 font-medium">🧮 Điểm</th>
                            <th class="text-left px-3 py-3 font-medium">Xu hướng</th>
                            <th class="text-right px-6 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($topPosts as $index => $topPost)
                            @php
                                $postTrend = $topPost->events()
                                    ->whereIn('type', \App\Models\PostEvent::ENGAGEMENT_TYPES)
                                    ->where('created_at', '>=', now()->subDays(14))
                                    ->selectRaw('date(created_at) as day, COUNT(*) as total')
                                    ->groupBy('day')
                                    ->orderBy('day')
                                    ->get()
                                    ->map(fn ($row) => ['label' => $row->day, 'views' => (int) $row->total])
                                    ->all();
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-3"><x-posts.thumbnail :post="$topPost" size="sm" /></td>
                                <td class="px-3 py-3">
                                    <a href="{{ route('posts.show', $topPost) }}" class="font-medium text-gray-800 hover:text-slate-600 line-clamp-1">{{ $topPost->title }}</a>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        📂 {{ ucfirst($topPost->category) }} · ✍️ {{ $topPost->user?->name ?? 'Không rõ' }} · 📅 {{ $topPost->created_at->format('d/m/Y') }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format($topPost->views_count) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format($topPost->shares_count) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format($topPost->favorites_count) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format($topPost->comments_count) }}</td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums">{{ number_format($topPost->engagementScore(), 0, ',', '.') }}</td>
                                <td class="px-3 py-3"><x-analytics.sparkline :series="$postTrend" /></td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('admin.analytics.posts.show', [$topPost, 'period' => $period]) }}"
                                       class="text-xs font-medium text-slate-600 hover:text-slate-900 underline">Chi tiết</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 pb-8 text-sm text-gray-500">Không có bài viết nào khớp bộ lọc này.</div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top topics -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">📂 Chủ đề đang có tương tác cao</h2>
            <p class="text-xs text-gray-500 mb-4">Tổng hợp theo danh mục của bài viết.</p>
            <div class="space-y-4">
                @forelse($topCategories as $topic)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <a href="{{ route('admin.analytics.index', ['period' => $period, 'metric' => $metric, 'category' => $topic['category']]) }}"
                               class="font-medium text-gray-800 hover:text-slate-600">
                                {{ ucfirst($topic['category']) }}
                            </a>
                            <span class="text-xs text-gray-500 tabular-nums">
                                {{ number_format($topic['engagement'], 0, ',', '.') }} điểm · {{ $topic['share_of_engagement'] }}%
                            </span>
                        </div>
                        <x-analytics.bar :value="$topic['engagement']" :max="$maxCategoryEngagement" />
                        <div class="text-xs text-gray-500 mt-1 tabular-nums">
                            👁️ {{ number_format($topic['views']) }} ·
                            🔗 {{ number_format($topic['shares']) }} ·
                            ❤️ {{ number_format($topic['favorites']) }} ·
                            💬 {{ number_format($topic['comments']) }} ·
                            📝 {{ number_format($topic['posts']) }} bài
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Chưa có dữ liệu chủ đề.</p>
                @endforelse
            </div>
        </div>

        <!-- Hot keywords -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">🔑 Từ khoá đang có tương tác cao</h2>
            <p class="text-xs text-gray-500 mb-4">Từ khoá trích tự động từ tiêu đề, tóm tắt, danh mục và nội dung bài viết.</p>

            @if($topKeywords->count() > 0)
                <div class="flex flex-wrap gap-2 mb-5">
                    @foreach($topKeywords as $keywordRow)
                        @php
                            $weight = $maxKeywordEngagement > 0 ? $keywordRow['engagement'] / $maxKeywordEngagement : 0;
                            $fontSize = round(0.75 + ($weight * 0.85), 2);
                            $opacity = round(0.55 + ($weight * 0.45), 2);
                        @endphp
                        <a href="{{ route('posts.index', ['search' => $keywordRow['keyword']]) }}"
                           title="{{ number_format($keywordRow['engagement'], 0, ',', '.') }} điểm · {{ $keywordRow['posts'] }} bài · 👁️ {{ number_format($keywordRow['views']) }}"
                           class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition font-medium"
                           style="font-size:{{ $fontSize }}rem;opacity:{{ $opacity }}">
                            {{ $keywordRow['keyword'] }}
                        </a>
                    @endforeach
                </div>

                <div class="overflow-x-auto border-t pt-3">
                    <table class="min-w-full text-xs">
                        <thead class="text-gray-500 uppercase">
                            <tr>
                                <th class="text-left py-2 font-medium">Từ khoá</th>
                                <th class="text-right py-2 font-medium">Bài</th>
                                <th class="text-right py-2 font-medium">👁️</th>
                                <th class="text-right py-2 font-medium">🔗</th>
                                <th class="text-right py-2 font-medium">❤️</th>
                                <th class="text-right py-2 font-medium">Điểm</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($topKeywords->take(10) as $keywordRow)
                                <tr>
                                    <td class="py-2 font-medium text-gray-800">
                                        {{ $keywordRow['keyword'] }}
                                        @if($keywordRow['top_post_title'])
                                            <span class="text-gray-400 font-normal">— {{ \Illuminate\Support\Str::limit($keywordRow['top_post_title'], 40) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right tabular-nums">{{ number_format($keywordRow['posts']) }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ number_format($keywordRow['views']) }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ number_format($keywordRow['shares']) }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ number_format($keywordRow['favorites']) }}</td>
                                    <td class="py-2 text-right font-semibold tabular-nums">{{ number_format($keywordRow['engagement'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500">Chưa có từ khoá nào — cần ít nhất một bài viết để phân tích.</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Share platforms -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-3">📣 Chia sẻ theo nền tảng</h2>
            <div class="space-y-3">
                @forelse($platforms as $platformRow)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 font-medium">{{ $platformRow['label'] }}</span>
                            <span class="text-xs text-gray-500 tabular-nums">{{ number_format($platformRow['shares']) }} · {{ $platformRow['percentage'] }}%</span>
                        </div>
                        <x-analytics.bar :value="$platformRow['shares']" :max="$maxPlatformShares" color="#6366f1" />
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Chưa có lượt chia sẻ nào trong kỳ.</p>
                @endforelse
            </div>
        </div>

        <!-- Traffic sources -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-3">🌐 Lượt xem đến từ đâu</h2>
            <div class="space-y-3">
                @forelse($sources as $sourceRow)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 font-medium">{{ $sourceRow['label'] }}</span>
                            <span class="text-xs text-gray-500 tabular-nums">{{ number_format($sourceRow['views']) }} · {{ $sourceRow['percentage'] }}%</span>
                        </div>
                        <x-analytics.bar :value="$sourceRow['views']" :max="$maxSourceViews" color="#0ea5e9" />
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Chưa có lượt xem nào trong kỳ.</p>
                @endforelse
            </div>
        </div>

        <!-- Authors -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-3">✍️ Tác giả nổi bật</h2>
            <div class="space-y-3">
                @forelse($authors as $authorRow)
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <div class="font-medium text-gray-800">{{ $authorRow['author'] }}</div>
                            <div class="text-xs text-gray-500 tabular-nums">
                                {{ number_format($authorRow['posts']) }} bài · 👁️ {{ number_format($authorRow['views']) }} · ❤️ {{ number_format($authorRow['favorites']) }}
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-slate-700 tabular-nums">{{ number_format($authorRow['engagement'], 0, ',', '.') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Chưa có dữ liệu tác giả.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent activity -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-3">🕒 Tương tác gần đây</h2>
            <ul class="divide-y divide-gray-50 text-sm">
                @forelse($recentEvents as $event)
                    <li class="py-2 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="mr-1">{{ $event->icon() }}</span>
                            <span class="text-gray-700">{{ $event->label() }}</span>
                            @if($event->post)
                                trên
                                <a href="{{ route('admin.analytics.posts.show', $event->post) }}" class="font-medium text-slate-700 hover:text-slate-900 truncate">
                                    {{ \Illuminate\Support\Str::limit($event->post->title, 48) }}
                                </a>
                            @endif
                            @if($event->user)
                                <span class="text-gray-500">bởi {{ $event->user->name }}</span>
                            @endif
                            @if(($event->meta['platform'] ?? null))
                                <span class="text-xs text-gray-400">({{ \App\Services\AnalyticsService::platformLabel($event->meta['platform']) }})</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ $event->created_at?->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-gray-500">Chưa có tương tác nào được ghi nhận.</li>
                @endforelse
            </ul>
        </div>

        <!-- Ask the chatbot -->
        <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl border p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-1">🤖 Hỏi đáp với chatbot về số liệu này</h2>
            <p class="text-xs text-gray-500 mb-4">
                Chatbot dùng các tool phân tích (chỉ admin) để trả lời bằng số liệu thật, không suy đoán.
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach($chatbotQuestions as $question)
                    <a href="{{ route('chatbot.index', ['q' => $question]) }}"
                       class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-sm text-slate-700 hover:border-slate-400 hover:bg-slate-50 transition">
                        {{ $question }}
                    </a>
                @endforeach
            </div>
            <div class="mt-5 text-xs text-gray-500 space-y-1 border-t border-slate-200 pt-4">
                <div><code class="bg-white px-1.5 py-0.5 rounded">get_engagement_analytics</code> — tổng quan lượt xem / chia sẻ / yêu thích, so sánh kỳ trước</div>
                <div><code class="bg-white px-1.5 py-0.5 rounded">get_trending_topics</code> — chủ đề (danh mục) có tương tác cao</div>
                <div><code class="bg-white px-1.5 py-0.5 rounded">get_hot_keywords</code> — từ khoá có tương tác cao</div>
                <div><code class="bg-white px-1.5 py-0.5 rounded">get_post_engagement</code> — phân tích chi tiết một bài viết</div>
            </div>
        </div>
    </div>
</div>
@endsection
