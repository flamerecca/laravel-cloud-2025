<?php

namespace App\Filament\Resources\NewsletterIssues\Pages;

use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNewsletterIssue extends EditRecord
{
    protected static string $resource = NewsletterIssueResource::class;

    /**
     * The form was locked when the page rendered, but the issue may have been dispatched since.
     * Content changes after that would reach later recipients only, so nothing is saved.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->fresh()?->isDispatched()) {
            Notification::make()
                ->title('這一期已開始派送，變更沒有儲存')
                ->warning()
                ->send();

            return [];
        }

        return $data;
    }
}
