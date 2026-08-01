<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('name')
                            ->placeholder('—'),
                        TextEntry::make('phone')
                            ->placeholder('—')
                            ->copyable(),
                        TextEntry::make('email')
                            ->placeholder('—')
                            ->copyable(),
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state) => $state === 'registered' ? 'success' : 'gray')
                            ->formatStateUsing(fn (string $state) => $state === 'registered' ? 'Registered account' : 'Guest checkout'),
                        TextEntry::make('orders_count')
                            ->label('Orders'),
                        TextEntry::make('lifetime_value')
                            ->label('Lifetime value')
                            ->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                        TextEntry::make('created_at')
                            ->label('Customer since')
                            ->dateTime(),
                    ]),
            ]);
    }
}
