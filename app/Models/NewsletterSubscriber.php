<?php

namespace App\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @use HasFactory<NewsletterSubscriberFactory> */
class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $connection = 'google-sheets';

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'email',
        'subscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
        ];
    }
}
