<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => '電子報列表'])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-10 px-6 py-12">
            <header class="flex flex-col gap-3">
                <flux:badge color="amber" class="self-start">免費訂閱</flux:badge>
                <flux:heading size="xl" level="1">電子報列表</flux:heading>
                <flux:text>挑選有興趣的主題訂閱，每份電子報都需要各自點擊確認信中的連結才會開始寄送。</flux:text>
            </header>

            <ul class="flex flex-col gap-3">
                @foreach ($topics as $topic)
                    <li>
                        <x-newsletter.topic-card :topic="$topic" />
                    </li>
                @endforeach
            </ul>
        </main>
        @fluxScripts
    </body>
</html>
