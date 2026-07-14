<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product'),
                TextColumn::make('sku')
                    ->label('SKU'),
                TextColumn::make('unit_price')
                    ->label('Unit price')
                    ->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                TextColumn::make('quantity')
                    ->numeric(),
                TextColumn::make('line_total')
                    ->label('Line total')
                    ->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
