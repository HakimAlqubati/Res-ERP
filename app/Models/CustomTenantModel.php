<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Tenant;

class CustomTenantModel extends Tenant
{
    use SoftDeletes;
    // use UsesLandlordConnection;
    protected $table = 'tenants';
    protected $fillable = ['name', 'domain', 'database', 'active', 'database_created'];
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

    public static function setDatabaseConnection($databaseName)
    {
        // Dynamically update the database connection to point to the specified database
        config(['database.connections.mysql.database' => $databaseName]);

        // Reconnect with the updated database connection
        DB::reconnect('mysql');
    }

    public function importDatabase($record)
    {
        DB::beginTransaction();
        try {
            $sql = 'WorkbenchRomansiah.sql';
            $sql = Storage::path($sql);
            $sql = file_get_contents($sql);
            CustomTenantModel::setDatabaseConnection($record->database);

            DB::unprepared($sql);
            DB::commit();
            showSuccessNotifiMessage('Done');
        } catch (\Throwable $th) {
            DB::rollBack();
            showWarningNotifiMessage($th->getMessage());
            throw $th;
        }
    }
    public function importDatabaseByForm($database, $sqlFile)
    {
        DB::beginTransaction();
        try {
            $sql = Storage::path($sqlFile);
            $sql = file_get_contents($sql);
            CustomTenantModel::setDatabaseConnection($database);

            DB::unprepared($sql);
            DB::commit();
            showSuccessNotifiMessage('Done');
        } catch (\Throwable $th) {
            DB::rollBack();
            showWarningNotifiMessage($th->getMessage());
            throw $th;
        }
    }
}
