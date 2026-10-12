<?php

namespace App\Filament\Resources\NewsletterIssues\Actions;

use App\Models\NewsletterIssue;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class MarkNewsletterIssueUnsentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'markUnsent';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('改為未寄出')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('warning')
            ->visible(fn (NewsletterIssue $record): bool => $record->isDispatched())
            ->requiresConfirmation()
            ->modalHeading('把這一期改為未寄出？')
            ->modalDescription('預計寄送時間會清空。重新排程後，所有可寄送的訂閱者都會再收到一次，包含已經收過的人。舊的寄送紀錄會保留。')
            ->modalSubmitActionLabel('改為未寄出')
            ->action(function (NewsletterIssue $record, mixed $livewire): void {
                $record->resetToUnsent();

                if ($livewire instanceof EditRecord) {
                    $livewire->refreshFormData(['scheduled_at']);
                }

                Notification::make()
                    ->title("第 {$record->number} 期已改為未寄出")
                    ->success()
                    ->send();
            });
    }
}
