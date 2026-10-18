<?php

namespace App\Filament\Resources\NewsletterTopicSettings;

use App\Filament\Resources\NewsletterTopicSettings\Pages\ListNewsletterTopicSettings;
use App\Filament\Resources\NewsletterTopicSettings\Tables\NewsletterTopicSettingsTable;
use App\Models\NewsletterTopicSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NewsletterTopicSettingResource extends Resource
{
    protected static ?string $model = NewsletterTopicSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $modelLabel = '系列寄送間隔';

    protected static ?string $pluralModelLabel = '系列寄送間隔';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return NewsletterTopicSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterTopicSettings::route('/'),
        ];
    }
}
