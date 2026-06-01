<?php

use App\Http\Controllers\Api\AnnouncementController;

Route::get('/announcements', [AnnouncementController::class, 'index']);

Route::get(
    '/announcements/{id}',
    [AnnouncementController::class, 'show']
);

