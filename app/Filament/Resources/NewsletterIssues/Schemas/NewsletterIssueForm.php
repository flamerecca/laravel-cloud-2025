<?php

namespace App\Filament\Resources\NewsletterIssues\Schemas;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class NewsletterIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        $locked = fn (?NewsletterIssue $record): bool => $record?->isDispatched() ?? false;

        return $schema->components([
            Select::make('topic')
                ->label('電子報')
                ->options(NewsletterTopic::class)
                ->required()
                ->disabled($locked)
                ->live(),
            TextInput::make('number')
                ->label('期號')
                ->numeric()
                ->minValue(1)
                ->required()
                ->disabled($locked)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('topic', $get('topic')),
                ),
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
            DateTimePicker::make('scheduled_at')
                ->label('預計寄送時間')
                ->timezone('Asia/Taipei')
                ->helperText('到了這個時間，系統會自動寄給已確認的訂閱者，留空代表尚未排程。')
                ->disabled($locked),
        ]);
    }
}
