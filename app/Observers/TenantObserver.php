<?php

namespace App\Observers;

use Exception;
use App\Models\CustomTenantModel as Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     */
    public function created(Tenant $tenant): void
    {
        $this->createDatabase($tenant);
    }

    /**
     * Handle the Tenant "updated" event.
     */
    public function updated(Tenant $tenant): void
    {
        //
    }

    /**
     * Handle the Tenant "deleted" event.
     */
    public function deleted(Tenant $tenant): void
    {
        //
    }

    /**
     * Handle the Tenant "restored" event.
     */
    public function restored(Tenant $tenant): void
    {
        //
    }

    /**
     * Handle the Tenant "force deleted" event.
     */
    public function forceDeleted(Tenant $tenant): void
    {
        //
    }

    public static function createUsers()
    {
        // Step 3: Add default users and assign roles
        DB::table('users')->insert([
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@admin.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Assign roles to users (assuming roles and user-role relationships exist)
        DB::table('model_has_roles')->insert([
            [
                'role_id' => 1, // Admin role
                'model_type' => 'App\Models\User',
                'model_id' => 1, // ID of the admin user
            ],
            [
                'role_id' => 3, // Manager role
                'model_type' => 'App\Models\User',
                'model_id' => 2, // ID of the manager user
            ],
        ]);

    }

    public static function createDatabase($tenant)
    {
        // DB::beginTransaction();
        try {
            // Create the database
            DB::statement("CREATE DATABASE `$tenant->database`");
            // Step 2: Import all SQL files from the needed_tables folder
            $sqlFilesPath = database_path('migrations/needed_tables');
            $sqlFiles = File::files($sqlFilesPath);

            // // Use the newly created database
            // $dbName = $tenant->database;
            // config(['database.connections.tenant.database' => $dbName]);
            // DB::purge('tenant'); // Refresh connection
            // DB::reconnect('tenant'); // Reconnect to the new database
            // foreach ($sqlFiles as $file) {
            //     if (File::extension($file) === 'sql') {
            // $sql = File::get($file);
            // DB::unprepared($sql); // Execute SQL file contents
            //     }
            // }
            // Artisan::call('tenants:artisan', [
            //     'artisanCommand' => 'migrate --database=tenant',
            // ]);
            // static::createUsers();
            // Update the database_created field
            $tenant->update(['database_created' => true]);
            // DB::commit();
        } catch (Exception $e) {
            $tenant->update(['database_created' => false]);
            // Log or handle the error if needed
            Log::error('Database creation failed for tenant ID: ' . $tenant->id . '. Error: ' . $e->getMessage());
            DB::statement("DROP DATABASE IF EXISTS `$tenant->database`");
            // DB::rollBack();
            throw $e;
            // return $e->getMessage();
        }
    }
}
