<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Central\Resources\TenantInvoices\Pages\CreateTenantInvoice;
use App\Filament\Central\Resources\TenantInvoices\Pages\EditTenantInvoice;
use App\Filament\Central\Resources\TenantInvoices\Pages\ListTenantInvoices;
use App\Filament\Central\Resources\TenantInvoices\TenantInvoiceResource;
use App\Models\CustomTenantModel;
use App\Models\TenantInvoice;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Multitenancy\Contracts\IsTenant;
use Tests\TestCase;

class TenantInvoiceResourceTest extends TestCase
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

    public function test_tenant_invoices_table_exists_on_landlord_with_required_columns(): void
    {
        $schema = Schema::connection('landlord');

        $this->assertTrue($schema->hasTable('tenant_invoices'));
        $this->assertTrue($schema->hasColumns('tenant_invoices', [
            'id',
            'tenant_id',
            'invoice_number',
            'amount',
            'invoice_date',
            'due_date',
            'status',
            'paid_at',
            'warning_days',
            'notes',
            'created_by',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_model_auto_generates_unique_invoice_number_defaults_and_supports_scopes_and_mark_as_paid(): void
    {
        $admin = $this->getCentralSuperAdmin();
        $tenant = CustomTenantModel::firstOrFail();

        $this->actingAs($admin, 'web');

        $overdueInvoice = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 250.50,
            'invoice_date' => today()->subDays(20),
            'due_date' => today()->subDays(5),
            'notes' => 'Initial note',
        ]);

        $upcomingInvoice = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 500.00,
            'invoice_date' => today(),
            'due_date' => today()->addDays(15),
        ]);

        $expectedYear = today()->subDays(20)->format('Y');
        $this->assertSame(
            TenantInvoice::formatInvoiceNumber($overdueInvoice->id, $expectedYear),
            $overdueInvoice->invoice_number
        );
        $this->assertNotSame($overdueInvoice->invoice_number, $upcomingInvoice->invoice_number);
        $this->assertSame(TenantInvoice::STATUS_UNPAID, $overdueInvoice->status);
        $this->assertSame(TenantInvoice::getDefaultWarningDays(), $overdueInvoice->warning_days);
        $this->assertSame($admin->id, $overdueInvoice->created_by);
        $this->assertTrue($overdueInvoice->isOverdue());
        $this->assertFalse($upcomingInvoice->isOverdue());
        $this->assertNull($overdueInvoice->remaining_days);
        $this->assertSame(15, $upcomingInvoice->remaining_days);

        // Invoice due today has no remaining future days
        $dueTodayInvoice = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 100.00,
            'invoice_date' => today(),
            'due_date' => today(),
        ]);
        $this->assertNull($dueTodayInvoice->remaining_days);

        // Paid invoice returns null even if due_date is in the future
        $upcomingPaid = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 100.00,
            'invoice_date' => today(),
            'due_date' => today()->addDays(20),
        ]);
        $upcomingPaid->markAsPaid();
        $this->assertNull($upcomingPaid->fresh()->remaining_days);

        // Relationships
        $this->assertSame($tenant->id, $overdueInvoice->tenant->id);
        $this->assertSame($admin->id, $overdueInvoice->creator->id);
        $this->assertTrue($tenant->invoices()->whereKey($overdueInvoice->id)->exists());

        // Scopes
        $this->assertTrue(TenantInvoice::unpaid()->whereKey($overdueInvoice->id)->exists());
        $this->assertTrue(TenantInvoice::overdue()->whereKey($overdueInvoice->id)->exists());
        $this->assertFalse(TenantInvoice::overdue()->whereKey($upcomingInvoice->id)->exists());

        // Mark as paid domain method
        $paidDate = today()->toDateString();
        $overdueInvoice->markAsPaid($paidDate, 'Bank transfer confirmed');
        $overdueInvoice->refresh();

        $this->assertSame(TenantInvoice::STATUS_PAID, $overdueInvoice->status);
        $this->assertTrue($overdueInvoice->isPaid());
        $this->assertFalse($overdueInvoice->isOverdue());
        $this->assertSame($paidDate, $overdueInvoice->paid_at?->toDateString());
        $this->assertStringContainsString('Initial note', (string) $overdueInvoice->notes);
        $this->assertStringContainsString('Bank transfer confirmed', (string) $overdueInvoice->notes);
        $this->assertTrue(TenantInvoice::paid()->whereKey($overdueInvoice->id)->exists());
        $this->assertFalse(TenantInvoice::overdue()->whereKey($overdueInvoice->id)->exists());
    }

    public function test_resource_is_registered_only_in_central_panel_with_expected_sidebar_order_and_eager_loading(): void
    {
        $admin = $this->getCentralSuperAdmin();
        $this->actingAs($admin, 'web');

        $centralPanel = filament()->getPanel('central');
        $adminPanel = filament()->getPanel('admin');

        $this->assertContains(TenantInvoiceResource::class, $centralPanel->getResources());
        $this->assertNotContains(TenantInvoiceResource::class, $adminPanel->getResources());
        $this->assertNull(TenantInvoiceResource::getNavigationGroup());
        $this->assertSame(2, TenantInvoiceResource::getNavigationSort());
        $this->assertArrayHasKey('tenant', TenantInvoiceResource::getEloquentQuery()->getEagerLoads());

        filament()->setCurrentPanel($centralPanel);
        filament()->bootCurrentPanel();

        $navigationGroups = array_values($centralPanel->getNavigation());
        $this->assertCount(1, $navigationGroups);

        $firstGroup = $navigationGroups[0];
        $labels = collect($firstGroup->getItems())
            ->values()
            ->map(fn ($item): string => (string) $item->getLabel())
            ->all();

        $this->assertNull($firstGroup->getLabel());
        $this->assertSame([
            'Dashboard',
            __('Tenant Invoices'),
            'Tenants',
            __('Users'),
            'Settings',
        ], $labels);
    }

    public function test_create_form_requires_manual_unique_invoice_number_validates_inputs_and_creates_invoice(): void
    {
        $admin = $this->getCentralSuperAdmin();
        $tenant = CustomTenantModel::firstOrFail();

        $this->actingAs($admin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        // invoice_number must be visible, enabled, and required; validation must also reject amount <= 0 and due_date < invoice_date
        Livewire::test(CreateTenantInvoice::class)
            ->assertFormFieldIsVisible('invoice_number')
            ->assertFormFieldIsEnabled('invoice_number')
            ->fillForm([
                'invoice_number' => '',
                'tenant_id' => $tenant->id,
                'amount' => 0,
                'invoice_date' => '2026-11-20',
                'due_date' => '2026-11-10',
                'warning_days' => 10,
            ])
            ->call('create')
            ->assertHasFormErrors(['invoice_number', 'amount', 'due_date']);

        // Valid creation with manual invoice_number
        Livewire::test(CreateTenantInvoice::class)
            ->fillForm([
                'invoice_number' => 'MAN-2026-0001',
                'tenant_id' => $tenant->id,
                'amount' => 1200.75,
                'invoice_date' => '2026-11-23',
                'due_date' => '2026-12-23',
                'warning_days' => 10,
                'notes' => 'Annual subscription invoice',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = TenantInvoice::latest('id')->firstOrFail();

        $this->assertSame($tenant->id, $created->tenant_id);
        $this->assertSame('MAN-2026-0001', $created->invoice_number);
        $this->assertSame('1200.75', (string) $created->amount);
        $this->assertSame(TenantInvoice::STATUS_UNPAID, $created->status);
        $this->assertNull($created->paid_at);
        $this->assertSame($admin->id, $created->created_by);

        // Duplicate invoice_number is rejected
        Livewire::test(CreateTenantInvoice::class)
            ->fillForm([
                'invoice_number' => 'MAN-2026-0001',
                'tenant_id' => $tenant->id,
                'amount' => 200.00,
                'invoice_date' => '2026-11-23',
                'due_date' => '2026-12-23',
                'warning_days' => 10,
            ])
            ->call('create')
            ->assertHasFormErrors(['invoice_number']);

        // Creating an already-paid invoice directly from Create form
        Livewire::test(CreateTenantInvoice::class)
            ->fillForm([
                'invoice_number' => 'MAN-2026-0002',
                'tenant_id' => $tenant->id,
                'amount' => 950.00,
                'invoice_date' => '2026-11-23',
                'due_date' => '2026-12-23',
                'warning_days' => 10,
                'status' => TenantInvoice::STATUS_PAID,
                'paid_at' => '2026-11-23',
                'notes' => 'Paid upfront',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $paidCreated = TenantInvoice::latest('id')->firstOrFail();
        $this->assertSame('MAN-2026-0002', $paidCreated->invoice_number);
        $this->assertSame(TenantInvoice::STATUS_PAID, $paidCreated->status);
        $this->assertSame('2026-11-23', $paidCreated->paid_at?->toDateString());
        $this->assertNull($paidCreated->remaining_days);
    }

    public function test_edit_form_allows_editing_invoice_number_and_ignores_current_record_for_uniqueness(): void
    {
        $admin = $this->getCentralSuperAdmin();
        $tenant = CustomTenantModel::firstOrFail();

        $this->actingAs($admin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        $invoice = TenantInvoice::create([
            'invoice_number' => 'MAN-2026-EDIT-1',
            'tenant_id' => $tenant->id,
            'amount' => 300,
            'invoice_date' => today(),
            'due_date' => today()->addDays(10),
        ]);

        Livewire::test(EditTenantInvoice::class, ['record' => $invoice->getRouteKey()])
            ->assertOk()
            ->assertFormFieldIsVisible('invoice_number')
            ->assertFormFieldIsEnabled('invoice_number')
            ->fillForm([
                'invoice_number' => 'MAN-2026-EDIT-1',
                'amount' => 325,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->fillForm([
                'invoice_number' => 'MAN-2026-EDIT-2',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('MAN-2026-EDIT-2', $invoice->fresh()->invoice_number);
    }

    public function test_table_search_filters_and_mark_as_paid_row_action_work(): void
    {
        $admin = $this->getCentralSuperAdmin();
        $tenant = CustomTenantModel::firstOrFail();

        $this->actingAs($admin, 'web');
        filament()->setCurrentPanel(filament()->getPanel('central'));

        $unpaidOverdue = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 450,
            'invoice_date' => today()->subDays(15),
            'due_date' => today()->subDays(2),
        ]);

        $paidInvoice = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 600,
            'invoice_date' => today()->subDays(10),
            'due_date' => today()->addDays(5),
        ]);
        $paidInvoice->markAsPaid(today()->toDateString(), 'Already settled');

        Livewire::test(ListTenantInvoices::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$unpaidOverdue, $paidInvoice])
            ->searchTable($unpaidOverdue->invoice_number)
            ->assertCanSeeTableRecords([$unpaidOverdue])
            ->assertCanNotSeeTableRecords([$paidInvoice])
            ->searchTable('')
            ->filterTable('overdue', true)
            ->assertCanSeeTableRecords([$unpaidOverdue])
            ->assertCanNotSeeTableRecords([$paidInvoice])
            ->resetTableFilters()
            ->assertActionVisible(TestAction::make('markAsPaid')->table($unpaidOverdue))
            ->assertActionHidden(TestAction::make('markAsPaid')->table($paidInvoice))
            ->callAction(TestAction::make('markAsPaid')->table($unpaidOverdue), data: [
                'paid_at' => today()->toDateString(),
                'notes' => 'Paid via wire',
            ])
            ->assertHasNoActionErrors();

        $unpaidOverdue->refresh();
        $this->assertTrue($unpaidOverdue->isPaid());
        $this->assertSame(today()->toDateString(), $unpaidOverdue->paid_at?->toDateString());
        $this->assertStringContainsString('Paid via wire', (string) $unpaidOverdue->notes);
    }

    public function test_tenant_invoices_routes_are_blocked_on_tenant_domains(): void
    {
        $admin = $this->getCentralSuperAdmin();

        $this->actingAs($admin, 'web')
            ->get('http://sultan.workbench.test/central/tenant-invoices')
            ->assertNotFound();

        $this->actingAs($admin, 'web')
            ->get('http://workbench.test/admin/tenant-invoices')
            ->assertNotFound();
    }
}
