<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function decrement(): void
    {
        $this->count--;
    }
};
?>

<div class="flex items-center gap-4">
    <button type="button" class="rounded bg-gray-200 px-3 py-1 text-lg" wire:click="decrement">-</button>
    <span class="text-2xl font-bold">{{ $count }}</span>
    <button type="button" class="rounded bg-gray-200 px-3 py-1 text-lg" wire:click="increment">+</button>
</div>
