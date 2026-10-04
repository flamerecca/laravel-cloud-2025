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

    protected $connection = 'google-sheets';

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'email',
        'subscribed_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /**
     * Query the google-sheets tab for the given topic, or the general list when no topic is given.
     *
     * @return Builder<static>
     */
    public static function forTopic(?NewsletterTopic $topic): Builder
    {
        $model = new static;

        if ($topic) {
            $model->setTable($topic->table());
        }

        return $model->newQuery();
    }
}
