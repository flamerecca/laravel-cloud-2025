@props(['topic'])

<a
    href="{{ route('newsletter.show', $topic) }}"
    class="flex items-center gap-4 rounded-lg border border-zinc-200 p-4 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
>
    <img src="{{ Vite::asset($topic->logo()) }}" alt="" class="size-12 shrink-0 rounded-md" />
    <div class="flex flex-col gap-1">
        <flux:heading level="2">{{ $topic->title() }}</flux:heading>
        <flux:text>{{ $topic->description() }}</flux:text>
    </div>
</a>
