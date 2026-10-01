<?php

namespace Modules\Manufacturing\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'production.view' => ['manufacturing', 'View production orders'],
            'production.create' => ['manufacturing', 'Create BOMs and production orders'],
            'production.release' => ['manufacturing', 'Release and close production orders'],
            'production.execute' => ['manufacturing', 'Start and complete production runs'],
            'bom.view' => ['manufacturing', 'View bills of materials'],
            'bom.manage' => ['manufacturing', 'Create and edit bills of materials'],
        ];

        foreach ($permissions as $name => [$module, $description]) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    // 'module' => $module,
                    'description' => $description,
                ]
            );
        }
    }
}
