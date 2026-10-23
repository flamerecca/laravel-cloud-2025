<?php

namespace App\Filament\Resources\NewsletterBroadcasts;

use App\Filament\Resources\NewsletterBroadcasts\Pages\CreateNewsletterBroadcast;
use App\Filament\Resources\NewsletterBroadcasts\Pages\EditNewsletterBroadcast;
use App\Filament\Resources\NewsletterBroadcasts\Pages\ListNewsletterBroadcasts;
use App\Filament\Resources\NewsletterBroadcasts\Schemas\NewsletterBroadcastForm;
use App\Filament\Resources\NewsletterBroadcasts\Tables\NewsletterBroadcastsTable;
use App\Models\NewsletterBroadcast;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NewsletterBroadcastResource extends Resource
{
    protected static ?string $model = NewsletterBroadcast::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = '電子報公告';

    protected static ?string $pluralModelLabel = '電子報公告';

    public static function form(Schema $schema): Schema
    {
        return NewsletterBroadcastForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NewsletterBroadcastsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterBroadcasts::route('/'),
            'create' => CreateNewsletterBroadcast::route('/create'),
            'edit' => EditNewsletterBroadcast::route('/{record}/edit'),
        ];
    }
}
