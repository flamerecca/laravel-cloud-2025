<?php

namespace App\Jobs;

use App\Enums\NewsletterTopic;
use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class StoreNewsletterSubscriber implements ShouldQueue
{
    use Queueable;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60, 120, 300];

    public int $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $email,
        public readonly Carbon $subscribedAt,
        public readonly ?NewsletterTopic $topic = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $subscriber = NewsletterSubscriber::forTopic($this->topic)->firstOrCreate(
            ['email' => $this->email],
            ['subscribed_at' => $this->subscribedAt],
        );

        // Coming back after unsubscribing starts over: the address has to be confirmed again.
        if ($subscriber->unsubscribed_at !== null) {
            $subscriber->update([
                'subscribed_at' => $this->subscribedAt,
                'confirmed_at' => null,
                'unsubscribed_at' => null,
                'series_started_at' => null,
                'series_next_at' => null,
            ]);
        }

        if ($subscriber->isConfirmed()) {
            return;
        }

        Mail::to($this->email)->send(new NewsletterConfirmation($this->email, $this->topic));
    }
}
