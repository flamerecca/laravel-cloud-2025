<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/day-of-the-week', function (Request $request) {
    $dateInput = $request->query('date');

    // 日期解析與錯誤處理
    try {
        if (!$dateInput) {
            throw new Exception("Missing date");
        }

        $date = Carbon::createFromFormat('Y-m-d', $dateInput);
    } catch (\Exception $e) {
        return response()->json([
            'error' => "Invalid or missing 'date' parameter. Expected format: YYYY-MM-DD"
        ], 422);
    }

    return response()->json([
        'date' => $date->toDateString(),
        'dayOfWeek' => $date->translatedFormat('l'),
    ]);
});

Route::get('/v2/day-of-the-week', function (Request $request) {
    $dateInput = $request->query('date');
    $locale = $request->query('locale', 'en');

    // 可支援語系
    $supportedLocales = ['en', 'zh_TW', 'ja'];

    // fallback 處理
    if (!in_array($locale, $supportedLocales)) {
        $locale = 'en';
    }

    // 日期解析與錯誤處理
    try {
        if (!$dateInput) {
            throw new Exception("Missing date");
        }

        $date = Carbon::createFromFormat('Y-m-d', $dateInput);
    } catch (\Exception $e) {
        return response()->json([
            'error' => "Invalid or missing 'date' parameter. Expected format: YYYY-MM-DD"
        ], 422);
    }

    Carbon::setLocale($locale);

    return response()->json([
        'date' => $date->toDateString(),
        'locale' => $locale,
        'dayOfWeek' => $date->translatedFormat('l'),
    ]);
});

Route::get('/v3/day-of-the-week', function (Request $request) {
    $dates = $request->query('dates', []);
    $locale = $request->query('locale', 'en');

    $supportedLocales = ['en', 'zh_TW', 'ja'];

    // fallback 語系處理
    if (!in_array($locale, $supportedLocales)) {
        $locale = 'en';
    }

    Carbon::setLocale($locale);

    $results = [];

    foreach ($dates as $dateInput) {
        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateInput);

            $results[] = [
                'date' => $date->toDateString(),
                'dayOfWeek' => $date->translatedFormat('l'),
            ];
        } catch (\Exception $e) {
            $results[] = [
                'date' => $dateInput,
                'error' => 'Invalid date format'
            ];
        }
    }

    return response()->json([
        'locale' => $locale,
        'results' => $results
    ]);
});
