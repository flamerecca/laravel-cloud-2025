<?php

use App\Models\Announcement;
use App\Models\AnnouncementComment;
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

test('users can see comments on announcement detail page', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->create();
    $comment = AnnouncementComment::factory()->create([
        'announcement_id' => $announcement->id,
        'content' => 'This is a test comment',
    ]);

    $this->actingAs($user)
        ->get(route('announcements.show', $announcement))
        ->assertStatus(200)
        ->assertSee('This is a test comment')
        ->assertSee($comment->user->name);
});

test('authenticated users can post a comment', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->create();

    $this->actingAs($user);

    Volt::test('announcements.show', ['announcement' => $announcement])
        ->set('commentContent', 'New comment content')
        ->call('postComment')
        ->assertSet('commentContent', '')
        ->assertDispatched('comment-posted');

    $this->assertDatabaseHas('announcement_comments', [
        'announcement_id' => $announcement->id,
        'user_id' => $user->id,
        'content' => 'New comment content',
    ]);
});

test('users can delete their own comment', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->create();
    $comment = AnnouncementComment::factory()->create([
        'announcement_id' => $announcement->id,
        'user_id' => $user->id,
        'content' => 'My comment to be deleted',
    ]);

    $this->actingAs($user);

    Volt::test('announcements.show', ['announcement' => $announcement])
        ->call('deleteComment', $comment->id)
        ->assertHasNoErrors();

    $this->assertSoftDeleted('announcement_comments', [
        'id' => $comment->id,
    ]);
});

test('users cannot delete others comments', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $announcement = Announcement::factory()->create();
    $comment = AnnouncementComment::factory()->create([
        'announcement_id' => $announcement->id,
        'user_id' => $otherUser->id,
        'content' => 'Someone else\'s comment',
    ]);

    $this->actingAs($user);

    Volt::test('announcements.show', ['announcement' => $announcement])
        ->call('deleteComment', $comment->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('announcement_comments', [
        'id' => $comment->id,
        'deleted_at' => null,
    ]);
});
