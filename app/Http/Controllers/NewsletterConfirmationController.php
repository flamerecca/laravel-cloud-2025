<?php

namespace App\Http\Controllers;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NewsletterConfirmationController extends Controller
{
    /**
     * Confirm a subscription from the signed link in the confirmation email.
     */
    public function __invoke(Request $request): Response
    {
        $topic = $request->enum('topic', NewsletterTopic::class);

        if (! $request->hasValidSignature()) {
            return response()->view('newsletter.confirmed', ['confirmed' => false, 'topic' => $topic], 403);
        }

        $subscriber = NewsletterSubscriber::forTopic($topic)
            ->where('email', $request->query('email'))
            ->first();

        if (! $subscriber) {
            return response()->view('newsletter.confirmed', ['confirmed' => false, 'topic' => $topic], 404);
        }

        if (! $subscriber->isConfirmed()) {
            $subscriber->update(['confirmed_at' => now()]);
        }

        return response()->view('newsletter.confirmed', ['confirmed' => true, 'topic' => $topic]);
    }
}
