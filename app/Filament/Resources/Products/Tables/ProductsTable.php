<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('base_price')
                    ->label('Price')
                    ->formatStateUsing(fn ($record) => $record->isOnSale()
                        ? '<s>৳'.number_format($record->base_price).'</s> ৳'.number_format($record->sale_price)
                        : '৳'.number_format($record->base_price))
                    ->html()
                    ->sortable(),
                TextColumn::make('variants_count')
                    ->label('Variants')
                    ->counts('variants'),
                IconColumn::make('stock_status')
                    ->label('In stock')
                    ->getStateUsing(fn ($record) => $record->isInStock())
                    ->boolean(),
                TextColumn::make('labels.name')
                    ->label('Labels')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'archived' => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'archived' => 'Archived',
                    ]),
                SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name')
                    ->searchable(),
                SelectFilter::make('tags')
                    ->label('Tag')
                    ->relationship('tags', 'name'),
                SelectFilter::make('labels')
                    ->label('Marketing label')
                    ->relationship('labels', 'name'),
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
                EditAction::make(),
                DeleteAction::make()
                    ->action(function ($record, DeleteAction $action) {
                        if ($record->hasActiveOrders()) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete product')
                                ->body('This product has active orders and cannot be deleted.')
                                ->send();

                            return;
                        }

                        $record->delete();
                        $action->success();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records, DeleteBulkAction $action) {
                            $deletable = $records->reject(fn (Product $record) => $record->hasActiveOrders());
                            $blockedCount = $records->count() - $deletable->count();

                            if ($blockedCount > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title('Some products were not deleted')
                                    ->body("{$blockedCount} product(s) have active orders and cannot be deleted.")
                                    ->send();
                            }

                            $deletable->each->delete();
                            $action->success();
                        }),
                ]),
            ]);
    }
}
