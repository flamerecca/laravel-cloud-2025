<?php

Route::get('/ping', function () {
    return 'pong';
});

Route::get('/ping-check', function () {
    return 'pong';
})->middleware('token.check');
