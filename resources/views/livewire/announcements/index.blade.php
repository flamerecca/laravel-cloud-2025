<?php

use App\Models\Announcement;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    #[Computed]
    public function announcements()
    {
        return Announcement::where('published_at', '<=', now())
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->get();
    }
}; ?>

<div class="space-y-4">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">最新公告</h1>

    @if ($this->announcements->isEmpty())
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center text-gray-500">
            目前尚無公告
        </div>
    @else
        @foreach ($this->announcements as $announcement)
            <div wire:key="ann-{{ $announcement->id }}" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 flex flex-col gap-2">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-2">
                        @if ($announcement->is_pinned)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                置頂
                            </span>
                        @endif
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $announcement->title }}
                        </h3>
                    </div>
                    <span class="text-sm text-gray-500">
                        {{ $announcement->published_at?->format('Y-m-d') }}
                    </span>
                </div>

                <p class="text-gray-600 dark:text-gray-400 line-clamp-2">
                    {{ str($announcement->content)->limit(150) }}
                </p>

                <div class="mt-2">
                    <a href="{{ route('announcements.show', $announcement) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        閱讀全文 &rarr;
                    </a>
                </div>
            </div>
        @endforeach
    @endif
</div>
