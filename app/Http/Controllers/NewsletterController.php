<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeNewsletterRequest;
use App\Jobs\StoreNewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class NewsletterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SubscribeNewsletterRequest $request): RedirectResponse
    {
        StoreNewsletterSubscriber::dispatch(
            $request->validated('email'),
            Carbon::now(),
        );

        return back()->with('success', '感謝訂閱！請到信箱查看確認信');
    }
}
