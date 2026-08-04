<?php

namespace App\Filament\Widgets;

use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockTable extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Low stock';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ProductVariant::with('product')
                ->where('stock_quantity', '<=', 5)
                ->orderBy('stock_quantity'))
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product'),
                TextColumn::make('sku')
                    ->label('SKU'),
                TextColumn::make('size_label')
                    ->label('Size'),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : 'warning'),
            ])
            ->paginated(false);
    }
}
