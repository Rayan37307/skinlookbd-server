<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic information')
                    ->schema([
                        TextEntry::make('name')
                            ->placeholder('-'),
                        TextEntry::make('code')
                            ->placeholder('Automatic'),
                        TextEntry::make('method')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === 'automatic' ? 'Automatic' : 'Coupon code'),
                    ])
                    ->columns(2),
                Section::make('Discount configuration')
                    ->schema([
                        TextEntry::make('discount_type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'percent' => 'Percentage',
                                'flat' => 'Fixed amount',
                                'free_delivery' => 'Free delivery',
                                default => $state,
                            }),
                        TextEntry::make('discount_value')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('max_discount')
                            ->label('Maximum discount')
                            ->numeric()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Conditions & eligibility')
                    ->schema([
                        TextEntry::make('min_order_value')
                            ->numeric(),
                        IconEntry::make('first_order_only')
                            ->boolean(),
                        TextEntry::make('products.name')
                            ->label('Products')
                            ->badge()
                            ->listWithLineBreaks()
                            ->placeholder('All products'),
                        TextEntry::make('categories.name')
                            ->label('Categories')
                            ->badge()
                            ->listWithLineBreaks()
                            ->placeholder('-'),
                        TextEntry::make('brands.name')
                            ->label('Brands')
                            ->badge()
                            ->listWithLineBreaks()
                            ->placeholder('-'),
                        TextEntry::make('tags.name')
                            ->label('Tags')
                            ->badge()
                            ->listWithLineBreaks()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Usage & combination')
                    ->schema([
                        TextEntry::make('max_uses')
                            ->label('Maximum uses')
                            ->numeric()
                            ->placeholder('Unlimited'),
                        TextEntry::make('max_uses_per_user')
                            ->label('Uses per customer')
                            ->numeric()
                            ->placeholder('Unlimited'),
                        IconEntry::make('allow_combination')
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Schedule & status')
                    ->schema([
                        TextEntry::make('starts_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('expires_at')
                            ->dateTime()
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Timestamps')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
