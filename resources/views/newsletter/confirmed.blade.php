<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $confirmed ? '訂閱成功' : '確認連結無效'])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-6 px-6 py-12">
            @if ($confirmed)
                <flux:callout variant="success" icon="check-circle" heading="訂閱成功">
                    <flux:callout.text>
                        已確認您的 Email，之後會開始收到「{{ $topic?->title() ?? config('app.name').' 電子報' }}」。
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:callout variant="danger" icon="x-circle" heading="確認連結無效">
                    <flux:callout.text>
                        這個連結已過期或不完整，請重新訂閱，我們會寄一封新的確認信給您。
                    </flux:callout.text>
                </flux:callout>
            @endif

            <flux:button :href="$topic ? route('newsletter.show', $topic) : url('/')" class="self-start">
                {{ $confirmed ? '回到電子報頁面' : '重新訂閱' }}
            </flux:button>
        </main>
        @fluxScripts
    </body>
</html>
