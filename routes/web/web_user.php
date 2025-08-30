<?php

use Illuminate\Support\Facades\Route;
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

Route::get('/users-with-cache', function (Request $request) {
    $email = $request->input('email');
    return Cache::remember("user_search_email_{$email}", 300, function () use ($email) {
        return response()->json(User::where('email', $email)->get());
    });
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
