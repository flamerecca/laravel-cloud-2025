<?php

use App\Models\Announcement;
use App\Models\User;
use Livewire\Volt\Volt;

test('authenticated user can see announcements list', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->create([
        'title' => 'Important Update',
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('announcements.index'))
        ->assertStatus(200)
        ->assertSee('Important Update');
});

test('authenticated user can see announcement detail', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->create([
        'title' => 'Detail View Test',
        'content' => 'This is the content of the announcement.',
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('announcements.show', $announcement))
        ->assertStatus(200)
        ->assertSee('Detail View Test')
        ->assertSee('This is the content of the announcement.');
});

test('guest cannot see announcements', function () {
    $this->get(route('announcements.index'))
        ->assertRedirect(route('login'));
});

test('announcements list sorts by pinned first', function () {
    $user = User::factory()->create();
    Announcement::factory()->create([
        'title' => 'Normal Announcement',
        'is_pinned' => false,
        'published_at' => now()->subDay(),
    ]);
    Announcement::factory()->create([
        'title' => 'Pinned Announcement',
        'is_pinned' => true,
        'published_at' => now()->subDays(2),
    ]);

    $this->actingAs($user);

    Volt::test('announcements.index')
        ->assertSeeInOrder([
            'Pinned Announcement',
            'Normal Announcement',
        ]);
});
