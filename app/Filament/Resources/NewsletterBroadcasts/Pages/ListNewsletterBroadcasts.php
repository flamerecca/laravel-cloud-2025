<?php

namespace App\Filament\Resources\NewsletterBroadcasts\Pages;

use App\Filament\Resources\NewsletterBroadcasts\NewsletterBroadcastResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNewsletterBroadcasts extends ListRecords
{
    protected static string $resource = NewsletterBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
