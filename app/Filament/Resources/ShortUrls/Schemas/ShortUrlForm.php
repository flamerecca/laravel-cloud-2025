<?php

namespace App\Filament\Resources\ShortUrls\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ShortUrlForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required(),
                Textarea::make('original_url')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('customer_id')
                    ->numeric(),
            ]);
    }
}
