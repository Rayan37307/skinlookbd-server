<?php

namespace App\Filament\Resources\Concerns\Pages;

use App\Filament\Resources\Concerns\ConcernResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConcerns extends ListRecords
{
    protected static string $resource = ConcernResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
