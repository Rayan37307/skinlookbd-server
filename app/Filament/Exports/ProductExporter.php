<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    /**
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('sku'),
            ExportColumn::make('categories.name')
                ->label('Categories'),
            ExportColumn::make('brand.name')
                ->label('Brand'),
            ExportColumn::make('base_price')
                ->label('Regular price'),
            ExportColumn::make('sale_price'),
            ExportColumn::make('stock_quantity')
                ->label('Stock'),
            ExportColumn::make('status'),
            ExportColumn::make('created_at')
                ->label('Added at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed and '.number_format($export->successful_rows).' '.
            str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
