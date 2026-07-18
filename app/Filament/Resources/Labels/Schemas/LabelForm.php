<?php

namespace App\Filament\Resources\Labels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LabelForm
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
                Select::make('color')
                    ->options([
                        'danger' => 'Red',
                        'warning' => 'Orange',
                        'success' => 'Green',
                        'primary' => 'Purple',
                        'info' => 'Blue',
                        'gray' => 'Gray',
                    ])
                    ->default('gray')
                    ->required(),
                TextInput::make('icon')
                    ->label('Icon (emoji)')
                    ->maxLength(10),
            ]);
    }
}
