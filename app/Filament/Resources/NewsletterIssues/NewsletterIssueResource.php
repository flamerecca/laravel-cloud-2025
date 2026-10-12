<?php

namespace App\Filament\Resources\NewsletterIssues;

use App\Filament\Resources\NewsletterIssues\Pages\CreateNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\EditNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\ListNewsletterIssues;
use App\Filament\Resources\NewsletterIssues\Schemas\NewsletterIssueForm;
use App\Filament\Resources\NewsletterIssues\Tables\NewsletterIssuesTable;
use App\Models\NewsletterIssue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NewsletterIssueResource extends Resource
{
    protected static ?string $model = NewsletterIssue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = '電子報期數';

    protected static ?string $pluralModelLabel = '電子報期數';

    public static function form(Schema $schema): Schema
    {
        return NewsletterIssueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NewsletterIssuesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterIssues::route('/'),
            'create' => CreateNewsletterIssue::route('/create'),
            'edit' => EditNewsletterIssue::route('/{record}/edit'),
        ];
    }
}
