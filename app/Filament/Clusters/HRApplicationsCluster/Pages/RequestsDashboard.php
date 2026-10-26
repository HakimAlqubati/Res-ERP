<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Pages;

use App\Filament\Clusters\HRApplicationsCluster;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\EmployeeApplicationV2;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class RequestsDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?string $cluster = HRApplicationsCluster::class;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'dashboard';

    protected string $view = 'filament.clusters.hr-applications-cluster.pages.requests-dashboard';

    public function getSubNavigation(): array
    {
        return [];
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.requests_dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return __('lang.requests_dashboard');
    }

    public static function canAccess(): bool
    {
        return EmployeeApplicationResource::canViewAny();
    }

    public function getDashboardData(): array
    {
        $countsRaw = EmployeeApplicationV2::query()
            ->whereHas('employee', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->forBranchManager()
            ->select('application_type_id', 'status', DB::raw('count(*) as aggregate'))
            ->groupBy('application_type_id', 'status')
            ->get();

        $definitions = [
            EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => [
                'type_id' => EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST,
                'name' => __('lang.missed_checkin_request'),
                'tab' => 'Missed check-in',
            ],
            EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => [
                'type_id' => EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
                'name' => __('lang.missed_checkout_request'),
                'tab' => 'Missed check-out',
            ],
            EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST => [
                'type_id' => EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST,
                'name' => __('lang.advance_request'),
                'tab' => 'Advance request',
            ],
            EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST => [
                'type_id' => EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST,
                'name' => __('lang.leave_request'),
                'tab' => 'Leave request',
            ],
            EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST => [
                'type_id' => EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST,
                'name' => __('lang.employee_meals_request'),
                'tab' => 'Expense advance',
            ],
        ];

        $items = [];
        foreach ($definitions as $typeId => $def) {
            $pending = (int) ($countsRaw->first(fn ($r) => $r->application_type_id == $typeId && $r->status === EmployeeApplicationV2::STATUS_PENDING)?->aggregate ?? 0);
            $total = (int) ($countsRaw->where('application_type_id', $typeId)->sum('aggregate'));

            $items[] = [
                'type_id' => $typeId,
                'name' => $def['name'],
                'pending' => $pending,
                'total' => $total,
                'url' => EmployeeApplicationResource::getUrl('index', ['tab' => $def['tab']], isAbsolute: false),
            ];
        }

        return [
            'items' => $items,
        ];
    }
}
