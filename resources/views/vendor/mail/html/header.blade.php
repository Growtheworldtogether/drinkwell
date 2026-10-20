@props(['url'])
@php
    // Mail clients cannot fetch a logo from localhost, so the wordmark is the fallback (SPEC 11.6).
    $brand = app(\App\Services\BrandService::class)->brand();
    $host = parse_url((string) config('app.url'), PHP_URL_HOST);
    $logo = $brand->logoLightUrl && ! in_array($host, [null, 'localhost', '127.0.0.1', '::1'], true)
        ? url('/brand/light').'?v='.$brand->version
        : null;
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logo)
<img src="{{ $logo }}" class="logo" height="32" alt="{{ $brand->name }}">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
