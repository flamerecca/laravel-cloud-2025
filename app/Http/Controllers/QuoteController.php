<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Inspiring;
class QuoteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (rand(1, 5) === 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error'
            ], 500);
        }

        $quote = Inspiring::quote();

        return response()->json([
            'status' => 'success',
            'data' => [
                'quote' => $quote,
                'timestamp' => now()->toISOString(),
            ]
        ]);
    }
}
