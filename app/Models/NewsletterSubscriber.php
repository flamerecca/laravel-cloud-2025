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
    ];

    protected function casts(): array
    {
        return [
            'topic' => NewsletterTopic::class,
            'subscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
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
