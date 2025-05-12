<?php

use App\Models\ShortUrl;
use Illuminate\Support\Facades\Route;

Route::get('short-urls', function () {
    $shortUrls = ShortUrl::all();

    return view('short_urls.index', compact('shortUrls'));
});
