<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => '確認取消訂閱'])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-6 px-6 py-12">
            <flux:heading size="xl" level="1">確認取消訂閱</flux:heading>
            <flux:text>
                取消訂閱後，就不會再收到「{{ $topic?->title() ?? config('app.name').' 電子報' }}」。
            </flux:text>

            <form method="POST" action="{{ $action }}" class="flex gap-3">
                @csrf
                <flux:button type="submit" variant="danger">確認取消訂閱</flux:button>
                <flux:button :href="url('/')" variant="ghost">回到首頁</flux:button>
            </form>
        </main>
        @fluxScripts
    </body>
</html>
