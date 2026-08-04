<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Exports\CustomerExporter;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(CustomerExporter::class),
        ];
    }
}
