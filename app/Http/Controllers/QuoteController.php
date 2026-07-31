<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                'message' => 'Internal Server Error',
            ], 500);
        }

        $quote = Inspiring::quote();

        return response()->json([
            'status' => 'success',
            'data' => [
                'quote' => $quote,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
