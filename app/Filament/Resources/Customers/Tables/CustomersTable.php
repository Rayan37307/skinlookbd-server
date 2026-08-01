<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('email')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => $state === 'registered' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state) => $state === 'registered' ? 'Registered' : 'Guest'),
                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lifetime_value')
                    ->label('Lifetime value')
                    ->formatStateUsing(fn (int $state) => '৳'.number_format($state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Since')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'registered' => 'Registered',
                        'guest' => 'Guest',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
