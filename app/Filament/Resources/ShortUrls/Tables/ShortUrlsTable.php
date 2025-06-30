<?php

namespace App\Filament\Resources\ShortUrls\Tables;

use App\Filament\Imports\ShortUrlImporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ImportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShortUrlsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(ShortUrlImporter::class)
            ])
            ->columns([
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('original_url')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
