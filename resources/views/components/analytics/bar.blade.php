{{--
    Small horizontal bar showing a value relative to the biggest one.

    @param  float  $value
    @param  float  $max
    @param  string  $color
--}}
@php
    $barPercent = ($max ?? 0) > 0 ? min(100, (((float) $value) / ((float) $max)) * 100) : 0;
@endphp
<div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
    <div class="h-2 rounded-full transition-all" style="width:{{ round($barPercent, 1) }}%;background:{{ $color ?? '#334155' }}"></div>
</div>
