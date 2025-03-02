<?php


use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
Route::get('/users', function (Request $request) {
    $name = $request->input('name');
    $users = User::where('name', $name)->get();

    return response()->json($users);
});

Route::get('/users-seed', function () {
    User::factory()
        ->count(100_000)
        ->create();
});

Route::get('/users-with-index', function (Request $request) {
    dd(User::where('email', $request->input('email'))->first());
});

Route::get('/users-with-index', function (Request $request) {
    dd(User::where('email_without_index', $request->input('email'))->first());
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
