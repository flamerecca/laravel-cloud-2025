<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $unsubscribed ? '已取消訂閱' : '退訂連結無效', 'robots' => 'noindex, nofollow'])
    </head>
    <body class="min-h-screen bg-terminal text-ink antialiased">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-6 px-6 py-12">
            <x-home-button />

            @if ($unsubscribed)
                <flux:callout variant="success" icon="check-circle" heading="已取消訂閱">
                    <flux:callout.text>
                        之後不會再收到「{{ $topic?->title() ?? config('mail.newsletter_name') }}」。
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:callout variant="danger" icon="x-circle" heading="退訂連結無效">
                    <flux:callout.text>
                        這個連結不完整或找不到對應的訂閱，請從最新一封電子報底部的連結再試一次。
                    </flux:callout.text>
                </flux:callout>
            @endif
        </main>
        @fluxScripts
    </body>
</html>
