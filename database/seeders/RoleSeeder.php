<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        collect(['customer', 'super-admin', 'order-manager', 'catalog-manager'])
            ->each(fn (string $role) => Role::findOrCreate($role));
    }
}
