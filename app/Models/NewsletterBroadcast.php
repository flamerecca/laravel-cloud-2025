<?php

namespace App\Models;

use App\Enums\NewsletterTopic;
use App\Jobs\SendNewsletterBroadcast;
use Database\Factories\NewsletterBroadcastFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Bus;
use Throwable;

/** @use HasFactory<NewsletterBroadcastFactory> */
class NewsletterBroadcast extends Model
{
    use HasFactory;

    public const GENERAL_AUDIENCE = 'general';

    protected $fillable = [
        'title',
        'summary',
        'body',
        'audiences',
        'dispatched_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'audiences' => 'array',
            'dispatched_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<NewsletterBroadcastDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterBroadcastDelivery::class);
    }

    /**
     * The audiences an admin can tick: the general newsletter and every topic.
     *
     * @return array<string, string>
     */
    public static function audienceOptions(): array
    {
        return [self::GENERAL_AUDIENCE => '一般電子報']
            + collect(NewsletterTopic::cases())->mapWithKeys(fn (NewsletterTopic $topic) => [$topic->value => $topic->title()])->all();
    }

    /**
     * The addresses this broadcast goes to: the deliverable subscriptions of the ticked audiences, series
     * subscribers included, counted once per address however many subscriptions it has.
     * A broadcast without ticked audiences goes to everyone.
     *
     * @return array<int, string>
     */
    public function recipientEmails(): array
    {
        $audiences = $this->audiences ?: null;

        return NewsletterSubscriber::query()
            ->deliverable()
            ->when($audiences !== null, fn (Builder $query) => $query->where(function (Builder $query) use ($audiences): void {
                $topics = array_values(array_diff($audiences, [self::GENERAL_AUDIENCE]));

                $query->whereIn('topic', $topics)
                    ->when(in_array(self::GENERAL_AUDIENCE, $audiences, true), fn (Builder $query) => $query->orWhereNull('topic'));
            }))
            ->pluck('email')
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Claim the broadcast for sending. Only the caller that wins this update may queue it,
     * so a double click or a second admin cannot send it twice.
     */
    public function claimForSending(): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->whereNull('dispatched_at')
            ->update(['dispatched_at' => now()]) === 1;
    }

    /**
     * Queue one mail per address. Returns the number of addresses queued, or null when
     * the broadcast had already been dispatched.
     */
    public function dispatchToAll(): ?int
    {
        if (! $this->claimForSending()) {
            return null;
        }

        try {
            $jobs = array_map(
                fn (string $email) => new SendNewsletterBroadcast($this->id, $email),
                $this->recipientEmails(),
            );

            if ($jobs === []) {
                $this->update(['sent_at' => now()]);

                return 0;
            }

            $broadcastId = $this->id;

            Bus::batch($jobs)
                ->allowFailures()
                ->finally(function () use ($broadcastId): void {
                    static::query()->whereKey($broadcastId)->update(['sent_at' => now()]);
                })
                ->dispatch();

            return count($jobs);
        } catch (Throwable $e) {
            // Without this the broadcast would look dispatched while nothing was queued.
            static::query()->whereKey($this->id)->update(['dispatched_at' => null]);

            throw $e;
        }
    }

    public function isDispatched(): bool
    {
        return $this->dispatched_at !== null;
    }

    public function status(): string
    {
        return match (true) {
            $this->sent_at !== null => '已寄出',
            $this->dispatched_at !== null => '派送中',
            default => '草稿',
        };
    }
}
