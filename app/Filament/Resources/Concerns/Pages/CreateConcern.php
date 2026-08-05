<?php

namespace App\Filament\Resources\Concerns\Pages;

use App\Filament\Resources\Concerns\ConcernResource;
use App\Filament\Support\ImageOrUrlField;
use Filament\Resources\Pages\CreateRecord;

class CreateConcern extends CreateRecord
{
    protected static string $resource = ConcernResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ImageOrUrlField::combine($data, 'image');
    }
}
