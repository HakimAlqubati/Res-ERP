<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\SuspendOverdueTenantsCommand;
use App\Models\CentralSetting;
use App\Models\CustomTenantModel;
use App\Models\TenantInvoice;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Contracts\IsTenant;
use Tests\TestCase;

class SuspendOverdueTenantsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['landlord'];

    protected function setUp(): void
    {
        parent::setUp();

        app(IsTenant::class)::forgetCurrent();
    }

    protected function tearDown(): void
    {
        DB::connection('landlord')->disableQueryLog();
        DB::connection('landlord')->flushQueryLog();
        app(IsTenant::class)::forgetCurrent();

        parent::tearDown();
    }

    protected function createTenantWithoutObserver(bool $active = true): CustomTenantModel
    {
        $suffix = strtolower(Str::random(10));

        return CustomTenantModel::withoutEvents(fn (): CustomTenantModel => CustomTenantModel::create([
            'name' => "Test Tenant {$suffix}",
            'domain' => "tenant-{$suffix}.workbench.test",
            'database' => "tenant_test_{$suffix}",
            'active' => $active,
            'daily_backup_enabled' => false,
            'database_created' => false,
            'modules' => [],
        ]));
    }

    public function test_command_is_scheduled_daily_at_midnight_without_overlapping(): void
    {
        $schedule = app(Schedule::class);

        $event = collect($schedule->events())->first(
            fn (Event $event): bool => str_contains((string) $event->command, 'tenants:suspend-overdue')
        );

        $this->assertNotNull($event, 'Expected tenants:suspend-overdue command to be scheduled.');
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(
            config('app.schedule_timezone', config('app.timezone')),
            $event->timezone,
        );
    }

    public function test_command_skips_deactivation_with_single_setting_query_when_setting_is_disabled(): void
    {
        $tenant = $this->createTenantWithoutObserver(active: true);

        TenantInvoice::create([
            'invoice_number' => 'OVERDUE-OFF-' . Str::upper(Str::random(6)),
            'tenant_id' => $tenant->id,
            'amount' => 500.00,
            'invoice_date' => today()->subDays(20),
            'due_date' => today()->subDay(),
            'status' => TenantInvoice::STATUS_UNPAID,
        ]);

        CentralSetting::setSetting(CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE, false);

        $connection = DB::connection('landlord');
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $this->artisan(SuspendOverdueTenantsCommand::class)
            ->expectsOutput('Automatic tenant suspension for overdue invoices is disabled.')
            ->assertSuccessful();

        $queries = $connection->getQueryLog();
        $connection->disableQueryLog();

        $this->assertCount(1, $queries);
        $this->assertTrue((bool) $tenant->fresh()->active);
    }

    public function test_command_deactivates_only_active_tenants_with_unpaid_overdue_invoices_in_a_single_update_query(): void
    {
        // Ensure pre-existing landlord tenants do not distort the exact deactivated count
        CustomTenantModel::query()->where('active', true)->update(['active' => false]);

        $overdueTenant = $this->createTenantWithoutObserver(active: true);
        $dueTodayTenant = $this->createTenantWithoutObserver(active: true);
        $futureInvoiceTenant = $this->createTenantWithoutObserver(active: true);
        $paidOverdueTenant = $this->createTenantWithoutObserver(active: true);
        $cancelledOverdueTenant = $this->createTenantWithoutObserver(active: true);
        $alreadyInactiveTenant = $this->createTenantWithoutObserver(active: false);

        // Multiple unpaid overdue invoices on the same tenant should still count as 1 deactivated tenant
        TenantInvoice::create([
            'invoice_number' => 'OVERDUE-A1-' . Str::upper(Str::random(6)),
            'tenant_id' => $overdueTenant->id,
            'amount' => 300.00,
            'invoice_date' => today()->subDays(30),
            'due_date' => today()->subDays(5),
            'status' => TenantInvoice::STATUS_UNPAID,
        ]);

        TenantInvoice::create([
            'invoice_number' => 'OVERDUE-A2-' . Str::upper(Str::random(6)),
            'tenant_id' => $overdueTenant->id,
            'amount' => 400.00,
            'invoice_date' => today()->subDays(15),
            'due_date' => today()->subDay(),
            'status' => TenantInvoice::STATUS_UNPAID,
        ]);

        // Due today -> not overdue yet
        TenantInvoice::create([
            'invoice_number' => 'DUE-TODAY-' . Str::upper(Str::random(6)),
            'tenant_id' => $dueTodayTenant->id,
            'amount' => 300.00,
            'invoice_date' => today()->subDays(10),
            'due_date' => today(),
            'status' => TenantInvoice::STATUS_UNPAID,
        ]);

        // Future due date -> not overdue
        TenantInvoice::create([
            'invoice_number' => 'FUTURE-' . Str::upper(Str::random(6)),
            'tenant_id' => $futureInvoiceTenant->id,
            'amount' => 300.00,
            'invoice_date' => today(),
            'due_date' => today()->addDays(15),
            'status' => TenantInvoice::STATUS_UNPAID,
        ]);

        // Paid past due date -> must stay active
        TenantInvoice::create([
            'invoice_number' => 'PAID-OLD-' . Str::upper(Str::random(6)),
            'tenant_id' => $paidOverdueTenant->id,
            'amount' => 300.00,
            'invoice_date' => today()->subDays(25),
            'due_date' => today()->subDays(3),
            'status' => TenantInvoice::STATUS_PAID,
            'paid_at' => today()->subDays(4),
        ]);

        // Cancelled past due date -> must stay active
        TenantInvoice::create([
            'invoice_number' => 'CANCEL-OLD-' . Str::upper(Str::random(6)),
            'tenant_id' => $cancelledOverdueTenant->id,
            'amount' => 300.00,
            'invoice_date' => today()->subDays(25),
            'due_date' => today()->subDays(3),
            'status' => TenantInvoice::STATUS_CANCELLED,
        ]);

        // Already inactive tenant with overdue invoice -> should not be counted
        TenantInvoice::create([
            'invoice_number' => 'INACTIVE-OLD-' . Str::upper(Str::random(6)),
            'tenant_id' => $alreadyInactiveTenant->id,
            'amount' => 300.00,
            'invoice_date' => today()->subDays(25),
            'due_date' => today()->subDays(3),
            'status' => TenantInvoice::STATUS_UNPAID,
        ]);

        CentralSetting::setSetting(CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE, true);

        $connection = DB::connection('landlord');
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $this->artisan(SuspendOverdueTenantsCommand::class)
            ->expectsOutput('Deactivated 1 tenant(s) with unpaid overdue invoices.')
            ->assertSuccessful();

        $queries = $connection->getQueryLog();
        $connection->disableQueryLog();

        // 1 query to read central_settings + 1 bulk UPDATE with EXISTS subquery (zero N+1)
        $this->assertCount(2, $queries);

        $this->assertFalse((bool) $overdueTenant->fresh()->active);
        $this->assertTrue((bool) $dueTodayTenant->fresh()->active);
        $this->assertTrue((bool) $futureInvoiceTenant->fresh()->active);
        $this->assertTrue((bool) $paidOverdueTenant->fresh()->active);
        $this->assertTrue((bool) $cancelledOverdueTenant->fresh()->active);
        $this->assertFalse((bool) $alreadyInactiveTenant->fresh()->active);

        // Idempotent second run affects 0 tenants
        $this->artisan(SuspendOverdueTenantsCommand::class)
            ->expectsOutput('Deactivated 0 tenant(s) with unpaid overdue invoices.')
            ->assertSuccessful();
    }
}
