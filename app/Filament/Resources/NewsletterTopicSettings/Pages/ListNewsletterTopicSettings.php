<?php

namespace App\Filament\Resources\NewsletterTopicSettings\Pages;

use App\Filament\Resources\NewsletterTopicSettings\NewsletterTopicSettingResource;
use App\Models\NewsletterTopicSetting;
use Filament\Resources\Pages\ListRecords;

class ListNewsletterTopicSettings extends ListRecords
{
    protected static string $resource = NewsletterTopicSettingResource::class;

    public function mount(): void
    {
        NewsletterTopicSetting::syncWithTopics();

        parent::mount();
    }
}
