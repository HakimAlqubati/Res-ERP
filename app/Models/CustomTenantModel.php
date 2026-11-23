<?php

namespace App\Models;

use Throwable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Tenant;

class CustomTenantModel extends Tenant
{
    use SoftDeletes;
    use UsesLandlordConnection;

    protected $table = 'tenants';
    protected $fillable = ['name', 'domain', 'database', 'active', 'daily_backup_enabled', 'database_created', 'modules'];
    protected $casts = [
        'modules' => 'array',
        'active' => 'boolean',
        'daily_backup_enabled' => 'boolean',
        'database_created' => 'boolean',
    ];

    public static function current(): ?static
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        if (! app()->has($containerKey)) {
            return null;
        }

        $tenant = app($containerKey);

        if ($tenant instanceof static) {
            return $tenant;
        }

        // Safely upgrade base Spatie Tenant instances set by legacy jobs/commands
        if ($tenant instanceof Tenant) {
            $resolved = $tenant->exists
                ? static::find($tenant->getKey())
                : null;

            if (! $resolved) {
                $resolved = new static();
                $resolved->setRawAttributes($tenant->getAttributes(), true);
                $resolved->exists = $tenant->exists;
            }

            app()->instance($containerKey, $resolved);

            return $resolved;
        }

        return null;
    }

    public static function isCentralHost(?string $host = null): bool
    {
        $host = strtolower(trim((string) ($host ?? request()?->getHost() ?? '')));

        if ($host === '') {
            return false;
        }

        $centralDomains = array_map(
            fn ($domain) => strtolower(trim((string) $domain)),
            config('multitenancy.central_domains', [])
        );

        return in_array($host, $centralDomains, true);
    }

    public function hasModule(string $module): bool
    {
        return is_array($this->modules) && in_array($module, $this->modules, true);
    }

    public function scopeForDailyBackup($query)
    {
        return $query->where('active', true)->where('daily_backup_enabled', true);
    }
    protected static function booted()
    {
        // static::creating(fn(CustomTenantModel $model) => $model->createDatabase());
    }


    public const MODULE_HR = 'hr';
    public const MODULE_STOCK = 'stock';

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

    public function importDatabase($tenant)
    {
        DB::beginTransaction();
        try {
            $sql = 'emptyworkbench.sql';
            $sql = Storage::path($sql);
            $sql = file_get_contents($sql);
            CustomTenantModel::setDatabaseConnection($tenant->database);

            DB::unprepared($sql);
            DB::commit();
            showSuccessNotifiMessage('Done');
        } catch (Throwable $th) {
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
        } catch (Throwable $th) {
            DB::rollBack();
            showWarningNotifiMessage($th->getMessage());
            throw $th;
        }
    }

    public static function getModules(): array
    {
        return [
            'stock' => 'Stock',
            'hr' => 'HR',
        ];
    }

    public function getModulesTitlesAttribute(): string
    {
        return is_array($this->modules) ? implode(', ', $this->modules) : 'No Modules Set';
    }
}
