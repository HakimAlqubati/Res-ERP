<?php

namespace App\Models;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Tenant;

class CustomTenantModel extends Tenant
{
    // use UsesLandlordConnection;
    protected $table = 'tenants';
    protected $fillable = ['name', 'domain', 'database'];
    protected static function booted()
    {
        // static::creating(fn(CustomTenantModel $model) => $model->createDatabase());
    }

 
  
    public function createDatabase_()
    {
        // $dbName = 'tenant_' . $tenantName;
        $dbName = $this->database;
        // Check if the database exists
        $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);

        if ($databaseExists) {
            return "Database {$dbName} already exists.";
        }

        // Create the database
        DB::statement("CREATE DATABASE {$dbName}");

        // Set the database connection to the new database
        config(['database.connections.tenant.database' => $dbName]);

        // Run migrations for the new tenant database
        // Artisan::call('migrate', [
        //     '--database' => 'tenant',
        //     '--path' => 'database/migrations/tenant',
        //     '--force' => true,
        // ]);

        // Artisan::call('tenants:artisan', [
        //     'command' => 'migrate --database=tenant'
        // ]);

        // Artisan::call('tenants:artisan', [
        //     'artisanCommand' => 'migrate', // Provide the "artisanCommand" explicitly
        //     '--database' => 'tenant',     // Pass additional options as needed
        // ]);

        // Artisan::call('cache:clear');

        return "Database {$dbName} created and migrations applied successfully.";
        // add logic to create database
    }

    // public function tasks()
    // {
    //     return $this->hasMany(Task::class);
    // }
}
