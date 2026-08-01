<?php

namespace App\Filament\Resources\Concerns\Pages;

use App\Filament\Resources\Concerns\ConcernResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConcern extends CreateRecord
{
    protected static string $resource = ConcernResource::class;
}
