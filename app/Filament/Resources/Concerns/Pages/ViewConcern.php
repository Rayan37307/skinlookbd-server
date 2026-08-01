<?php

namespace App\Filament\Resources\Concerns\Pages;

use App\Filament\Resources\Concerns\ConcernResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewConcern extends ViewRecord
{
    protected static string $resource = ConcernResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
