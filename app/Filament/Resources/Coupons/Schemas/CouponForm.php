<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Models\Coupon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('1. Basic information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Promotion name')
                            ->required()
                            ->maxLength(255),
                        Select::make('method')
                            ->label('Method')
                            ->options([
                                'code' => 'Coupon code',
                                'automatic' => 'Automatic',
                            ])
                            ->default('code')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state === 'automatic') {
                                    $set('code', null);
                                }
                            })
                            ->helperText('Automatic promotions apply at checkout without a code.'),
                        TextInput::make('code')
                            ->label('Coupon code')
                            ->required(fn (Get $get): bool => ($get('method') ?? 'code') === 'code')
                            ->visible(fn (Get $get): bool => ($get('method') ?? 'code') === 'code')
                            ->dehydratedWhenHidden()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state): ?string => blank($state) ? null : Str::upper($state))
                            ->helperText('Customers enter this at checkout. Stored in uppercase.'),
                    ])
                    ->columns(2),

                Section::make('2. Discount configuration')
                    ->schema([
                        Select::make('discount_type')
                            ->label('Discount type')
                            ->options([
                                'percent' => 'Percentage',
                                'flat' => 'Fixed amount (BDT)',
                                'free_delivery' => 'Free delivery',
                            ])
                            ->default('percent')
                            ->required()
                            ->live(),
                        TextInput::make('discount_value')
                            ->label(fn (Get $get): string => $get('discount_type') === 'flat'
                                ? 'Discount value (BDT)'
                                : 'Discount value (%)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn (Get $get): ?int => $get('discount_type') === 'percent' ? 100 : null)
                            ->required(fn (Get $get): bool => $get('discount_type') !== 'free_delivery')
                            ->visible(fn (Get $get): bool => $get('discount_type') !== 'free_delivery')
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn ($state, Get $get) => $get('discount_type') === 'free_delivery' ? 0 : $state),
                        TextInput::make('max_discount')
                            ->label('Maximum discount (BDT)')
                            ->helperText('Caps percentage discounts. Leave blank for no cap.')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Get $get): bool => $get('discount_type') === 'percent')
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn ($state, Get $get) => $get('discount_type') === 'percent' ? $state : null),
                    ])
                    ->columns(2),

                Section::make('3. Conditions & eligibility')
                    ->schema([
                        TextInput::make('min_order_value')
                            ->label('Minimum order (BDT)')
                            ->helperText('Measured against the eligible items only when the coupon is restricted below.')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Select::make('applies_to')
                            ->label('Applies to')
                            ->options([
                                'order' => 'Entire order',
                                'items' => 'Specific products, categories, brands or tags',
                            ])
                            ->default('order')
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, ?Coupon $record): void {
                                if ($record) {
                                    $component->state($record->hasRestrictions() ? 'items' : 'order');
                                }
                            })
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state === 'order') {
                                    $set('products', []);
                                    $set('categories', []);
                                    $set('brands', []);
                                    $set('tags', []);
                                }
                            }),
                        Toggle::make('first_order_only')
                            ->label('First order only')
                            ->helperText('Requires a signed-in customer with no previous orders.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Products, categories, brands & tags')
                    ->description('Only matching items are discounted, and the minimum order is measured against them. Leave everything empty to apply to the whole order.')
                    ->schema([
                        Select::make('products')
                            ->label('Products')
                            ->relationship('products', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->dehydratedWhenHidden(),
                        Select::make('categories')
                            ->label('Categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->dehydratedWhenHidden()
                            ->helperText('Matches products assigned directly to these categories.'),
                        Select::make('brands')
                            ->label('Brands')
                            ->relationship('brands', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->dehydratedWhenHidden(),
                        Select::make('tags')
                            ->label('Tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->dehydratedWhenHidden(),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('applies_to') === 'items')
                    ->collapsible(),

                Section::make('4. Usage & combination')
                    ->schema([
                        TextInput::make('max_uses')
                            ->label('Maximum uses')
                            ->helperText('Total redemptions. Leave blank for unlimited.')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('max_uses_per_user')
                            ->label('Uses per customer')
                            ->helperText('Leave blank for unlimited.')
                            ->numeric()
                            ->minValue(1),
                        Toggle::make('allow_combination')
                            ->label('Allow combination')
                            ->helperText('An automatic promotion only stacks on a code coupon when both allow combination.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('5. Schedule & status')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Start date')
                            ->seconds(false),
                        DateTimePicker::make('expires_at')
                            ->label('Expiry date')
                            ->seconds(false)
                            ->after('starts_at'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
