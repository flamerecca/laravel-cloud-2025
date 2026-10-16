<?php

namespace App\Filament\Resources\NewsletterIssues\Tables;

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterIssues\Actions\MarkNewsletterIssueUnsentAction;
use App\Models\NewsletterIssue;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->placeholder('尚未排程')
                    ->icon(fn (NewsletterIssue $record): ?Heroicon => $record->isDispatched() ? null : Heroicon::OutlinedPencilSquare)
                    ->iconPosition(IconPosition::After)
                    ->action(
                        Action::make('editScheduledAt')
                            ->modalHeading(fn (NewsletterIssue $record): string => "第 {$record->number} 期預計寄送時間")
                            ->modalWidth(Width::Medium)
                            ->visible(fn (NewsletterIssue $record): bool => ! $record->isDispatched())
                            ->fillForm(fn (NewsletterIssue $record): array => ['scheduled_at' => $record->scheduled_at])
                            ->schema([
                                DateTimePicker::make('scheduled_at')
                                    ->label('預計寄送時間')
                                    ->timezone('Asia/Taipei')
                                    ->helperText('到了這個時間，系統會自動寄給已確認的訂閱者，留空代表尚未排程。'),
                            ])
                            ->action(function (NewsletterIssue $record, array $data): void {
                                $record->update(['scheduled_at' => $data['scheduled_at']]);

                                Notification::make()
                                    ->title("第 {$record->number} 期預計寄送時間已儲存")
                                    ->success()
                                    ->send();
                            })
                    ),
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
                    ->counts(['deliveries' => fn (Builder $query): Builder => $query->whereColumn('newsletter_deliveries.send_round', 'newsletter_issues.send_round')]),
            ])
            ->filters([
                SelectFilter::make('topic')
                    ->label('電子報')
                    ->options(NewsletterTopic::class),
            ])
            ->recordActions([
                EditAction::make(),
                MarkNewsletterIssueUnsentAction::make(),
                DeleteAction::make()
                    ->visible(fn (NewsletterIssue $record): bool => ! $record->isDispatched()),
            ]);
    }
}
