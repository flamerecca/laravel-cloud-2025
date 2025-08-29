<?php

Route::fallback(function () {
    $legacyBase = base_path('legacy');

    // 從 Request 抓出原始路徑
    $path = request()->path(); // e.g. "foo.php" 或 "bar/test.php"

    // 安全處理，避免路徑跳脫
    $safePath = str_replace('..', '', $path);

    // 拼出 legacy 目錄底下的檔案路徑
    $legacyFile = $legacyBase . '/' . $safePath;

    if (file_exists($legacyFile)) {
        chdir(dirname($legacyFile));   // 修正相對路徑
        return include $legacyFile;    // 執行舊程式
    }

    // 如果 legacy 裡也沒有 → 回傳 404
    abort(404, "Not Found in Laravel nor Legacy");
});
