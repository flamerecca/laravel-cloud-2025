<?php

namespace App\Http\Controllers;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NewsletterUnsubscribeController extends Controller
{
    /**
     * Ask for confirmation. Nothing is written here: mail scanners open every link in a message.
     */
    public function show(Request $request): Response
    {
        $topic = $request->enum('topic', NewsletterTopic::class);

        if (! $request->hasValidSignature()) {
            return response()->view('newsletter.unsubscribed', ['unsubscribed' => false, 'topic' => $topic], 403);
        }

        return response()->view('newsletter.unsubscribe-confirm', ['topic' => $topic, 'action' => $request->fullUrl()]);
    }

    /**
     * Stop deliveries, from the confirmation form or a one-click request from the mail client.
     */
    public function store(Request $request): Response
    {
        $topic = $request->enum('topic', NewsletterTopic::class);

        if (! $request->hasValidSignature()) {
            return response()->view('newsletter.unsubscribed', ['unsubscribed' => false, 'topic' => $topic], 403);
        }

        $subscriber = NewsletterSubscriber::forTopic($topic)
            ->where('email', $request->query('email'))
            ->first();

        if (! $subscriber) {
            return response()->view('newsletter.unsubscribed', ['unsubscribed' => false, 'topic' => $topic], 404);
        }

        if ($subscriber->unsubscribed_at === null) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return response()->view('newsletter.unsubscribed', ['unsubscribed' => true, 'topic' => $topic]);
    }
}
