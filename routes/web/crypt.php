<?php

use Illuminate\Support\Facades\Route;

Route::get('crypt/', function () {
    dd(str('eyJpdiI6Ikx5WXI2Ukp4UlV3eStMbC90M2dRalE9PSIsInZhbHVlIjoiWHJXdE9uYUJLWUJpWDJkeGFrbk9sQT09IiwibWFjIjoiYjU4NjViM2M1NjcxMjdkNDdjNGFjYjc4ZjNiM2ZhYzk3ZDEyNzk0ODc1ZWUxMDk2ZTYxMjU4YmJmM2U1OTRjZiIsInRhZyI6IiJ9')->decrypt());
});
