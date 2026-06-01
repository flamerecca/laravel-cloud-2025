<?php

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('layouts.app')] class extends Component {
    public Announcement $announcement;

    #[Validate('required|string|min:3')]
    public string $commentContent = '';

    public function mount(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function postComment()
    {
        $this->validate();

        $this->announcement->comments()->create([
            'user_id' => auth()->id(),
            'content' => $this->commentContent,
        ]);

        $this->commentContent = '';

        $this->dispatch('comment-posted');
    }

    public function deleteComment(AnnouncementComment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            return;
        }

        $comment->delete();
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

    <div class="pt-10 space-y-8">
        <flux:separator />

        <div class="space-y-4">
            <flux:heading size="lg">評論 ({{ $announcement->comments()->count() }})</flux:heading>

            @auth
                <form wire:submit="postComment" class="space-y-4">
                    <flux:textarea
                        wire:model="commentContent"
                        placeholder="寫下你的評論..."
                        rows="3"
                    />
                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">發表評論</flux:button>
                    </div>
                </form>
            @else
                <flux:callout variant="info">
                    請先 <a href="{{ route('login') }}" class="underline font-bold">登入</a> 以發表評論。
                </flux:callout>
            @endauth
        </div>

        <div class="space-y-6">
            @forelse ($announcement->comments()->latest()->get() as $comment)
                <div wire:key="comment-{{ $comment->id }}" class="flex gap-4">
                    <flux:avatar :name="$comment->user->name" size="sm" />

                    <div class="flex-1 space-y-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900 dark:text-white">{{ $comment->user->name }}</span>
                                <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>

                            @if ($comment->user_id === auth()->id())
                                <flux:button
                                    wire:click="deleteComment({{ $comment->id }})"
                                    wire:confirm="確定要刪除這條評論嗎？"
                                    variant="subtle"
                                    size="sm"
                                    icon="trash"
                                />
                            @endif
                        </div>
                        <div class="text-gray-700 dark:text-gray-300">
                            {{ $comment->content }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 text-gray-500">
                    目前還沒有評論，快來搶頭香！
                </div>
            @endforelse
        </div>
    </div>
</div>
