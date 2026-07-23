<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('category.parent.name')
                    ->label('Category')
                    ->getStateUsing(fn ($record) => ($record->category?->parent ?? $record->category)?->name),
                TextEntry::make('category.name')
                    ->label('Subcategory'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextEntry::make('brand.name')
                    ->label('Brand')
                    ->placeholder('-'),
                TextEntry::make('short_description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('description')
                    ->label('Full description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('ingredients')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('base_price')
                    ->label('Regular price')
                    ->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                TextEntry::make('sale_price')
                    ->label('Sale price')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?int $state) => $state !== null ? '৳'.number_format($state) : null),
                TextEntry::make('cost_price')
                    ->label('Cost price (hidden from customers)')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?int $state) => $state !== null ? '৳'.number_format($state) : null),
                IconEntry::make('stock_status')
                    ->label('In stock')
                    ->getStateUsing(fn ($record) => $record->isInStock())
                    ->boolean(),
                IconEntry::make('free_shipping')
                    ->boolean(),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'archived' => 'danger',
                    }),
                TextEntry::make('skinTypes.name')
                    ->label('Skin types')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('labels.name')
                    ->label('Marketing labels')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('relatedProducts.name')
                    ->label('Related products')
                    ->badge()
                    ->placeholder('-'),
                Section::make('SEO')
                    ->columns(2)
                    ->components([
                        TextEntry::make('meta_title')
                            ->placeholder('-'),
                        TextEntry::make('focus_keyword')
                            ->placeholder('-'),
                        TextEntry::make('meta_description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('canonical_url')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
