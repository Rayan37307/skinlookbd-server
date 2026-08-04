<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Exports\OrderExporter;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(OrderExporter::class),
        ];
    }
}
