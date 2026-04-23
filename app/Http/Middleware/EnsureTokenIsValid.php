<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Token');
        if ($token !== 'secret-token') {
            return response()->json([
                'status' => 'error',
                'message' => 'Header 驗證失敗，請求被拒絕！'
            ], Response::HTTP_UNAUTHORIZED);
        }
        return $next($request);
    }
}
