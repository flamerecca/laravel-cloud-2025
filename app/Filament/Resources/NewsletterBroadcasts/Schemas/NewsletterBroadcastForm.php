<?php

namespace App\Filament\Resources\NewsletterBroadcasts\Schemas;

use App\Models\NewsletterBroadcast;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class NewsletterBroadcastForm
{
    public static function configure(Schema $schema): Schema
    {
        $locked = fn (?NewsletterBroadcast $record): bool => $record?->isDispatched() ?? false;

        return $schema->components([
            CheckboxList::make('audiences')
                ->label('收件對象')
                ->options(NewsletterBroadcast::audienceOptions())
                ->default(array_keys(NewsletterBroadcast::audienceOptions()))
                ->required()
                ->minItems(1)
                ->columns(2)
                ->helperText('只會寄給已確認、未退訂的訂閱者，同一個 email 在勾選範圍內只寄一封。')
                ->disabled($locked)
                ->columnSpanFull(),
            TextInput::make('title')
                ->label('標題')
                ->required()
                ->maxLength(255)
                ->disabled($locked)
                ->columnSpanFull(),
            Textarea::make('summary')
                ->label('摘要')
                ->rows(2)
                ->disabled($locked)
                ->columnSpanFull(),
            MarkdownEditor::make('body')
                ->label('內文')
                ->required()
                ->disabled($locked)
                ->columnSpanFull(),
        ]);
    }
}
