@php
    $pageTitle = ($title ?? null) ? $title.' · '.$brand->name : $brand->name;
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="black">
<title>{{ $pageTitle }}</title>
@if ($brand->tagline)
    <meta name="description" content="{{ $brand->tagline }}">
    <meta property="og:description" content="{{ $brand->tagline }}">
@endif
<meta property="og:site_name" content="{{ $brand->name }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:type" content="website">
<link rel="icon" type="image/png" sizes="32x32" href="{{ $brand->iconUrl(32) }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ $brand->iconUrl(16) }}">
<link rel="apple-touch-icon" href="{{ $brand->iconUrl(180) }}">
<link rel="manifest" href="{{ url('/site.webmanifest') }}?v={{ $brand->version }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
