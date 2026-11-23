<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CustomTenantModel;
use App\Models\TenantInvoice;
use App\Models\User;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantInvoiceReminderBadgeTest extends TestCase
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
        app(IsTenant::class)::forgetCurrent();
        app()->setLocale('en');

        parent::tearDown();
    }

    protected function makeInMemoryUser(): User
    {
        $user = new User();
        $user->id = 999999;
        $user->name = 'Tenant Admin';
        $user->email = 'tenant-admin@example.com';
        $user->active = 1;

        $role = new Role();
        $role->id = 1;
        $role->name = 'super_admin';

        $user->setRelation('roles', new Collection([$role]));
        $user->setRelation('employee', null);

        return $user;
    }

    protected function renderTopbarHook(): string
    {
        $adminPanel = filament()->getPanel('admin');
        filament()->setCurrentPanel($adminPanel);
        filament()->bootCurrentPanel();

        return FilamentView::renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER)->toHtml();
    }

    public function test_badge_never_renders_on_central_without_active_tenant(): void
    {
        $tenant = CustomTenantModel::firstOrFail();

        TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 300,
            'invoice_date' => today()->subDays(10),
            'due_date' => today()->addDays(5),
            'warning_days' => 10,
        ]);

        $this->actingAs($this->makeInMemoryUser(), 'web');

        $html = $this->renderTopbarHook();

        $this->assertStringNotContainsString('invoice-warn-badge', $html);
    }

    public function test_badge_does_not_render_outside_warning_window_or_for_paid_or_cancelled_invoices(): void
    {
        $tenant = CustomTenantModel::firstOrFail();
        TenantInvoice::where('tenant_id', $tenant->id)->delete();

        // 15 days remaining > 10 warning_days -> outside warning window
        $outsideWindow = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 300,
            'invoice_date' => today(),
            'due_date' => today()->addDays(15),
            'warning_days' => 10,
        ]);

        // Paid invoice inside window -> ignored
        $paid = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 400,
            'invoice_date' => today()->subDays(5),
            'due_date' => today()->addDays(2),
            'warning_days' => 10,
            'status' => TenantInvoice::STATUS_PAID,
            'paid_at' => today(),
        ]);

        // Cancelled invoice inside window -> ignored
        $cancelled = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 500,
            'invoice_date' => today()->subDays(5),
            'due_date' => today()->addDays(2),
            'warning_days' => 10,
            'status' => TenantInvoice::STATUS_CANCELLED,
        ]);

        $this->assertFalse($outsideWindow->isInWarningWindow());
        $this->assertFalse($paid->isInWarningWindow());
        $this->assertFalse($cancelled->isInWarningWindow());
        $this->assertNull(TenantInvoice::getMostUrgentWarningForTenant($tenant->id));

        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);
        $this->actingAs($this->makeInMemoryUser(), 'web');

        $html = $this->renderTopbarHook();

        $this->assertStringNotContainsString('invoice-warn-badge', $html);
    }

    public function test_badge_renders_for_tenant_inside_warning_window_with_remaining_days_and_localized_tooltip(): void
    {
        $tenant = CustomTenantModel::firstOrFail();
        TenantInvoice::where('tenant_id', $tenant->id)->delete();

        $laterInvoice = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 450,
            'invoice_date' => today()->subDays(2),
            'due_date' => today()->addDays(9),
            'warning_days' => 10,
        ]);

        $urgentInvoice = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 250,
            'invoice_date' => today()->subDays(5),
            'due_date' => today()->addDays(6),
            'warning_days' => 10,
        ]);

        $selected = TenantInvoice::getMostUrgentWarningForTenant($tenant->id);
        $this->assertNotNull($selected);
        $this->assertSame($urgentInvoice->id, $selected->id);
        $this->assertNotSame($laterInvoice->id, $selected->id);
        $this->assertTrue($selected->isInWarningWindow());
        $this->assertSame(6, $selected->getWarningBadgeDays());
        $this->assertFalse($selected->isUrgentWarning());

        // Check English tooltip
        app()->setLocale('en');
        $this->assertStringContainsString('6', $selected->getWarningTooltip());
        $this->assertStringContainsString($selected->invoice_number, $selected->getWarningTooltip());

        // Check Arabic tooltip via translation key
        app()->setLocale('ar');
        $this->assertSame(
            __('lang.tenant_invoice_warning_tooltip', ['days' => 6, 'invoice' => $selected->invoice_number]),
            $selected->getWarningTooltip()
        );

        app()->setLocale('en');
        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);
        $this->actingAs($this->makeInMemoryUser(), 'web');

        $html = $this->renderTopbarHook();

        $this->assertStringContainsString('invoice-warn-badge', $html);
        $this->assertStringContainsString('data-days="6"', $html);
        $this->assertStringContainsString($selected->invoice_number, $html);

        // Immediately disappears once paid
        $urgentInvoice->markAsPaid();
        $laterInvoice->markAsPaid();
        $this->assertNull(TenantInvoice::getMostUrgentWarningForTenant($tenant->id));
    }

    public function test_badge_escalates_to_danger_for_due_today_and_overdue_invoices(): void
    {
        $tenant = CustomTenantModel::firstOrFail();
        TenantInvoice::where('tenant_id', $tenant->id)->delete();

        $dueToday = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 300,
            'invoice_date' => today()->subDays(10),
            'due_date' => today(),
            'warning_days' => 10,
        ]);

        $this->assertTrue($dueToday->isInWarningWindow());
        $this->assertTrue($dueToday->isUrgentWarning());
        $this->assertSame(0, $dueToday->getWarningBadgeDays());
        $this->assertSame(
            __('lang.tenant_invoice_due_today_tooltip', ['invoice' => $dueToday->invoice_number]),
            $dueToday->getWarningTooltip()
        );

        $overdue = TenantInvoice::create([
            'tenant_id' => $tenant->id,
            'amount' => 350,
            'invoice_date' => today()->subDays(20),
            'due_date' => today()->subDays(4),
            'warning_days' => 10,
        ]);

        $this->assertTrue($overdue->isInWarningWindow());
        $this->assertTrue($overdue->isUrgentWarning());
        $this->assertSame(0, $overdue->getWarningBadgeDays());
        $this->assertSame(4, $overdue->getOverdueDays());
        $this->assertSame(
            __('lang.tenant_invoice_overdue_tooltip', ['days' => 4, 'invoice' => $overdue->invoice_number]),
            $overdue->getWarningTooltip()
        );

        app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);
        $this->actingAs($this->makeInMemoryUser(), 'web');

        $html = $this->renderTopbarHook();

        $this->assertStringContainsString('invoice-warn-badge', $html);
        $this->assertStringContainsString('data-days="0"', $html);
        $this->assertStringContainsString($overdue->invoice_number, $html);
    }
}
