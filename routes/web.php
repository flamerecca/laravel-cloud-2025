<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

use App\Models\User;
use Illuminate\Http\Request;

Route::get('/users', function (Request $request) {
    $name = $request->input('name');
    $users = User::where('name', $name)->get();

    return response()->json($users);
});


Route::get('/users-count', function () {
    // 計算有幾位 user
    return User::count();
});

Route::get('/users-with-index', function (Request $request) {
    return response()->json(User::where('email', $request->input('email'))->get());
});

Route::get('/users-without-index', function (Request $request) {
    return response()->json(User::where('email_without_index', $request->input('email'))->get());
});

Route::get('/users-with-index-explain', function (Request $request) {
    return response()->json(User::where('email', $request->input('email'))->explain());
});

Route::get('/users-without-index-explain', function (Request $request) {
    return response()->json(User::where('email_without_index', $request->input('email'))->explain());
});

Route::get('/users-rand', function (Request $request) {
    return response()->json(User::inRandomOrder()->first());
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
