<?php

namespace App\Filament\Resources\Concerns\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ConcernForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('concerns')
                    ->helperText('Optional. Falls back to the gradient colors below when left blank.'),
                ColorPicker::make('color_from')
                    ->label('Gradient start color')
                    ->default('#e8c6a0'),
                ColorPicker::make('color_to')
                    ->label('Gradient end color')
                    ->default('#c98d5b'),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
