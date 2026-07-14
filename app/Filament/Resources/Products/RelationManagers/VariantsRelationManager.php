<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\InventoryLog;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('size_label')
                    ->label('Size')
                    ->required()
                    ->maxLength(255),
                TextInput::make('price_override')
                    ->label('Price override (BDT)')
                    ->helperText('Leave blank to use the product\'s base price.')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('৳'),
                TextInput::make('stock_quantity')
                    ->label('Initial stock')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('size_label')
                    ->label('Size'),
                TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('in_stock')
                    ->label('In stock')
                    ->boolean()
                    ->state(fn ($record) => $record->inStock()),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('adjustStock')
                    ->label('Adjust stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        TextInput::make('change_quantity')
                            ->label('Change (+/-)')
                            ->helperText('Positive to add stock, negative to remove.')
                            ->numeric()
                            ->required(),
                        Select::make('reason')
                            ->options([
                                'restock' => 'Restock',
                                'adjustment' => 'Adjustment',
                                'return' => 'Return',
                            ])
                            ->required(),
                        Textarea::make('note'),
                    ])
                    ->action(function ($record, array $data) {
                        if ($record->stock_quantity + $data['change_quantity'] < 0) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot adjust stock')
                                ->body('This adjustment would result in negative stock.')
                                ->send();

                            return;
                        }

                        $record->increment('stock_quantity', $data['change_quantity']);

                        $log = new InventoryLog([
                            'change_quantity' => $data['change_quantity'],
                            'reason' => $data['reason'],
                            'note' => $data['note'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                        $log->productVariant()->associate($record);
                        $log->save();

                        Notification::make()->success()->title('Stock adjusted')->send();
                    }),
                EditAction::make()
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('size_label')
                            ->label('Size')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('price_override')
                            ->label('Price override (BDT)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('৳'),
                    ]),
                DeleteAction::make()
                    ->action(function (Model $record, DeleteAction $action) {
                        try {
                            $record->delete();
                        } catch (QueryException) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete variant')
                                ->body('This variant has existing orders.')
                                ->send();

                            return;
                        }

                        $action->success();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
