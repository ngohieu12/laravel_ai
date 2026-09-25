@extends('layouts.dashboard')

@section('title', 'Phân tích bài viết của tôi')

@section('content')
    @php
        $trendData = collect($trend)->values()->all();
        $periodTotals['engagement'] = $periodTotals['engagement'] ?? ($periodTotals['interactions'] ?? 0);
        $cards = [
            ['label' => 'Lượt xem', 'icon' => '👁️', 'key' => 'views'],
            ['label' => 'Lượt chia sẻ', 'icon' => '🔗', 'key' => 'shares'],
            ['label' => 'Lượt yêu thích', 'icon' => '❤️', 'key' => 'favorites'],
            ['label' => 'Bình luận', 'icon' => '💬', 'key' => 'comments'],
            ['label' => 'Lượt lưu bài', 'icon' => '📌', 'key' => 'saves'],
            ['label' => 'Điểm tương tác', 'icon' => '🧮', 'key' => 'engagement'],
        ];
    @endphp

    <div class="flex flex-wrap gap-2 items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">📈 Phân tích bài viết của tôi</h1>
        <form method="GET" action="{{ route('dashboard.analytics.index') }}" class="flex gap-2 items-center">
            <select name="period" class="border-gray-300 rounded-lg px-3 py-2 border text-sm" onchange="this.form.submit()">
                @foreach(\App\Services\AnalyticsService::PERIODS as $value => $label)
                    <option value="{{ $value }}" {{ $period === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <p class="text-sm text-slate-500">Chỉ số của riêng các bài viết bạn đăng — tính đến {{ $periodLabel }}.</p>

    <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($cards as $card)
            <div class="rounded-xl border bg-gradient-to-b from-slate-50 to-white p-4 shadow-sm">
                <div class="text-2xl">{{ $card['icon'] }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ $card['label'] }} (kỳ này)</div>
                <div class="text-2xl font-bold text-slate-800">{{ number_format($periodTotals[$card['key']] ?? 0) }}</div>
                <div class="text-xs text-slate-400 mt-1">Tổng: {{ number_format($totals[$card['key']] ?? 0) }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-3">Xu hướng theo ngày</h2>
        <x-analytics.trend-chart :series="$trendData" />
    </div>

    <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-900">Bài viết nổi bật của tôi</h2>
            <form method="GET" action="{{ route('dashboard.analytics.index') }}" class="mt-2 flex flex-wrap gap-2 items-center text-sm">
                <input type="hidden" name="period" value="{{ $period }}">
                <span class="text-slate-500">Sắp xếp theo:</span>
                @foreach($metrics as $m)
                    <a href="{{ route('dashboard.analytics.index', array_merge(request()->except('metric'), ['metric' => $m])) }}"
                       class="px-3 py-1 rounded-full text-xs font-medium {{ $metric === $m ? 'bg-slate-700 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">
                        {{ ucfirst($m) }}
                    </a>
                @endforeach
            </form>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Bài viết</th>
                    <th class="px-4 py-3">👁️ Xem</th>
                    <th class="px-4 py-3">🔗 Chia sẻ</th>
                    <th class="px-4 py-3">❤️ Yêu thích</th>
                    <th class="px-4 py-3">💬 Bình luận</th>
                    <th class="px-4 py-3">📌 Lưu</th>
                    <th class="px-4 py-3">🧮 Điểm</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($topPosts as $topPost)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('dashboard.analytics.posts.show', $topPost) }}" class="font-medium text-slate-800 hover:underline">{{ $topPost->title }}</a>
                        </td>
                        <td class="px-4 py-3">{{ number_format($topPost->views_count) }}</td>
                        <td class="px-4 py-3">{{ number_format($topPost->shares_count) }}</td>
                        <td class="px-4 py-3">{{ number_format($topPost->favorites_count) }}</td>
                        <td class="px-4 py-3">{{ number_format($topPost->comments_count) }}</td>
                        <td class="px-4 py-3">{{ number_format($topPost->saves_count) }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($topPost->engagementScore(), 1) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-3 text-slate-500">Bạn chưa có bài viết nào.</td>
                        <td colspan="6"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($posts->isNotEmpty())
        <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900">Tất cả bài viết của tôi</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Bài viết</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Danh mục</th>
                        <th class="px-4 py-3">Ngày đăng</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($posts as $mine)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('dashboard.analytics.posts.show', $mine) }}" class="font-medium text-slate-800 hover:underline">{{ $mine->title }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mine->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $mine->is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ ucfirst($mine->category) }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $mine->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
