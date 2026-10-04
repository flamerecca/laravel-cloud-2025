<?php

namespace App\Http\Controllers;

use App\Enums\NewsletterTopic;
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
            $request->enum('topic', NewsletterTopic::class),
        );

        return back()->with('success', '感謝訂閱！請到信箱點擊確認連結，完成訂閱');
    }
}
