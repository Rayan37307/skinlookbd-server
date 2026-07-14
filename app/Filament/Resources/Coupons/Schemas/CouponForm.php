<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (string $state) => Str::upper($state))
                    ->helperText('Stored in uppercase.'),
                Select::make('discount_type')
                    ->options([
                        'percent' => 'Percent',
                        'flat' => 'Flat (BDT)',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('discount_value')
                    ->label(fn (callable $get) => $get('discount_type') === 'flat' ? 'Discount value (BDT)' : 'Discount value (%)')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('min_order_value')
                    ->label('Minimum order value (BDT)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('max_uses')
                    ->label('Max total uses')
                    ->helperText('Leave blank for unlimited.')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('max_uses_per_user')
                    ->label('Max uses per customer')
                    ->helperText('Leave blank for unlimited.')
                    ->numeric()
                    ->minValue(1),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('expires_at')
                    ->after('starts_at'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
