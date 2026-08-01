<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'gray',
                        'confirmed', 'processing' => 'info',
                        'shipped' => 'warning',
                        'delivered' => 'success',
                        'cancelled', 'returned' => 'danger',
                    }),
                TextColumn::make('shipping_city')
                    ->label('City')
                    ->placeholder('—'),
                TextColumn::make('total')
                    ->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime(),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn ($record) => OrderResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }
}
