<?php

namespace App\Filament\Resources\NewsletterBroadcasts\Tables;

use App\Filament\Resources\NewsletterBroadcasts\Actions\SendNewsletterBroadcastAction;
use App\Models\NewsletterBroadcast;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NewsletterBroadcastsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('標題')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('audiences')
                    ->label('收件對象')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => NewsletterBroadcast::audienceOptions()[$state] ?? $state)
                    ->placeholder('全部'),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->state(fn (NewsletterBroadcast $record): string => $record->status())
                    ->color(fn (string $state): string => match ($state) {
                        '已寄出' => 'success',
                        '派送中' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('deliveries_count')
                    ->label('已寄出人數')
                    ->counts('deliveries'),
                TextColumn::make('dispatched_at')
                    ->label('派送時間')
                    ->dateTime(timezone: 'Asia/Taipei')
                    ->placeholder('尚未寄送'),
            ])
            ->recordActions([
                SendNewsletterBroadcastAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (NewsletterBroadcast $record): bool => ! $record->isDispatched()),
            ]);
    }
}
