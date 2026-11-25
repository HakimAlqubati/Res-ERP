<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Central\Resources\Settings\Pages\CreateSetting;
use App\Filament\Central\Resources\Settings\SettingResource as CentralSettingResource;
use App\Filament\Central\Resources\TenantInvoices\Pages\CreateTenantInvoice;
use App\Filament\Resources\SettingResource as AdminSettingResource;
use App\Models\CentralSetting;
use App\Models\CustomTenantModel;
use App\Models\TenantInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CentralSettingResourceTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['landlord'];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'multitenancy.central_domain' => 'workbench.test',
            'multitenancy.central_domains' => ['workbench.test', 'localhost', '127.0.0.1'],
        ]);

        app(IsTenant::class)::forgetCurrent();
    }

    protected function tearDown(): void
    {
        app(IsTenant::class)::forgetCurrent();

        parent::tearDown();
    }

    protected function getCentralSuperAdmin(): User
    {
        $user = User::on('landlord')
            ->withoutGlobalScopes()
            ->whereHas('roles', fn ($q) => $q->where('roles.id', 1))
            ->where('active', 1)
            ->firstOrFail();

        $user->load('roles');

        return $user;
    }

    protected function makeInMemoryStaffUser(): User
    {
        $user = new User();
        $user->id = 999998;
        $user->name = 'Staff User';
        $user->email = 'staff-only@example.com';
        $user->active = 1;

        $role = new Role();
        $role->id = 8;
        $role->name = 'Branch Staff';

        $user->setRelation('roles', new Collection([$role]));
        $user->setRelation('employee', null);

        return $user;
    }

    public function test_central_settings_table_exists_on_landlord_with_required_columns(): void
    {
        $schema = Schema::connection('landlord');

        $this->assertTrue($schema->hasTable('central_settings'));
        $this->assertTrue($schema->hasColumns('central_settings', [
            'id',
            'key',
            'value',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_central_setting_resource_is_registered_only_in_central_panel(): void
    {
        $centralResources = filament()->getPanel('central')->getResources();
        $adminResources = filament()->getPanel('admin')->getResources();

        $this->assertContains(CentralSettingResource::class, $centralResources);
        $this->assertNotContains(CentralSettingResource::class, $adminResources);
        $this->assertContains(AdminSettingResource::class, $adminResources);
        $this->assertNotContains(AdminSettingResource::class, $centralResources);
        $this->assertNull(CentralSettingResource::getNavigationGroup());
        $this->assertSame(5, CentralSettingResource::getNavigationSort());
    }

    public function test_central_setting_model_handles_defaults_zero_values_booleans_and_landlord_connection(): void
    {
        CentralSetting::query()->delete();

        $this->assertSame(CentralSetting::DEFAULT_INVOICE_WARNING_DAYS, CentralSetting::getDefaultInvoiceWarningDays());
        $this->assertFalse(CentralSetting::shouldSuspendTenantOnOverdueInvoice());

        CentralSetting::setSetting(CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS, 0);
        CentralSetting::setSetting(CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE, true);

        $this->assertSame(0, CentralSetting::getDefaultInvoiceWarningDays());
        $this->assertTrue(CentralSetting::shouldSuspendTenantOnOverdueInvoice());

        CentralSetting::setSetting(CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE, false);
        $this->assertFalse(CentralSetting::shouldSuspendTenantOnOverdueInvoice());

        // Still reads from landlord connection even when a tenant context is active
        $tenant = CustomTenantModel::firstOrFail();
        CentralSetting::setSetting(CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS, 18);
        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

        $this->assertSame(18, CentralSetting::getDefaultInvoiceWarningDays());
    }

    public function test_central_settings_page_loads_defaults_validates_and_persists_changes(): void
    {
        CentralSetting::query()->delete();

        $admin = $this->getCentralSuperAdmin();
        $this->actingAs($admin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        // Loads defaults on initial visit
        Livewire::test(CreateSetting::class)
            ->assertOk()
            ->assertFormFieldExists(CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS)
            ->assertFormFieldExists(CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE)
            ->assertFormSet([
                CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS => CentralSetting::DEFAULT_INVOICE_WARNING_DAYS,
                CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE => false,
            ])
            ->fillForm([
                CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS => -5,
            ])
            ->call('create')
            ->assertHasFormErrors([CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS]);

        // Rejects values over 365
        Livewire::test(CreateSetting::class)
            ->fillForm([
                CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS => 400,
            ])
            ->call('create')
            ->assertHasFormErrors([CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS]);

        // Saves valid values
        Livewire::test(CreateSetting::class)
            ->fillForm([
                CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS => 21,
                CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Done');

        $this->assertSame(21, CentralSetting::getDefaultInvoiceWarningDays());
        $this->assertTrue(CentralSetting::shouldSuspendTenantOnOverdueInvoice());

        // Reloads saved values on subsequent mount and allows updating back to false and 0
        Livewire::test(CreateSetting::class)
            ->assertFormSet([
                CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS => 21,
                CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE => true,
            ])
            ->fillForm([
                CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS => 0,
                CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(0, CentralSetting::getDefaultInvoiceWarningDays());
        $this->assertFalse(CentralSetting::shouldSuspendTenantOnOverdueInvoice());
    }

    public function test_tenant_invoice_creation_uses_default_warning_days_from_central_settings(): void
    {
        $admin = $this->getCentralSuperAdmin();
        $tenant = CustomTenantModel::firstOrFail();
        $initialActive = (bool) $tenant->active;

        CentralSetting::setSetting(CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS, 15);
        CentralSetting::setSetting(CentralSetting::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE, true);

        $this->actingAs($admin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        // Filament create form pre-fills warning_days from CentralSetting
        Livewire::test(CreateTenantInvoice::class)
            ->assertOk()
            ->assertFormSet([
                'warning_days' => 15,
            ])
            ->fillForm([
                'invoice_number' => 'MAN-SETTINGS-001',
                'tenant_id' => $tenant->id,
                'amount' => 350.00,
                'invoice_date' => '2026-11-25',
                'due_date' => '2026-12-25',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $formInvoice = TenantInvoice::latest('id')->firstOrFail();
        $this->assertSame(15, $formInvoice->warning_days);

        // Programmatic creation without explicit warning_days also reads from CentralSetting
        CentralSetting::setSetting(CentralSetting::KEY_DEFAULT_INVOICE_WARNING_DAYS, 7);

        $programmaticInvoice = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 450.00,
            'invoice_date' => today()->subDays(10),
            'due_date' => today()->subDays(2),
        ]);

        $this->assertSame(7, $programmaticInvoice->warning_days);

        // Toggle has no enforcement effect yet: tenant active flag remains unchanged
        $tenant->refresh();
        $this->assertSame($initialActive, (bool) $tenant->active);
    }

    public function test_central_settings_routes_enforce_central_super_admin_and_domain_isolation(): void
    {
        $admin = $this->getCentralSuperAdmin();
        $staff = $this->makeInMemoryStaffUser();

        $this->actingAs($admin, 'web')
            ->get('http://workbench.test/central/settings')
            ->assertOk();

        $this->actingAs($staff, 'web')
            ->get('http://workbench.test/central/settings')
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->get('http://sultan.workbench.test/central/settings')
            ->assertNotFound();

        $tenant = new CustomTenantModel([
            'name' => 'Test Tenant',
            'domain' => 'sultan.workbench.test',
            'database' => 'workbench_db',
            'active' => true,
        ]);
        $tenant->id = 999999;

        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

        $this->actingAs($admin, 'web')
            ->get('http://workbench.test/central/settings')
            ->assertNotFound();
    }
}
