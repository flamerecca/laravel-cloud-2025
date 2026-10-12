<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'newsletter_issue_id',
        'newsletter_subscriber_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<NewsletterIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(NewsletterIssue::class, 'newsletter_issue_id');
    }

    /**
     * @return BelongsTo<NewsletterSubscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscriber::class, 'newsletter_subscriber_id');
    }
}
