<?php

use App\Models\Announcement;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public Announcement $announcement;

    public function mount(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }
}; ?>

<div class="max-w-2xl mx-auto space-y-6">
    <a href="{{ route('announcements.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
        &larr; 返回列表
    </a>

    <div class="space-y-2">
        <div class="flex items-center gap-2">
            @if ($announcement->is_pinned)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    置頂公告
                </span>
            @endif
            <span class="text-sm text-gray-500">
                發佈於 {{ $announcement->published_at?->format('Y-m-d H:i') }}
            </span>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $announcement->title }}</h1>
    </div>

    <hr class="border-gray-200 dark:border-gray-700" />

    <div class="text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap text-lg">
        {{ $announcement->content }}
    </div>
</div>
