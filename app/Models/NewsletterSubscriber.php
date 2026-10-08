<?php

namespace App\Models;

use App\Enums\NewsletterTopic;
use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @use HasFactory<NewsletterSubscriberFactory> */
class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'topic',
        'subscribed_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'topic' => NewsletterTopic::class,
            'subscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
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
