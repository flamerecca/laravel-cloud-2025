<?php

namespace App\Jobs;

use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

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
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        NewsletterSubscriber::firstOrCreate(
            ['email' => $this->email],
            ['subscribed_at' => $this->subscribedAt],
        );
    }
}
