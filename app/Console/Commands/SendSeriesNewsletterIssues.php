<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterIssue;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterTopicSetting;
use Illuminate\Console\Command;

class SendSeriesNewsletterIssues extends Command
{
    protected $signature = 'newsletter:send-series';

    protected $description = 'Queue the next issue for every series subscriber whose turn has come';

    public function handle(): int
    {
        $queued = 0;

        // By id, not by offset: claiming a subscriber changes the query's own filter.
        foreach (NewsletterSubscriber::query()->seriesDue()->lazyById() as $subscriber) {
            $issue = $subscriber->nextIssue();

            if (! $issue) {
                continue;
            }

            // Only the process that wins this update queues the issue for this subscriber.
            $claimed = NewsletterSubscriber::query()
                ->whereKey($subscriber->id)
                ->where('series_next_at', '<=', now())
                ->update(['series_next_at' => now()->addDays(NewsletterTopicSetting::intervalDaysFor($subscriber->topic))]);

            if ($claimed !== 1) {
                continue;
            }

            SendNewsletterIssue::dispatch($issue->id, $subscriber->id, $issue->send_round);
            $queued++;
        }

        $this->components->info("已派送 {$queued} 封系列電子報");

        return self::SUCCESS;
    }
}
