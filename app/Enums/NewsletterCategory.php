<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NewsletterCategory: string implements HasLabel
{
    case Laravel = 'laravel';
    case Kotlin = 'kotlin';
    case Python = 'python';

    public function getLabel(): string
    {
        return $this->title();
    }

    public function title(): string
    {
        return match ($this) {
            self::Laravel => 'Laravel 生態系',
            self::Kotlin => 'Kotlin 後端',
            self::Python => 'Python 後端',
        };
    }

    /**
     * The topics of this category, in the order they are declared.
     *
     * @return array<int, NewsletterTopic>
     */
    public function topics(): array
    {
        return array_values(array_filter(
            NewsletterTopic::cases(),
            fn (NewsletterTopic $topic): bool => $topic->category() === $this,
        ));
    }
}
