{{--
    Tiny sparkline for a single metric.

    @param  array<int, array<string, mixed>>  $series  points containing $key
    @param  string  $key
    @param  string  $color
--}}
@php
    $sparkSeries = collect($series ?? [])->values()->all();
    $sparkKey = $key ?? 'views';
    $sparkColor = $color ?? '#334155';
    $sparkWidth = 110;
    $sparkHeight = 26;
    $sparkCount = count($sparkSeries);
    $sparkMax = max(1, (float) collect($sparkSeries)->max($sparkKey));
    $sparkStep = $sparkCount > 1 ? $sparkWidth / ($sparkCount - 1) : $sparkWidth;
    $sparkPoints = [];

    foreach ($sparkSeries as $i => $point) {
        $sparkPoints[] = round($i * $sparkStep, 2).','.round($sparkHeight - (((float) ($point[$sparkKey] ?? 0) / $sparkMax) * ($sparkHeight - 2)) - 1, 2);
    }
@endphp

@if($sparkCount > 1)
    <svg viewBox="0 0 {{ $sparkWidth }} {{ $sparkHeight }}" class="w-[110px] h-[26px]" aria-hidden="true">
        <polyline fill="none" stroke="{{ $sparkColor }}" stroke-width="1.5" points="{{ implode(' ', $sparkPoints) }}"/>
    </svg>
@else
    <span class="text-xs text-gray-400">chưa đủ dữ liệu</span>
@endif
