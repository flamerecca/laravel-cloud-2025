<?php

namespace App\Models;

use App\Enums\NewsletterTopic;
use Database\Factories\NewsletterIssueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @use HasFactory<NewsletterIssueFactory> */
class NewsletterIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic',
        'number',
        'title',
        'summary',
        'body',
        'scheduled_at',
        'dispatched_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'topic' => NewsletterTopic::class,
            'number' => 'integer',
            'scheduled_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<NewsletterDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class);
    }

    /**
     * Issues whose scheduled time has passed and that have not been queued yet.
     *
     * @param  Builder<static>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereNull('dispatched_at');
    }

    /**
     * The subscribers this issue goes to.
     *
     * @return Builder<NewsletterSubscriber>
     */
    public function recipients(): Builder
    {
        return NewsletterSubscriber::forTopic($this->topic)->deliverable();
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
            $this->scheduled_at !== null => '已排程',
            default => '未排程',
        };
    }
}
