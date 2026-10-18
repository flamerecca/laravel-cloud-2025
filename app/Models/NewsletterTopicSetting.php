<?php

namespace App\Models;

use App\Enums\NewsletterTopic;
use Database\Factories\NewsletterTopicSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @use HasFactory<NewsletterTopicSettingFactory> */
class NewsletterTopicSetting extends Model
{
    use HasFactory;

    public const DEFAULT_INTERVAL_DAYS = 7;

    protected $fillable = [
        'topic',
        'interval_days',
    ];

    protected function casts(): array
    {
        return [
            'topic' => NewsletterTopic::class,
            'interval_days' => 'integer',
        ];
    }

    /**
     * Days between two issues for a series subscriber of the given topic.
     */
    public static function intervalDaysFor(NewsletterTopic $topic): int
    {
        return static::query()->where('topic', $topic->value)->value('interval_days') ?? self::DEFAULT_INTERVAL_DAYS;
    }

    /**
     * Make sure every topic has a row, so the admin list shows all of them.
     */
    public static function syncWithTopics(): void
    {
        foreach (NewsletterTopic::cases() as $topic) {
            static::query()->firstOrCreate(
                ['topic' => $topic->value],
                ['interval_days' => self::DEFAULT_INTERVAL_DAYS],
            );
        }
    }
}
