{{--
    Engagement trend line chart (inline SVG — no JS dependency).

    @param  array<int, array<string, mixed>>  $series  daily points: label, views, shares, favorites, comments
    @param  array<string, string>  $lines              series key => hex colour
    @param  int  $height
    @param  string  $emptyMessage
--}}
@php
    $chartSeries = collect($series ?? [])->values()->all();
    $chartLines = $lines ?? ['views' => '#0284c7', 'shares' => '#6366f1', 'favorites' => '#e11d48', 'comments' => '#d97706'];
    $chartHeight = (int) ($height ?? 180);
    $chartWidth = 760;
    $padTop = 12;
    $padBottom = 26;
    $plotHeight = $chartHeight - $padTop - $padBottom;
    $pointCount = count($chartSeries);
    $step = $pointCount > 1 ? $chartWidth / ($pointCount - 1) : $chartWidth;

    $maxValue = 1;
    foreach ($chartSeries as $point) {
        foreach (array_keys($chartLines) as $key) {
            $maxValue = max($maxValue, (float) ($point[$key] ?? 0));
        }
    }

    $xAt = fn (int $i): float => round($i * $step, 2);
    $yAt = fn ($value): float => round($padTop + $plotHeight - (((float) $value / $maxValue) * $plotHeight), 2);

    $gridLines = '';
    for ($g = 0; $g <= 4; $g++) {
        $gridY = round($padTop + ($plotHeight / 4) * $g, 2);
        $gridValue = (int) round($maxValue - ($maxValue / 4) * $g);
        $gridLines .= '<line x1="0" y1="'.$gridY.'" x2="'.$chartWidth.'" y2="'.$gridY.'" stroke="#e5e7eb" stroke-width="1" stroke-dasharray="4 4"/>';
        $gridLines .= '<text x="2" y="'.($gridY - 4).'" font-size="10" fill="#9ca3af">'.$gridValue.'</text>';
    }

    $polylines = '';
    foreach ($chartLines as $key => $color) {
        $coordinates = [];
        foreach ($chartSeries as $i => $point) {
            $coordinates[] = $xAt($i).','.$yAt($point[$key] ?? 0);
        }
        $polylines .= '<polyline fill="none" stroke="'.$color.'" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" points="'.implode(' ', $coordinates).'"><title>'.e(ucfirst($key)).'</title></polyline>';
    }

    $labelEvery = max(1, (int) ceil($pointCount / 10));
    $axisLabels = '';
    foreach ($chartSeries as $i => $point) {
        if ($i % $labelEvery !== 0 && $i !== $pointCount - 1) {
            continue;
        }
        $axisLabels .= '<text x="'.$xAt($i).'" y="'.($chartHeight - 8).'" font-size="10" fill="#6b7280" text-anchor="middle">'.e($point['label'] ?? '').'</text>';
    }
@endphp

@if($pointCount > 0)
    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" class="w-full" style="height:{{ $chartHeight }}px" role="img" aria-label="Biểu đồ tương tác theo ngày">
        {!! $gridLines !!}
        {!! $polylines !!}
        {!! $axisLabels !!}
    </svg>
@else
    <div class="text-center py-10 text-gray-500 text-sm">{{ $emptyMessage ?? 'Chưa có dữ liệu tương tác.' }}</div>
@endif
