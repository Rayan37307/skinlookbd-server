<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
