<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? 'Laravel' }}</title>

@if (! empty($description))
    <meta name="description" content="{{ $description }}">
@endif
@if (! empty($robots))
    <meta name="robots" content="{{ $robots }}">
@endif
@if (! empty($canonical))
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="zh_TW">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    @if (! empty($description))
        <meta property="og:description" content="{{ $description }}">
    @endif
    @if (! empty($image))
        <meta property="og:image" content="{{ $image }}">
        <meta property="og:image:alt" content="{{ $imageAlt ?? $title ?? config('app.name') }}">
    @endif

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $title ?? config('app.name') }}">
    @if (! empty($description))
        <meta name="twitter:description" content="{{ $description }}">
    @endif
    @if (! empty($image))
        <meta name="twitter:image" content="{{ $image }}">
    @endif
@endif

<link rel="icon" type="image/png" href="{{ Vite::asset('resources/images/reccaChao.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,700" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
