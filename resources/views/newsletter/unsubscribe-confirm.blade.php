<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => '確認取消訂閱', 'robots' => 'noindex, nofollow'])
    </head>
    <body class="min-h-screen bg-terminal text-ink antialiased">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-6 px-6 py-12">
            <x-home-button />

            <flux:heading size="xl" level="1" class="term-title">確認取消訂閱</flux:heading>
            <flux:text>
                取消訂閱後，就不會再收到「{{ $topic?->title() ?? config('mail.newsletter_name') }}」。
            </flux:text>

            <form method="POST" action="{{ $action }}" class="flex gap-3">
                @csrf
                <flux:button type="submit" variant="danger">確認取消訂閱</flux:button>
                <flux:button :href="route('home')" variant="ghost">保留訂閱</flux:button>
            </form>
        </main>
        @fluxScripts
    </body>
</html>
