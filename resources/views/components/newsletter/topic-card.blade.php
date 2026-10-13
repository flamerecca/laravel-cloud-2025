@props(['topic'])

<a
    href="{{ route('newsletter.show', $topic->slug()) }}"
    class="term-card flex items-center gap-4 p-4 transition"
>
    <img src="{{ Vite::asset($topic->logo()) }}" alt="" class="size-12 shrink-0 rounded-sm" />
    <div class="flex flex-col gap-1">
        <flux:heading level="2">{{ $topic->title() }}</flux:heading>
        <flux:text>{{ $topic->description() }}</flux:text>
    </div>
</a>
