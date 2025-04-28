<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'import_order',
            'export_order',
            'approve_order',
            // اضف أي صلاحيات إضافية تحتاجها هنا
        ];

        foreach ($permissions as $permissionName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }
}
