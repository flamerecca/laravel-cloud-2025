<?php

namespace App\Filament\Resources\NewsletterBroadcasts\Actions;

use App\Models\NewsletterBroadcast;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class SendNewsletterBroadcastAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'send';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('寄送給所有訂閱者')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->visible(fn (NewsletterBroadcast $record): bool => ! $record->isDispatched())
            ->requiresConfirmation()
            ->modalHeading('寄送這則公告？')
            ->modalDescription(fn (NewsletterBroadcast $record): string => '公告會寄給 '.count($record->recipientEmails()).' 個 email，同一個 email 訂閱多個電子報也只會收到一封。寄出後無法再寄第二次。')
            ->modalSubmitActionLabel('寄送')
            ->action(function (NewsletterBroadcast $record): void {
                $count = $record->dispatchToAll();

                if ($count === null) {
                    Notification::make()
                        ->title('這則公告已經寄送過了')
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title("公告已派送給 {$count} 個 email")
                    ->success()
                    ->send();
            });
    }
}
