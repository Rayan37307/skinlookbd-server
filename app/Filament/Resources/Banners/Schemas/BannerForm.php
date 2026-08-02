<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->helperText('Used as the image alt text — not shown on the slide itself.')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('image')
                    ->label('Slide image')
                    ->helperText('Recommended 1920×500 (or similar wide aspect ratio) — it\'s cropped to fill the banner on all screen sizes.')
                    ->image()
                    ->disk('public')
                    ->directory('banners')
                    ->required(),
                TextInput::make('link_url')
                    ->label('Link URL')
                    ->url()
                    ->maxLength(2048),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('expires_at')
                    ->after('starts_at'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
