<?php

use App\Models\CustomTenantModel;
use App\Tasks\Tenant\SwitchTenantDatabaseTask;
use App\TenantFinders\CustomTenantFinder;
use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Actions\MigrateTenantAction;
use Spatie\Multitenancy\Tasks\PrefixCacheTask;

return [
    /*
     * The canonical central (landlord) domain and allowed central hostnames.
     * Always read via config('multitenancy.central_domain') / config('multitenancy.central_domains')
     * so `php artisan config:cache` works reliably in production.
     */
    'central_domain' => env('CENTRAL_DOMAIN', 'localhost'),

    'central_domains' => array_values(array_unique(array_filter(array_map(
        'trim',
        explode(',', env('CENTRAL_DOMAINS', implode(',', array_filter([
            env('CENTRAL_DOMAIN', 'localhost'),
            env('APP_ENV', 'production') !== 'production' ? 'localhost' : null,
            env('APP_ENV', 'production') !== 'production' ? '127.0.0.1' : null,
            env('APP_ENV', 'production') === 'local' ? 'workbench.test' : null,
        ]))))
    )))),

    /*
     * Optional secret token to inspect inactive tenants during maintenance.
     * Disabled (null) by default — never hardcode a literal bypass string in source code.
     */
    'dev_bypass_secret' => env('TENANT_DEV_BYPASS_SECRET'),

    /*
     * This class is responsible for determining which tenant should be current
     * for the given request.
     *
     * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
     *
     */
    'tenant_finder' => CustomTenantFinder::class,


    /*
     * These fields are used by tenant:artisan command to match one or more tenant.
     */
    'tenant_artisan_search_fields' => [
        'id',
    ],

    /*
     * These tasks will be performed when switching tenants.
     *
     * A valid task is any class that implements Spatie\Multitenancy\Tasks\SwitchTenantTask
     */
    'switch_tenant_tasks' => [
        PrefixCacheTask::class,
        // \Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
        // \Spatie\Multitenancy\Tasks\SwitchRouteCacheTask::class,
        SwitchTenantDatabaseTask::class,
    ],

    /*
     * This class is the model used for storing configuration on tenants.
     *
     * It must extend `Spatie\Multitenancy\Models\Tenant::class` or
     * implement `Spatie\Multitenancy\Contracts\IsTenant::class` interface
     */
    'tenant_model' => CustomTenantModel::class,

    /*
     * If there is a current tenant when dispatching a job, the id of the current tenant
     * will be automatically set on the job. When the job is executed, the set
     * tenant on the job will be made current.
     */
    'queues_are_tenant_aware_by_default' => true,

    /*
     * The connection name to reach the tenant database.
     *
     * Set to `null` to use the default connection.
     */
    'tenant_database_connection_name' => 'tenant',

    /*
     * The connection name to reach the landlord database.
     */
    'landlord_database_connection_name' => 'landlord',

    /*
     * This key will be used to associate the current tenant in the context
     */
    'current_tenant_context_key' => 'tenantId',

    /*
     * This key will be used to bind the current tenant in the container.
     */
    'current_tenant_container_key' => 'currentTenant',

    /**
     * Set it to `true` if you like to cache the tenant(s) routes
     * in a shared file using the `SwitchRouteCacheTask`.
     */
    'shared_routes_cache' => false,

    /*
     * You can customize some of the behavior of this package by using your own custom action.
     * Your custom action should always extend the default one.
     */
    'actions' => [
        'make_tenant_current_action' => MakeTenantCurrentAction::class,
        'forget_current_tenant_action' => ForgetCurrentTenantAction::class,
        'make_queue_tenant_aware_action' => MakeQueueTenantAwareAction::class,
        'migrate_tenant' => MigrateTenantAction::class,
    ],

    /*
     * Jobs tenant aware even if these don't implement the TenantAware interface.
     */
    'tenant_aware_jobs' => [
        // App\Jobs\GenerateUnauditedStocktakeJob::class,
        // Illuminate\Notifications\SendQueuedNotifications::class,

    ],

    /*
     * Jobs not tenant aware even if these don't implement the NotTenantAware interface.
     */
    'not_tenant_aware_jobs' => [
        // ...
        // \App\Modules\Stock\Jobs\SyncPriceOnNewStockEntryJob::class,
        // \App\Modules\Stock\Jobs\SyncProductCurrentBatchPriceJob::class,
    ],
];
