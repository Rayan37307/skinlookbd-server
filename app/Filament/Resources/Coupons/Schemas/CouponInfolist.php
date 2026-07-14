<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('discount_type'),
                TextEntry::make('discount_value')
                    ->numeric(),
                TextEntry::make('min_order_value')
                    ->numeric(),
                TextEntry::make('max_uses')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_uses_per_user')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('starts_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('expires_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
