<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    private string $role;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->role = $data['role'];
        unset($data['role']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole($this->role);

        AuditLog::record(auth()->user(), 'staff.created', $this->record, ['role' => $this->role]);
    }
}
