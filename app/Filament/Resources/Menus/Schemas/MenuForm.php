<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Models\Category;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent item')
                    ->helperText('Leave blank for a top-level nav item.')
                    ->relationship(
                        'parent',
                        'label',
                        fn ($query, $record) => $record ? $query->whereKeyNot($record->id) : $query,
                    )
                    ->searchable(),
                TextInput::make('label')
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'category' => 'Category link',
                        'custom_url' => 'Custom URL',
                        'button' => 'Special button',
                    ])
                    ->default('custom_url')
                    ->live()
                    ->required(),
                Select::make('target')
                    ->label('Category')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'slug'))
                    ->searchable()
                    ->visible(fn (Get $get) => $get('type') === 'category')
                    ->required(fn (Get $get) => $get('type') === 'category'),
                TextInput::make('target')
                    ->label('URL')
                    ->helperText('Relative path (e.g. /products?tag=new-arrival) or a full URL.')
                    ->maxLength(255)
                    ->visible(fn (Get $get) => $get('type') !== 'category')
                    ->required(fn (Get $get) => $get('type') !== 'category'),
                Select::make('style')
                    ->options([
                        'highlight' => 'Highlight (e.g. a Sale badge)',
                    ])
                    ->live()
                    ->helperText('Optional visual treatment for the storefront to key off of.'),
                ColorPicker::make('highlight_color')
                    ->label('Pill background color')
                    ->helperText('Background color of the highlight pill. Defaults to the brand color if left blank.')
                    ->visible(fn (Get $get) => $get('style') === 'highlight'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
