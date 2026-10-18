<?php

namespace App\Models;

use App\Enums\NewsletterTopic;
use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/** @use HasFactory<NewsletterSubscriberFactory> */
class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'topic',
        'subscribed_at',
        'confirmed_at',
        'unsubscribed_at',
        'series_started_at',
        'series_next_at',
        'series_start_number',
    ];

    protected function casts(): array
    {
        return [
            'topic' => NewsletterTopic::class,
            'subscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'series_started_at' => 'datetime',
            'series_next_at' => 'datetime',
            'series_start_number' => 'integer',
        ];
    }

    public function isInSeries(): bool
    {
        return $this->series_started_at !== null;
    }

    /**
     * Mark the address as confirmed. Topic subscribers who confirm start the series from the beginning,
     * with the first issue due right away. The general newsletter has no issues, so it stays out.
     */
    public function confirm(): void
    {
        $attributes = ['confirmed_at' => now(), 'unsubscribed_at' => null];

        if ($this->topic !== null && ! $this->isInSeries()) {
            $attributes += ['series_started_at' => now(), 'series_next_at' => now()];
        }

        $this->update($attributes);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /**
     * Subscribers who confirmed their address and have not unsubscribed since.
     *
     * @param  Builder<static>  $query
     */
    public function scopeDeliverable(Builder $query): void
    {
        $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    /**
     * Subscribers who signed up but have not clicked the confirmation link yet.
     *
     * @param  Builder<static>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    /**
     * Subscribers who used the unsubscribe link.
     *
     * @param  Builder<static>  $query
     */
    public function scopeUnsubscribed(Builder $query): void
    {
        $query->whereNotNull('unsubscribed_at');
    }

    /**
     * Subscribers who receive the topic as a series.
     *
     * @param  Builder<static>  $query
     */
    public function scopeInSeries(Builder $query): void
    {
        $query->whereNotNull('series_started_at');
    }

    /**
     * Series subscribers whose next issue may go out now.
     *
     * @param  Builder<static>  $query
     */
    public function scopeSeriesDue(Builder $query): void
    {
        $query->deliverable()->inSeries()->where('series_next_at', '<=', now());
    }

    /**
     * The lowest published issue at or above the start number that this subscriber has not received in its current round.
     */
    public function nextIssue(): ?NewsletterIssue
    {
        if ($this->topic === null) {
            return null;
        }

        return NewsletterIssue::query()
            ->published()
            ->where('topic', $this->topic->value)
            // A model that was just created in this request has not loaded the column default yet.
            ->where('number', '>=', $this->series_start_number ?? 1)
            ->whereDoesntHave('deliveries', fn (Builder $query) => $query
                ->where('newsletter_subscriber_id', $this->id)
                ->whereColumn('newsletter_deliveries.send_round', 'newsletter_issues.send_round'))
            ->orderBy('number')
            ->first();
    }

    public function seriesProgress(): string
    {
        if (! $this->isInSeries() || $this->topic === null) {
            return '廣播';
        }

        $published = NewsletterIssue::query()->published()->where('topic', $this->topic->value);

        $received = (clone $published)
            ->whereHas('deliveries', fn (Builder $query) => $query
                ->where('newsletter_subscriber_id', $this->id)
                ->whereColumn('newsletter_deliveries.send_round', 'newsletter_issues.send_round'))
            ->count();

        return "已收到 {$received} / {$published->count()} 期";
    }

    public function nextIssueLabel(): string
    {
        if (! $this->isInSeries()) {
            return '廣播';
        }

        $issue = $this->nextIssue();

        return $issue ? "第 {$issue->number} 期" : '已追上';
    }

    public function status(): string
    {
        return match (true) {
            $this->unsubscribed_at !== null => '已退訂',
            $this->confirmed_at !== null => '已確認',
            default => '尚未確認',
        };
    }

    /**
     * The signed link in every newsletter that stops further deliveries.
     */
    public function unsubscribeUrl(): string
    {
        return URL::signedRoute(
            'newsletter.unsubscribe',
            array_filter(['email' => $this->email, 'topic' => $this->topic?->value]),
        );
    }

    /**
     * Query the subscribers of the given topic, or of the general newsletter when no topic is given.
     * Records created through this query get the same topic.
     *
     * @return Builder<static>
     */
    public static function forTopic(?NewsletterTopic $topic): Builder
    {
        return static::query()->withAttributes(['topic' => $topic?->value]);
    }
}
