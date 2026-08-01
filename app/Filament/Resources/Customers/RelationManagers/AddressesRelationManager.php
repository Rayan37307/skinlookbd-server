<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\Customer;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    /**
     * Guests never have a persisted Address row — their shipping details only ever exist as a
     * snapshot on each order, shown in the Orders tab instead — so hide this tab for them.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Customer && ! $ownerRecord->isGuest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address_line1')
            ->columns([
                TextColumn::make('label')
                    ->placeholder('—'),
                TextColumn::make('recipient_name'),
                TextColumn::make('phone'),
                TextColumn::make('address_line1')
                    ->label('Address'),
                TextColumn::make('city'),
                IconColumn::make('is_default')
                    ->boolean(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
