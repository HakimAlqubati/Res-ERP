<?php

namespace Tests\Feature;

use App\Filament\Central\Resources\Tenants\Pages\ListTenants;
use App\Filament\Central\Resources\Tenants\TenantResource;
use App\Http\Middleware\EnsureCentralDomain;
use App\Models\CustomTenantModel;
use App\Models\User;
use App\Policies\TenantPolicy;
use App\Services\Tenancy\TenantBackupService;
use App\TenantFinders\CustomTenantFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Livewire;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CentralPanelAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'multitenancy.central_domain' => 'workbench.test',
            'multitenancy.central_domains' => ['workbench.test', 'localhost', '127.0.0.1'],
            'multitenancy.dev_bypass_secret' => null,
        ]);

        app(IsTenant::class)::forgetCurrent();
    }

    protected function tearDown(): void
    {
        app(IsTenant::class)::forgetCurrent();

        parent::tearDown();
    }

    protected function makeInMemoryUser(int $roleId, bool $active = true, string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->id = 999999;
        $user->name = 'Test User';
        $user->email = $email;
        $user->active = $active ? 1 : 0;

        $role = new Role();
        $role->id = $roleId;
        $role->name = $roleId === 1 ? 'super_admin' : 'staff';

        $user->setRelation('roles', new Collection([$role]));
        $user->setRelation('employee', null);

        return $user;
    }

    protected function makeInMemoryTenant(string $domain = 'client1.workbench.test', bool $active = true): CustomTenantModel
    {
        $tenant = new CustomTenantModel([
            'name' => 'Client 1',
            'domain' => $domain,
            'database' => 'workbench_db',
            'active' => $active,
            'modules' => [CustomTenantModel::MODULE_HR, CustomTenantModel::MODULE_STOCK],
        ]);
        $tenant->id = 999999;

        return $tenant;
    }

    public function test_guest_can_view_central_login_on_central_domain(): void
    {
        $this->get('http://workbench.test/central/login')
            ->assertOk();
    }

    public function test_guest_is_redirected_to_central_login_when_visiting_central_dashboard(): void
    {
        $this->get('http://workbench.test/central')
            ->assertRedirect('http://workbench.test/central/login');
    }

    public function test_central_super_admin_can_access_central_dashboard_tenants_list_and_edit(): void
    {
        $superAdmin = $this->makeInMemoryUser(roleId: 1, active: true);

        $this->actingAs($superAdmin, 'web')
            ->get('http://workbench.test/central')
            ->assertOk();

        $this->actingAs($superAdmin, 'web')
            ->get('http://workbench.test/central/tenants')
            ->assertOk();

        // Tenant creation remains intentionally disabled
        $this->actingAs($superAdmin, 'web')
            ->get('http://workbench.test/central/tenants/create')
            ->assertForbidden();

        // If at least one tenant exists in landlord DB, verify Edit page renders cleanly
        $existingTenant = CustomTenantModel::first();
        if ($existingTenant) {
            $this->actingAs($superAdmin, 'web')
                ->get("http://workbench.test/central/tenants/{$existingTenant->getKey()}/edit")
                ->assertOk();
        }
    }

    public function test_livewire_table_search_on_central_tenants_works_without_sql_error(): void
    {
        $superAdmin = $this->makeInMemoryUser(roleId: 1, active: true);

        $this->actingAs($superAdmin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        Livewire::test(ListTenants::class)
            ->searchTable('hr')
            ->assertOk();
    }

    public function test_ensure_central_domain_is_registered_as_persistent_livewire_middleware(): void
    {
        $superAdmin = $this->makeInMemoryUser(roleId: 1, active: true);

        $this->actingAs($superAdmin, 'web')
            ->get('http://workbench.test/central')
            ->assertOk();

        $this->assertContains(EnsureCentralDomain::class, Livewire::getPersistentMiddleware());
    }

    public function test_admin_panel_dashboard_has_no_regression_on_central_domain(): void
    {
        $superAdmin = $this->makeInMemoryUser(roleId: 1, active: true);

        $this->actingAs($superAdmin, 'web')
            ->get('http://workbench.test/admin')
            ->assertOk();
    }

    public function test_non_super_admin_user_is_forbidden_from_central_panel(): void
    {
        $staffUser = $this->makeInMemoryUser(roleId: 8, active: true);

        $this->actingAs($staffUser, 'web')
            ->get('http://workbench.test/central')
            ->assertForbidden();

        $this->actingAs($staffUser, 'web')
            ->get('http://workbench.test/central/tenants')
            ->assertForbidden();
    }

    public function test_inactive_super_admin_is_forbidden_from_central_panel(): void
    {
        $inactiveAdmin = $this->makeInMemoryUser(roleId: 1, active: false);

        $this->actingAs($inactiveAdmin, 'web')
            ->get('http://workbench.test/central')
            ->assertForbidden();
    }

    public function test_central_panel_returns_404_on_non_central_host_even_for_super_admin(): void
    {
        $superAdmin = $this->makeInMemoryUser(roleId: 1, active: true);

        $this->actingAs($superAdmin, 'web')
            ->get('http://client1.workbench.test/central')
            ->assertNotFound();

        $this->actingAs($superAdmin, 'web')
            ->get('http://client1.workbench.test/central/login')
            ->assertNotFound();

        $this->actingAs($superAdmin, 'web')
            ->get('http://client1.workbench.test/central/tenants')
            ->assertNotFound();
    }

    public function test_central_panel_returns_404_when_a_tenant_context_is_active(): void
    {
        $superAdmin = $this->makeInMemoryUser(roleId: 1, active: true);
        $tenant = $this->makeInMemoryTenant();

        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

        $this->actingAs($superAdmin, 'web')
            ->get('http://workbench.test/central')
            ->assertNotFound();

        $this->actingAs($superAdmin, 'web')
            ->get('http://workbench.test/central/tenants')
            ->assertNotFound();
    }

    public function test_admin_tenants_routes_no_longer_exist_on_admin_panel(): void
    {
        $superAdmin = $this->makeInMemoryUser(roleId: 1, active: true);

        $this->actingAs($superAdmin, 'web')
            ->get('http://workbench.test/admin/tenants')
            ->assertNotFound();

        $this->actingAs($superAdmin, 'web')
            ->get('http://client1.workbench.test/admin/tenants')
            ->assertNotFound();
    }

    public function test_dev_panel_blocks_non_developer_tenant_super_admin_outside_local(): void
    {
        $devPanel = filament()->getPanel('dev');
        $tenant = $this->makeInMemoryTenant();

        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

        $tenantAdmin = $this->makeInMemoryUser(roleId: 1, active: true, email: 'client-admin@example.com');
        $developer = $this->makeInMemoryUser(roleId: 1, active: true, email: 'hakimahmed123321@gmail.com');

        // In APP_ENV=testing (non-local), tenant super admin is denied /dev while developer is allowed
        $this->assertFalse($tenantAdmin->canAccessPanel($devPanel));
        $this->assertTrue($developer->canAccessPanel($devPanel));
    }

    public function test_legacy_base_spatie_tenant_is_safely_upgraded_without_type_error(): void
    {
        $baseTenant = new SpatieTenant();
        $baseTenant->forceFill([
            'id' => 999998,
            'name' => 'Legacy Tenant',
            'domain' => 'legacy.workbench.test',
            'database' => 'workbench_db',
            'active' => 1,
            'modules' => json_encode([CustomTenantModel::MODULE_HR]),
        ]);

        app()->instance(config('multitenancy.current_tenant_container_key'), $baseTenant);

        $current = app(IsTenant::class)::current();

        $this->assertInstanceOf(CustomTenantModel::class, $current);
        $this->assertSame(999998, $current->id);
        $this->assertSame([CustomTenantModel::MODULE_HR], $current->modules);
    }

    public function test_tenant_switching_tasks_make_and_forget_current_cleanly(): void
    {
        $originalPrefix = config('cache.prefix');
        $tenant = $this->makeInMemoryTenant();

        $tenant->makeCurrent();

        $this->assertTrue(app(IsTenant::class)::checkCurrent());
        $this->assertSame('tenant', DB::getDefaultConnection());
        $this->assertSame('tenant_id_999999', config('cache.prefix'));

        $tenant->forget();

        $this->assertFalse(app(IsTenant::class)::checkCurrent());
        $this->assertSame('landlord', DB::getDefaultConnection());
        $this->assertSame($originalPrefix, config('cache.prefix'));
    }

    public function test_tenant_policy_enforces_central_super_admin_only(): void
    {
        $policy = new TenantPolicy();
        $tenant = $this->makeInMemoryTenant();
        $superAdmin = $this->makeInMemoryUser(roleId: 1, active: true);
        $branchManager = $this->makeInMemoryUser(roleId: 7, active: true);

        $this->assertTrue($policy->viewAny($superAdmin));
        $this->assertTrue($policy->update($superAdmin, $tenant));
        $this->assertTrue($policy->delete($superAdmin, $tenant));

        $this->assertFalse($policy->viewAny($branchManager));
        $this->assertFalse($policy->update($branchManager, $tenant));
        $this->assertFalse($policy->delete($branchManager, $tenant));

        // Even a super admin is denied if a tenant context is active
        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);
        $this->assertFalse($policy->viewAny($superAdmin));
        $this->assertFalse($policy->update($superAdmin, $tenant));
    }

    public function test_tenant_finder_returns_null_for_central_hosts_and_503_for_unknown_host(): void
    {
        $finder = new CustomTenantFinder();

        $this->assertNull($finder->findForRequest(Request::create('http://workbench.test/central')));
        $this->assertNull($finder->findForRequest(Request::create('http://localhost/central')));

        try {
            $finder->findForRequest(Request::create('http://non-existent-tenant.workbench.test/admin'));
            $this->fail('Expected HttpException(503) for unknown tenant host.');
        } catch (HttpException $e) {
            $this->assertSame(503, $e->getStatusCode());
        }
    }

    public function test_backup_service_rejects_invalid_database_identifiers(): void
    {
        $service = new TenantBackupService();
        $maliciousTenant = new CustomTenantModel([
            'name' => 'Bad Tenant',
            'domain' => 'bad.workbench.test',
            'database' => 'tenant_db; rm -rf /',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $service->backupToGoogleDrive($maliciousTenant);
    }

    public function test_tenant_resource_is_registered_only_in_central_panel(): void
    {
        $centralResources = filament()->getPanel('central')->getResources();
        $adminResources = filament()->getPanel('admin')->getResources();

        $this->assertContains(TenantResource::class, $centralResources);
        $this->assertNotContains(TenantResource::class, $adminResources);
    }
}
