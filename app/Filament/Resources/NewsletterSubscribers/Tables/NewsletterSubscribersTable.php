<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use App\Enums\NewsletterTopic;
use App\Jobs\StoreNewsletterSubscriber;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->state(fn (NewsletterSubscriber $record): string => $record->status())
                    ->color(fn (string $state): string => match ($state) {
                        '已確認' => 'success',
                        '已退訂' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('confirmed_at')
                    ->label('確認時間')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('尚未確認'),
                TextColumn::make('series_progress')
                    ->label('進度')
                    ->state(fn (NewsletterSubscriber $record): string => $record->seriesProgress()),
                TextColumn::make('next_issue')
                    ->label('下一封')
                    ->state(fn (NewsletterSubscriber $record): string => $record->nextIssueLabel()),
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
                Action::make('confirmSubscription')
                    ->label('直接確認訂閱')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (NewsletterSubscriber $record): bool => ! $record->isConfirmed())
                    ->requiresConfirmation()
                    ->modalDescription('不經確認信，直接把這位訂閱者標記為已確認，之後會開始收到電子報。')
                    ->action(function (NewsletterSubscriber $record): void {
                        $record->confirm();

                        Notification::make()
                            ->title("已確認 {$record->email} 的訂閱")
                            ->success()
                            ->send();
                    }),
                Action::make('adjustNextIssue')
                    ->label('調整下一封')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->visible(fn (NewsletterSubscriber $record): bool => $record->isInSeries())
                    ->modalDescription('設定之後，下一封只會從所選的期數開始找。所選期數之前還沒收到的期數會被略過，已經收過的期數不會重寄。寄送時間仍依原本的間隔。')
                    ->fillForm(fn (NewsletterSubscriber $record): array => ['number' => $record->series_start_number])
                    ->schema(fn (NewsletterSubscriber $record): array => [
                        Select::make('number')
                            ->label('下一封從哪一期開始')
                            ->options(fn (): array => NewsletterIssue::query()
                                ->where('topic', $record->topic->value)
                                ->orderBy('number')
                                ->get()
                                ->mapWithKeys(fn (NewsletterIssue $issue): array => [$issue->number => "第 {$issue->number} 期：{$issue->title}"])
                                ->all())
                            ->required(),
                    ])
                    ->action(function (NewsletterSubscriber $record, array $data): void {
                        $record->update(['series_start_number' => (int) $data['number']]);

                        Notification::make()
                            ->title("{$record->email} 的下一封已調整為第 {$data['number']} 期起")
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('confirmSubscriptions')
                        ->label('直接確認訂閱')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('只會確認尚未確認的訂閱者，已確認或已退訂的不受影響。')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $confirmed = $records
                                ->filter(fn (NewsletterSubscriber $record): bool => ! $record->isConfirmed())
                                ->each(fn (NewsletterSubscriber $record) => $record->confirm())
                                ->count();

                            Notification::make()
                                ->title("已確認 {$confirmed} 位訂閱者")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
