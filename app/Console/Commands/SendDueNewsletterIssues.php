<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterIssue;
use App\Models\NewsletterIssue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class SendDueNewsletterIssues extends Command
{
    protected $signature = 'newsletter:send-due';

    protected $description = 'Queue newsletter issues whose scheduled time has passed';

    public function handle(): int
    {
        NewsletterIssue::due()->get()->each(function (NewsletterIssue $issue): void {
            // Only the process that wins this update queues the issue.
            $claimed = NewsletterIssue::query()
                ->whereKey($issue->id)
                ->whereNull('dispatched_at')
                ->update(['dispatched_at' => now()]);

            if ($claimed !== 1) {
                return;
            }

            try {
                $jobs = $issue->recipients()
                    ->pluck('id')
                    ->map(fn (int $subscriberId) => new SendNewsletterIssue($issue->id, $subscriberId))
                    ->all();

                if ($jobs === []) {
                    $issue->update(['sent_at' => now()]);
                    $this->components->info("第 {$issue->number} 期沒有可寄送的訂閱者，直接標記為已寄出");

                    return;
                }

                $issueId = $issue->id;

                Bus::batch($jobs)
                    ->allowFailures()
                    ->finally(function () use ($issueId): void {
                        NewsletterIssue::query()->whereKey($issueId)->update(['sent_at' => now()]);
                    })
                    ->dispatch();

                $this->components->info('第 '.$issue->number.' 期已派送給 '.count($jobs).' 位訂閱者');
            } catch (Throwable $e) {
                // Without this the issue would look dispatched while nothing was queued.
                NewsletterIssue::query()->whereKey($issue->id)->update(['dispatched_at' => null]);

                throw $e;
            }
        });

        return self::SUCCESS;
    }
}
