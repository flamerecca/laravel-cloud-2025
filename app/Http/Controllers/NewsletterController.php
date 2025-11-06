<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NewsletterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $webhookUrl = config('services.n8n.webhook');

        Http::post($webhookUrl, [
            'email' => $validated['email'],
            'subscribed_at' => now()->toDateTimeString(),
        ]);

        return back()->with('success', '感謝訂閱！請到信箱查看確認信');
    }
}
