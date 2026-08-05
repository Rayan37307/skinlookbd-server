<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Filament\Support\ImageOrUrlField;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
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
                Grid::make(2)
                    ->schema([
                        ...ImageOrUrlField::make(
                            'image',
                            'banners',
                            label: 'Desktop image',
                            helperText: 'Recommended 1920×500 (wide aspect ratio) — cropped to fill the banner.',
                            required: true,
                        ),
                        ...ImageOrUrlField::make(
                            'mobile_image',
                            'banners',
                            label: 'Mobile image (optional)',
                            helperText: 'A narrower, taller crop (roughly 4:3) for phone screens. Falls back to the desktop image if left empty.',
                        ),
                    ]),
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
