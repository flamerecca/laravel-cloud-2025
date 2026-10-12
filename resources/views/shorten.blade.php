<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>短網址產生器</title>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<a href="{{ route('home') }}" style="position: fixed; top: 1rem; left: 1rem;">回到首頁</a>
@livewire('url-shortener')

@livewireScripts
</body>
</html>
