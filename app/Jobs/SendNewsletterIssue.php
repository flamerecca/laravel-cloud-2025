<?php

namespace App\Jobs;

use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewsletterIssue implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public int $tries = 4;

    public function __construct(
        public readonly int $issueId,
        public readonly int $subscriberId,
        public readonly ?int $round = null,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $issue = NewsletterIssue::find($this->issueId);
        $subscriber = NewsletterSubscriber::find($this->subscriberId);

        if (! $issue || ! $subscriber) {
            return;
        }

        // Queued before the issue was turned back into unsent: the new round has its own jobs.
        $round = $this->round ?? $issue->send_round;

        if ($round !== $issue->send_round) {
            return;
        }

        // A retry after a successful send must not send the same issue twice in one round.
        if ($issue->deliveries()->where('newsletter_subscriber_id', $subscriber->id)->where('send_round', $round)->exists()) {
            return;
        }

        // The subscriber may have unsubscribed between queueing and sending.
        if (! NewsletterSubscriber::deliverable()->whereKey($subscriber->id)->exists()) {
            return;
        }

        Mail::to($subscriber->email)->send(new NewsletterIssueMail($issue, $subscriber));

        $issue->deliveries()->create([
            'newsletter_subscriber_id' => $subscriber->id,
            'send_round' => $round,
            'sent_at' => now(),
        ]);
    }
}
