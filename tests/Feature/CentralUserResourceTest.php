<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Central\Resources\Users\Pages\CreateUser;
use App\Filament\Central\Resources\Users\Pages\EditUser;
use App\Filament\Central\Resources\Users\Pages\ListUsers;
use App\Filament\Central\Resources\Users\UserResource as CentralUserResource;
use App\Filament\Resources\UserResource as AdminUserResource;
use App\Models\CustomTenantModel;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CentralUserResourceTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['landlord', 'mysql'];

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
        $user = User::query()
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

    public function test_central_user_resource_is_registered_only_in_central_panel(): void
    {
        $centralResources = filament()->getPanel('central')->getResources();
        $adminResources = filament()->getPanel('admin')->getResources();

        $this->assertContains(CentralUserResource::class, $centralResources);
        $this->assertNotContains(CentralUserResource::class, $adminResources);
        $this->assertContains(AdminUserResource::class, $adminResources);
        $this->assertNotContains(AdminUserResource::class, $centralResources);
        $this->assertNull(CentralUserResource::getNavigationGroup());
        $this->assertSame(4, CentralUserResource::getNavigationSort());
    }

    public function test_create_form_only_exposes_name_email_and_password_and_creates_central_super_admin(): void
    {
        $admin = $this->getCentralSuperAdmin();

        $this->actingAs($admin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        Livewire::test(CreateUser::class)
            ->assertOk()
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('email')
            ->assertFormFieldExists('password')
            ->assertFormFieldDoesNotExist('roles')
            ->assertFormFieldDoesNotExist('role_id')
            ->assertFormFieldDoesNotExist('branch_id')
            ->fillForm([
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'email', 'password']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Central Operator',
                'email' => 'central.operator@workbench.test',
                'password' => 'StrongPass123!',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()
            ->where('email', 'central.operator@workbench.test')
            ->firstOrFail();

        $this->assertSame('Central Operator', $created->name);
        $this->assertSame(1, (int) $created->active);
        $this->assertSame(1, (int) $created->role_id);
        $this->assertNotSame('StrongPass123!', $created->password);
        $this->assertTrue(Hash::check('StrongPass123!', $created->password));

        $created->load('roles');
        $this->assertTrue($created->isSuperAdmin());
        $this->assertTrue($created->canAccessCentralPanel());

        // Newly created central user can immediately access /central and /central/users
        $this->actingAs($created, 'web')
            ->get('http://workbench.test/central')
            ->assertOk();

        $this->actingAs($created, 'web')
            ->get('http://workbench.test/central/users')
            ->assertOk();

        // Duplicate email is rejected
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Duplicate Email User',
                'email' => 'central.operator@workbench.test',
                'password' => 'StrongPass123!',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_edit_form_updates_name_and_email_without_changing_password_when_blank_and_updates_password_when_provided(): void
    {
        $admin = $this->getCentralSuperAdmin();

        $targetUser = User::create([
            'name' => 'Original Central User',
            'email' => 'original.central@workbench.test',
            'password' => Hash::make('InitialSecret123!'),
            'active' => 1,
            'role_id' => 1,
        ]);

        $originalHash = $targetUser->password;

        $this->actingAs($admin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        // Update name and email while leaving password blank
        Livewire::test(EditUser::class, ['record' => $targetUser->getRouteKey()])
            ->assertOk()
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('email')
            ->assertFormFieldExists('password')
            ->assertFormFieldDoesNotExist('roles')
            ->assertFormSet([
                'name' => 'Original Central User',
                'email' => 'original.central@workbench.test',
                'password' => null,
            ])
            ->fillForm([
                'name' => 'Updated Central User',
                'email' => 'updated.central@workbench.test',
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $targetUser->refresh();
        $this->assertSame('Updated Central User', $targetUser->name);
        $this->assertSame('updated.central@workbench.test', $targetUser->email);
        $this->assertSame($originalHash, $targetUser->password);
        $this->assertTrue(Hash::check('InitialSecret123!', $targetUser->password));

        // Update password when provided
        Livewire::test(EditUser::class, ['record' => $targetUser->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Central User',
                'email' => 'updated.central@workbench.test',
                'password' => 'NewSecretPass456!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $targetUser->refresh();
        $this->assertNotSame($originalHash, $targetUser->password);
        $this->assertTrue(Hash::check('NewSecretPass456!', $targetUser->password));
    }

    public function test_table_displays_only_expected_columns_searches_and_prevents_self_deletion(): void
    {
        $admin = $this->getCentralSuperAdmin();

        $otherUser = User::create([
            'name' => 'Deletable Admin',
            'email' => 'deletable.admin@workbench.test',
            'password' => Hash::make('SecretPass123!'),
            'active' => 1,
            'role_id' => 1,
        ]);

        $this->actingAs($admin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('email')
            ->assertTableColumnDoesNotExist('roles')
            ->assertTableColumnDoesNotExist('roles_title')
            ->assertTableColumnDoesNotExist('first_role.name')
            ->assertCanSeeTableRecords([$admin, $otherUser])
            ->searchTable('deletable.admin@workbench.test')
            ->assertCanSeeTableRecords([$otherUser])
            ->assertCanNotSeeTableRecords([$admin])
            ->searchTable('')
            ->assertActionHidden(TestAction::make('delete')->table($admin))
            ->assertActionVisible(TestAction::make('delete')->table($otherUser))
            ->callAction(TestAction::make('delete')->table($otherUser))
            ->assertHasNoActionErrors();

        $this->assertSoftDeleted('users', ['id' => $otherUser->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);

        // Bulk delete also cannot delete the currently authenticated user, while still allowing deletion of other users
        $thirdUser = User::create([
            'name' => 'Bulk Deletable Admin',
            'email' => 'bulk.deletable@workbench.test',
            'password' => Hash::make('SecretPass123!'),
            'active' => 1,
            'role_id' => 1,
        ]);

        Livewire::test(ListUsers::class)
            ->selectTableRecords([$admin, $thirdUser])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
        $this->assertSoftDeleted('users', ['id' => $thirdUser->id]);

        // Edit page header DeleteAction is hidden for the authenticated user's own account
        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertOk()
            ->assertActionHidden('delete');
    }

    public function test_central_users_routes_enforce_central_super_admin_and_domain_isolation(): void
    {
        $admin = $this->getCentralSuperAdmin();
        $staff = $this->makeInMemoryStaffUser();

        // Central super admin can visit index and create
        $this->actingAs($admin, 'web')
            ->get('http://workbench.test/central/users')
            ->assertOk();

        $this->actingAs($admin, 'web')
            ->get('http://workbench.test/central/users/create')
            ->assertOk();

        $this->actingAs($admin, 'web')
            ->get("http://workbench.test/central/users/{$admin->getKey()}/edit")
            ->assertOk();

        // Non-super-admin is forbidden
        $this->actingAs($staff, 'web')
            ->get('http://workbench.test/central/users')
            ->assertForbidden();

        $this->actingAs($staff, 'web')
            ->get('http://workbench.test/central/users/create')
            ->assertForbidden();

        // Tenant domains receive 404
        $this->actingAs($admin, 'web')
            ->get('http://sultan.workbench.test/central/users')
            ->assertNotFound();

        // Active tenant context receives 404
        $tenant = new CustomTenantModel([
            'name' => 'Test Tenant',
            'domain' => 'sultan.workbench.test',
            'database' => 'workbench_db',
            'active' => true,
        ]);
        $tenant->id = 999999;

        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

        $this->actingAs($admin, 'web')
            ->get('http://workbench.test/central/users')
            ->assertNotFound();
    }
}
