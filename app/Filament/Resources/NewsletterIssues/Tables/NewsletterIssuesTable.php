<?php

namespace App\Filament\Resources\NewsletterIssues\Tables;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsletterIssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                TextColumn::make('topic')
                    ->label('電子報')
                    ->badge(),
                TextColumn::make('number')
                    ->label('期號')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('標題')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('scheduled_at')
                    ->label('預計寄送時間')
                    ->dateTime(timezone: 'Asia/Taipei')
                    ->sortable()
                    ->placeholder('尚未排程'),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->state(fn (NewsletterIssue $record): string => $record->status())
                    ->color(fn (string $state): string => match ($state) {
                        '已寄出' => 'success',
                        '派送中' => 'warning',
                        '已排程' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('deliveries_count')
                    ->label('已寄出人數')
                    ->counts('deliveries'),
            ])
            ->filters([
                SelectFilter::make('topic')
                    ->label('電子報')
                    ->options(NewsletterTopic::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (NewsletterIssue $record): bool => ! $record->isDispatched()),
            ]);
    }
}
