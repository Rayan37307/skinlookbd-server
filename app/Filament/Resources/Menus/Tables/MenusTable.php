<?php

namespace App\Filament\Resources\Menus\Tables;

use App\Models\Menu;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('parent.label')
                    ->label('Parent')
                    ->placeholder('— top level —'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('target')
                    ->limit(40)
                    ->placeholder('-'),
                TextColumn::make('style')
                    ->badge()
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('sort_order'),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Parent item')
                    ->options(fn () => Menu::whereNull('parent_id')->orderBy('label')->pluck('label', 'id')),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
