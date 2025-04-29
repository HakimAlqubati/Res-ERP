<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;

class UserTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Super Admin',
                'code' => UserType::SUPER_ADMIN,
                'level' => 1,
                'scope' => 'all',
                'description' => 'Full access to the system.',
            ],
            [
                'name' => 'System Manager',
                'code' => UserType::SYSTEM_MANAGER,
                'level' => 2,
                'scope' => 'all',
                'description' => 'Manage the system at a technical level.',
            ],
            [
                'name' => 'Branch Manager',
                'code' => UserType::BRANCH_MANAGER,
                'level' => 3,
                'scope' => 'branch',
                'description' => 'Manages a branch and its employees.',
            ],
            [
                'name' => 'Store Manager',
                'code' => UserType::STORE_MANAGER,
                'level' => 3,
                'scope' => 'store',
                'description' => 'Manages a store operations.',
            ],
            [
                'name' => 'Supervisor',
                'code' => UserType::SUPERVISOR,
                'level' => 4,
                'scope' => 'branch',
                'description' => 'Supervises team activities.',
            ],
            [
                'name' => 'Finance Manager',
                'code' => UserType::FINANCE_MANAGER,
                'level' => 3,
                'scope' => 'all',
                'description' => 'Manages financial operations.',
            ],
            [
                'name' => 'Branch User',
                'code' => UserType::BRANCH_USER,
                'level' => 5,
                'scope' => 'branch',
                'description' => 'Regular branch employee.',
            ],
            [
                'name' => 'Driver',
                'code' => UserType::DRIVER,
                'level' => 5,
                'scope' => 'branch',
                'description' => 'Responsible for deliveries and logistics.',
            ],
            [
                'name' => 'Stuff',
                'code' => UserType::STUFF,
                'level' => 5,
                'scope' => 'branch',
                'description' => 'General staff member.',
            ],
            [
                'name' => 'Attendance',
                'code' => UserType::ATTENDANCE,
                'level' => 5,
                'scope' => 'branch',
                'description' => 'Attendance tracking user.',
            ],
            [
                'name' => 'Maintenance Manager',
                'code' => UserType::MAINTENANCE_MANAGER,
                'level' => 4,
                'scope' => 'branch',
                'description' => 'Responsible for maintenance operations.',
            ],
        ];

        foreach ($types as $type) {
            UserType::firstOrCreate(
                ['code' => $type['code']], // unique
                $type
            );
        }
    }
}
