<?php

namespace App\Filament\Resources\NewsletterTopicSettings\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class NewsletterTopicSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('topic')
                    ->label('電子報')
                    ->badge(),
                TextInputColumn::make('interval_days')
                    ->label('每幾天寄一封')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:1']),
            ]);
    }
}
