<?php

namespace App\Jobs;

use App\Mail\NewsletterBroadcastMail;
use App\Models\NewsletterBroadcast;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewsletterBroadcast implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public int $tries = 4;

    public function __construct(
        public readonly int $broadcastId,
        public readonly string $email,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $broadcast = NewsletterBroadcast::find($this->broadcastId);

        if (! $broadcast) {
            return;
        }

        // Every subscription of this address may have been unsubscribed since the broadcast was queued.
        $deliverable = NewsletterSubscriber::deliverable()
            ->whereRaw('lower(email) = ?', [$this->email])
            ->exists();

        if (! $deliverable) {
            return;
        }

        // The unique row is the claim: whoever inserts it first sends, a retry or a duplicate job stops here.
        try {
            $delivery = $broadcast->deliveries()->create(['email' => $this->email, 'sent_at' => now()]);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        try {
            Mail::to($this->email)->send(new NewsletterBroadcastMail($broadcast));
        } catch (\Throwable $e) {
            // Release the claim so the retry can send it.
            $delivery->delete();

            throw $e;
        }
    }
}
