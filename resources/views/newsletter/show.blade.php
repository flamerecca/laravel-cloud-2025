@php
    $filament = $topic === \App\Enums\NewsletterTopic::Filament;

    // The Filament topic follows the Filament media kit colors; other topics keep the default look.
    $badge = $filament ? 'bg-honey! text-cocoa!' : '';
    $card = $filament ? 'bg-serenade dark:bg-off-black' : 'bg-zinc-50 dark:bg-zinc-800';
    $button = $filament ? 'bg-honey! text-cocoa! hover:bg-honey/90!' : '';
    $keyPointCards = $filament ? ['bg-bubblegum', 'bg-powder', 'bg-minty'] : ['bg-amber-50 dark:bg-zinc-800'];
    $keyPointText = $filament ? 'text-cocoa!' : '';
    $keyPointBadge = $filament ? 'bg-cocoa! text-serenade!' : '';
    $highlightCard = $filament
        ? 'border-l-4 border-honey bg-serenade dark:bg-off-black'
        : 'border border-zinc-200 dark:border-zinc-700';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => '訂閱電子報｜'.$topic->title()])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-10 px-6 py-12">
            <header class="flex flex-col gap-3">
                <flux:badge color="amber" class="self-start {{ $badge }}">免費訂閱</flux:badge>
                <flux:heading size="xl" level="1">{{ $topic->title() }}</flux:heading>
                <flux:text>{{ $topic->description() }}</flux:text>
            </header>

            <section class="flex flex-col gap-4 rounded-xl p-6 {{ $card }}">
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
                            <li class="flex flex-col gap-3 rounded-lg p-4 {{ $keyPointCards[$loop->index % count($keyPointCards)] }}">
                                <flux:badge color="amber" class="self-start {{ $keyPointBadge }}">{{ $loop->iteration }}</flux:badge>
                                <div class="flex flex-col gap-1">
                                    <flux:heading level="3" class="{{ $keyPointText }}">{{ $keyPoint['title'] }}</flux:heading>
                                    <flux:text class="{{ $keyPointText }}">{{ $keyPoint['description'] }}</flux:text>
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
                        <li class="flex flex-col gap-1 rounded-lg p-4 {{ $highlightCard }}">
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
