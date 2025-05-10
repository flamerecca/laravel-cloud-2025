<?php

namespace App\Http\Controllers;

use App\Services\ParityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParityController extends Controller
{
    public function __construct(private ParityService $parityService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        // 驗證輸入
        $validated = $request->validate([
            'nums' => ['required', 'array'],
            'nums.*' => 'integer',
        ]);

        $nums = $validated['nums'];

        // 依規則轉換
        $parity = $this->parityService;
        $result = $parity($nums);

        return response()->json([
            'result' => $result
        ]);
    }
}
