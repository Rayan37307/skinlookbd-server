<?php

namespace App\Filament\Exports;

use App\Models\Order;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class OrderExporter extends Exporter
{
    protected static ?string $model = Order::class;

    /**
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('order_number')
                ->label('Order #'),
            ExportColumn::make('user.name')
                ->label('Customer'),
            ExportColumn::make('recipient_name')
                ->label('Recipient'),
            ExportColumn::make('recipient_phone')
                ->label('Phone'),
            ExportColumn::make('status'),
            ExportColumn::make('payment_method')
                ->label('Payment method'),
            ExportColumn::make('subtotal'),
            ExportColumn::make('discount_total')
                ->label('Discount'),
            ExportColumn::make('shipping_charge')
                ->label('Shipping'),
            ExportColumn::make('total'),
            ExportColumn::make('shipping_address_line1')
                ->label('Address line 1'),
            ExportColumn::make('shipping_address_line2')
                ->label('Address line 2'),
            ExportColumn::make('shipping_city')
                ->label('City'),
            ExportColumn::make('shipping_postal_code')
                ->label('Postal code'),
            ExportColumn::make('created_at')
                ->label('Placed at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your order export has completed and '.number_format($export->successful_rows).' '.
            str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
