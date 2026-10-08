<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use App\Enums\NewsletterTopic;
use App\Jobs\StoreNewsletterSubscriber;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NewsletterSubscribersTable
{
    /**
     * Filter value standing in for the general newsletter, whose topic is stored as null.
     */
    public const GENERAL = 'general';

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('subscribed_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('topic')
                    ->label('電子報')
                    ->badge()
                    ->placeholder('一般電子報'),
                TextColumn::make('confirmed_at')
                    ->label('確認時間')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('尚未確認'),
                TextColumn::make('subscribed_at')
                    ->label('訂閱時間')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('更新時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('topic')
                    ->label('電子報')
                    ->options([
                        self::GENERAL => '一般電子報',
                        ...collect(NewsletterTopic::cases())->mapWithKeys(fn (NewsletterTopic $topic) => [$topic->value => $topic->title()]),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        null, '' => $query,
                        self::GENERAL => $query->whereNull('topic'),
                        default => $query->where('topic', $data['value']),
                    }),
                TernaryFilter::make('confirmed_at')
                    ->label('確認狀態')
                    ->nullable()
                    ->trueLabel('已確認')
                    ->falseLabel('尚未確認'),
            ])
            ->recordActions([
                Action::make('resendConfirmation')
                    ->label('重寄確認信')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn (NewsletterSubscriber $record): bool => ! $record->isConfirmed())
                    ->requiresConfirmation()
                    ->action(function (NewsletterSubscriber $record): void {
                        StoreNewsletterSubscriber::dispatch($record->email, $record->subscribed_at ?? now(), $record->topic);

                        Notification::make()
                            ->title("已重新寄送確認信給 {$record->email}")
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
