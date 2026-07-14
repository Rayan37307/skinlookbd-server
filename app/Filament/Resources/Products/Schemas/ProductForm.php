<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('brand')
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('ingredients')
                    ->columnSpanFull(),
                TextInput::make('base_price')
                    ->label('Base price (BDT)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('৳'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'archived' => 'Archived',
                    ])
                    ->required()
                    ->default('draft'),
                Select::make('skinTypes')
                    ->label('Skin types')
                    ->relationship('skinTypes', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }
}
