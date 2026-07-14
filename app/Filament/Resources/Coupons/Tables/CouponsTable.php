<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('discount_value')
                    ->label('Value')
                    ->formatStateUsing(fn ($state, $record) => $record->discount_type === 'flat' ? '৳'.number_format($state) : $state.'%'),
                TextColumn::make('min_order_value')
                    ->label('Min order')
                    ->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                TextColumn::make('max_uses')
                    ->label('Max uses')
                    ->placeholder('Unlimited'),
                TextColumn::make('max_uses_per_user')
                    ->label('Max per customer')
                    ->placeholder('Unlimited'),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
