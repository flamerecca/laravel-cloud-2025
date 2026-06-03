<?php

use App\Http\Controllers\Api\AnnouncementController;

Route::name('api.')->group(function () {
    Route::apiResource('/announcements', AnnouncementController::class);
});

