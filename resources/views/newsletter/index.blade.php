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
                        <a
                            href="{{ route('newsletter.show', $topic) }}"
                            class="flex flex-col gap-1 rounded-lg border border-zinc-200 p-4 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
                        >
                            <flux:heading level="2">{{ $topic->title() }}</flux:heading>
                            <flux:text>{{ $topic->description() }}</flux:text>
                        </a>
                    </li>
                @endforeach

                <li>
                    <a
                        href="{{ route('home') }}"
                        class="flex flex-col gap-1 rounded-lg border border-zinc-200 p-4 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
                    >
                        <flux:heading level="2">{{ config('app.name') }} 電子報</flux:heading>
                        <flux:text>不限主題的一般電子報，在首頁即可訂閱。</flux:text>
                    </a>
                </li>
            </ul>
        </main>
        @fluxScripts
    </body>
</html>
