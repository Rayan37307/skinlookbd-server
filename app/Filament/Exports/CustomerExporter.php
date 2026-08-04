<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CustomerExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    /**
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('phone'),
            ExportColumn::make('email'),
            ExportColumn::make('type')
                ->formatStateUsing(fn (string $state) => $state === 'registered' ? 'Registered' : 'Guest'),
            ExportColumn::make('orders_count')
                ->label('Orders'),
            ExportColumn::make('lifetime_value')
                ->label('Lifetime value'),
            ExportColumn::make('created_at')
                ->label('Customer since'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your customer export has completed and '.number_format($export->successful_rows).' '.
            str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
