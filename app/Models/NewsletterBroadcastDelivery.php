<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterBroadcastDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'newsletter_broadcast_id',
        'email',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<NewsletterBroadcast, $this>
     */
    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(NewsletterBroadcast::class, 'newsletter_broadcast_id');
    }
}
