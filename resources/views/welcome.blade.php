<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => 'Recca Chao'])
    </head>
    <body class="min-h-screen bg-terminal text-ink antialiased">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col gap-12 px-6 py-12">
            @guest
                @if (Route::has('login'))
                    <nav class="flex items-center justify-end gap-2">
                        <flux:button :href="route('login')" size="sm" variant="ghost">Log in</flux:button>
                        @if (Route::has('register'))
                            <flux:button :href="route('register')" size="sm" variant="ghost">Register</flux:button>
                        @endif
                    </nav>
                @endif
            @endguest

            <section class="flex flex-col gap-4">
                <div class="flex items-center gap-4">
                    <div class="term-card flex size-16 shrink-0 items-center justify-center bg-surface text-xl font-semibold text-phosphor">
                        RC
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:heading size="xl" level="1" class="term-title">Recca Chao</flux:heading>
                        <flux:text>後端開發者、講師與社群推廣者</flux:text>
                    </div>
                </div>

                <flux:text>
                    推廣各種後端技術，包含 Laravel 與 Kotlin 後端開發，關注自動化測試、程式碼品質與效能最佳化。擔任 Taiwan Kotlin User Group 管理者與 Taiwan Digital Rogue 共同主辦人，自 2024 年起授課，主題涵蓋 Laravel、Kotlin、n8n 工作流程自動化與網站安全。
                </flux:text>

                <flux:text>
                    曾在 LaravelConf Taiwan、COSCUP、JCConf Taiwan 與各地 DevFest 分享。目前以電子報分享 Filament 進階實戰與 Laravel 自動化測試的經驗。
                </flux:text>

                <div class="flex flex-wrap gap-2">
                    <flux:button href="https://gravatar.com/flamerecca" target="_blank" rel="noopener" size="sm">個人名片</flux:button>
                    <flux:button href="https://github.com/flamerecca" target="_blank" rel="noopener" size="sm">GitHub</flux:button>
                </div>
            </section>

            <section class="flex flex-col gap-4">
                <flux:heading size="lg" level="2">電子報</flux:heading>
                <ul class="flex flex-col gap-3">
                    @foreach ($topics as $topic)
                        <li>
                            <x-newsletter.topic-card :topic="$topic" />
                        </li>
                    @endforeach
                </ul>
            </section>
        </main>
        @fluxScripts
    </body>
</html>
