<?php

namespace App\Filament\Imports;

use App\Models\ShortUrl;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ShortUrlImporter extends Importer
{
    protected static ?string $model = ShortUrl::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('slug')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('original_url')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('customer_id')
                ->numeric()
                ->rules(['integer']),
        ];
    }

    public function resolveRecord(): ShortUrl
    {
        return ShortUrl::firstOrNew([
            'slug' => $this->data['slug'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your short url import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
