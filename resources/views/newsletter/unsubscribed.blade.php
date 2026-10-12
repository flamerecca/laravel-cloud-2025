<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $unsubscribed ? '已取消訂閱' : '退訂連結無效'])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-6 px-6 py-12">
            @if ($unsubscribed)
                <flux:callout variant="success" icon="check-circle" heading="已取消訂閱">
                    <flux:callout.text>
                        之後不會再收到「{{ $topic?->title() ?? config('app.name').' 電子報' }}」。
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:callout variant="danger" icon="x-circle" heading="退訂連結無效">
                    <flux:callout.text>
                        這個連結不完整或找不到對應的訂閱，請從最新一封電子報底部的連結再試一次。
                    </flux:callout.text>
                </flux:callout>
            @endif

            <flux:button :href="url('/')" class="self-start">回到首頁</flux:button>
        </main>
        @fluxScripts
    </body>
</html>
