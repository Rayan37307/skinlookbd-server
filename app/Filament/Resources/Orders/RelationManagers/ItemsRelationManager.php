<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->modifyQueryUsing(fn ($query) => $query->with('product.images'))
            ->columns([
                ImageColumn::make('product_image')
                    ->label('')
                    ->getStateUsing(fn ($record) => $record->product?->images->first()?->url())
                    ->square()
                    ->size(40),
                TextColumn::make('product_name')
                    ->label('Product')
                    ->url(fn ($record) => $record->product?->slug
                        ? 'https://skinlookbd.com/product/'.$record->product->slug
                        : null)
                    ->openUrlInNewTab(),
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
