<?php

namespace App\Filament\Resources\NewsletterBroadcasts\Pages;

use App\Filament\Resources\NewsletterBroadcasts\Actions\SendNewsletterBroadcastAction;
use App\Filament\Resources\NewsletterBroadcasts\NewsletterBroadcastResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNewsletterBroadcast extends EditRecord
{
    protected static string $resource = NewsletterBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendNewsletterBroadcastAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->getRecord()->isDispatched()),
        ];
    }

    /**
     * The form was locked when the page rendered, but the broadcast may have been dispatched since.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->fresh()?->isDispatched()) {
            Notification::make()
                ->title('這則公告已開始派送，變更沒有儲存')
                ->warning()
                ->send();

            return [];
        }

        return $data;
    }
}
