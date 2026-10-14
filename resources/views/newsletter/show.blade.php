@php
    $filament = $topic === \App\Enums\NewsletterTopic::Filament;
    $laravel = $topic === \App\Enums\NewsletterTopic::Laravel;
    $springBoot = $topic === \App\Enums\NewsletterTopic::SpringBoot;

    // Filament follows the Filament media kit colors, Laravel the Laravel brand colors and Spring Boot
    // the Spring green with a Kotlin purple accent;
    // other topics keep the default look.
    $badge = match (true) {
        $filament => 'bg-honey! text-cocoa!',
        $laravel => 'bg-laravel-red! text-white!',
        $springBoot => 'bg-spring-green! text-spring-ink!',
        default => '',
    };
    $card = match (true) {
        $filament => 'bg-off-black',
        $laravel => 'bg-laravel-ink border-t-4 border-laravel-red',
        $springBoot => 'bg-spring-ink border-t-4 border-spring-green',
        default => 'term-card bg-surface',
    };
    $button = match (true) {
        $filament => 'bg-honey! text-cocoa! hover:bg-honey/90!',
        $laravel => 'bg-laravel-red! text-white! hover:bg-laravel-ember!',
        $springBoot => 'bg-spring-green! text-spring-ink! hover:bg-spring-leaf!',
        default => '',
    };
    $keyPointCards = match (true) {
        $filament => ['bg-bubblegum', 'bg-powder', 'bg-minty'],
        $laravel => ['bg-laravel-red', 'bg-laravel-blush', 'bg-laravel-ink border border-laravel-red'],
        $springBoot => ['bg-spring-green', 'bg-spring-mist', 'bg-kotlin-purple'],
        default => ['term-card'],
    };
    $keyPointText = match (true) {
        $filament => ['text-cocoa!'],
        $laravel => ['text-white!', 'text-laravel-ink!', 'text-laravel-rose!'],
        $springBoot => ['text-spring-ink!', 'text-spring-ink!', 'text-white!'],
        default => [''],
    };
    $keyPointBadge = match (true) {
        $filament => ['bg-cocoa! text-serenade!'],
        $laravel => ['bg-white! text-laravel-red!', 'bg-laravel-red! text-white!', 'bg-laravel-red! text-white!'],
        $springBoot => ['bg-spring-ink! text-spring-green!', 'bg-spring-green! text-spring-ink!', 'bg-white! text-kotlin-purple!'],
        default => [''],
    };
    $highlightCard = match (true) {
        $filament => 'border-l-4 border-honey bg-off-black',
        $laravel => 'border-l-4 border-laravel-red bg-laravel-ink',
        $springBoot => 'border-l-4 border-spring-green bg-spring-ink',
        default => 'term-card',
    };
    $titleAccent = match (true) {
        $laravel => 'text-laravel-red!',
        $springBoot => 'text-spring-green!',
        default => '',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => '訂閱電子報｜'.$topic->title()])
    </head>
    <body class="min-h-screen bg-terminal text-ink antialiased">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-10 px-6 py-12">
            <x-home-button />

            <header class="flex flex-col gap-3">
                @if ($topic->secondaryLogo())
                    <div class="flex items-center gap-3">
                        <img src="{{ Vite::asset($topic->logo()) }}" alt="{{ $topic->title() }}" class="size-14 rounded-sm" />
                        <span class="text-ink/60" aria-hidden="true">+</span>
                        <img src="{{ Vite::asset($topic->secondaryLogo()) }}" alt="{{ $topic->secondaryLogo() === 'resources/images/pest.png' ? 'Pest' : 'Kotlin' }}" class="size-14 rounded-sm" />
                    </div>
                @endif
                <flux:badge color="amber" class="self-start {{ $badge }}">免費訂閱</flux:badge>
                <flux:heading size="xl" level="1" class="term-title {{ $titleAccent }}">{{ $topic->title() }}</flux:heading>
                <flux:text>{{ $topic->description() }}</flux:text>
            </header>

            <section class="flex flex-col gap-4 rounded-sm p-6 {{ $card }}">
                <flux:heading size="lg" level="2">訂閱電子報</flux:heading>

                @if (session('success'))
                    <flux:callout variant="success" icon="check-circle" heading="{{ session('success') }}" />
                @endif

                <form method="POST" action="{{ route('newsletter.subscribe') }}" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    @csrf
                    <input type="hidden" name="topic" value="{{ $topic->value }}">
                    <div class="grow">
                        <flux:input
                            type="email"
                            name="email"
                            :value="old('email')"
                            placeholder="請輸入您的 Email"
                            aria-label="Email"
                            required
                        />
                        @error('email')
                            <flux:text class="mt-2 text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <flux:button type="submit" variant="primary" class="{{ $button }}">訂閱</flux:button>
                </form>
            </section>

            @if ($topic->keyPoints())
                <section class="flex flex-col gap-4">
                    <flux:heading size="lg" level="2">訂閱後你會看到的三個重點</flux:heading>
                    <ol class="grid gap-3 sm:grid-cols-3">
                        @foreach ($topic->keyPoints() as $keyPoint)
                            <li class="flex flex-col gap-3 rounded-sm p-4 {{ $keyPointCards[$loop->index % count($keyPointCards)] }}">
                                <flux:badge color="amber" class="self-start {{ $keyPointBadge[$loop->index % count($keyPointBadge)] }}">{{ $loop->iteration }}</flux:badge>
                                <div class="flex flex-col gap-1">
                                    <flux:heading level="3" class="{{ $keyPointText[$loop->index % count($keyPointText)] }}">{{ $keyPoint['title'] }}</flux:heading>
                                    <flux:text class="{{ $keyPointText[$loop->index % count($keyPointText)] }}">{{ $keyPoint['description'] }}</flux:text>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <section class="flex flex-col gap-4">
                <flux:heading size="lg" level="2">你會收到的內容</flux:heading>
                <ul class="flex flex-col gap-3">
                    @foreach ($topic->highlights() as $highlight)
                        <li class="flex flex-col gap-1 rounded-sm p-4 {{ $highlightCard }}">
                            <flux:heading level="3">{{ $highlight['title'] }}</flux:heading>
                            <flux:text>{{ $highlight['description'] }}</flux:text>
                        </li>
                    @endforeach
                </ul>
            </section>
        </main>
        @fluxScripts
    </body>
</html>
