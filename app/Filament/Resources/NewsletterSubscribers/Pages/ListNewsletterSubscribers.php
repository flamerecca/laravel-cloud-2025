<?php

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Jobs\StoreNewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Goes through the same double opt-in flow as the public form: the subscriber
            // only counts once they click the confirmation link themselves.
            Action::make('addSubscriber')
                ->label('新增訂閱者')
                ->icon(Heroicon::OutlinedPlus)
                ->modalDescription('會寄出確認信，對方點擊信中的確認連結後才算訂閱成功。')
                ->schema([
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required(),
                    Select::make('topic')
                        ->label('電子報')
                        ->options(NewsletterTopic::class)
                        ->placeholder('一般電子報'),
                ])
                ->action(function (array $data): void {
                    StoreNewsletterSubscriber::dispatch(
                        $data['email'],
                        now(),
                        // Enum-backed options give us a NewsletterTopic, or null for the general newsletter.
                        $data['topic'] ?? null,
                    );

                    Notification::make()
                        ->title("已寄出確認信給 {$data['email']}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
