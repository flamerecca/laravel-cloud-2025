<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::post('/submissions', function (Request $request) {
    // 使用 request->validate() 進行驗證
    $validated = $request->validate([
        'student_id' => ['required', 'string'],
        'assignment_id' => ['required', 'string'],
        'file' => ['required', 'mimes:pdf', 'max:5120'],  // 限制為 PDF，最大 5MB
    ]);

    // 檢查是否有檔案並儲存
    $file = $request->file('file');
    $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();  // 隨機檔案名稱
    $file->storeAs('submissions', $fileName, 'public');  // 儲存到 storage/app/public/submissions

    // 回應成功
    return response()->json([
        'message' => 'Assignment uploaded successfully.',
        'submission_id' => Str::uuid(),  // 隨機生成 submission_id
        'student_id' => $validated['student_id'],
        'assignment_id' => $validated['assignment_id'],
        'filename' => $fileName
    ], 200);
});
