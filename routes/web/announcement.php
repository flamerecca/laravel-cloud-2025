<?php

use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function (): void {
    Volt::route('announcements', 'announcements.index')->name('announcements.index');
    Volt::route('announcements/{announcement}', 'announcements.show')->name('announcements.show');
});
