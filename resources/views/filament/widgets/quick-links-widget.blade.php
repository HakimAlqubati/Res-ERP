@php
    use Spatie\Multitenancy\Contracts\IsTenant;
    use App\Models\CustomTenantModel;

    $currentTenant = app(IsTenant::class)::current();
    if ($currentTenant) {
        $currentTenant = CustomTenantModel::find($currentTenant->id);
    }
@endphp


<x-filament::widget>
    <div wire:ignore>
        <style>
            .quick-link {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 168px;
                height: 168px;
                gap: .5rem;
                text-align: center;
                border-radius: 1rem;
                background-color: #0d7c66;
                color: #fff;
                transition: all .2s ease;
                border: 1px solid transparent;
            }

            .quick-link:hover {
                transform: translateY(-4px);
                border-color: #0d7c66;
                color: #0d7c66;
                background-color: #fff;
            }

            .quick-link svg {
                width: 4.5rem;
                height: 4.5rem;
                color: #ffffff;
            }

            .quick-link:hover svg {
                color: #0d7c66;
            }

            .quick-link .label {
                font-size: 1.05rem;
                font-weight: 700;
            }

            .quick-link .badge {
                font-weight: 700;
                font-size: 0.9rem;
            }

            .dark .quick-link {
                background-color: #0d7c66;
                color: #fff;
                border-color: rgba(255, 255, 255, .25);
            }

            .dark .quick-link:hover {
                background-color: rgba(255, 255, 255, .08);
                color: #0d7c66;
                border-color: #0d7c66;
            }

            .dark .quick-link svg {
                color: #fff;
            }

            .tile-grid {
                display: grid;
                gap: 1rem;
                place-items: center;
                /* توسيط أفقي + عمودي */
            }

            @media (min-width: 1024px) {
                .grid-3 {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }

                .grid-6 {
                    grid-template-columns: repeat(6, minmax(0, 1fr));
                }
            }
        </style>

        <x-filament::card>
            {{-- General Section --}}
            <x-filament::fieldset label="{{ __('General') }}">
                <div class="tile-grid grid-3">
                    <a href="{{ route('filament.admin.resources.users.index') }}" class="quick-link">
                        <x-heroicon-o-user />
                        <div class="label">{{ __('Users') }}</div>
                        <div class="badge">{{ \App\Models\User::count() }}</div>
                    </a>

                    <a href="{{ route('filament.admin.resources.branches.index') }}" class="quick-link">
                        <x-heroicon-o-building-office-2 />
                        <div class="label">{{ __('Branches') }}</div>
                        <div class="badge">{{ \App\Models\Branch::count() }}</div>
                    </a>

                    <a href="{{ route('filament.admin.area-management.resources.countries.index') }}"
                        class="quick-link">
                        <x-heroicon-o-globe-alt />
                        <div class="label">{{ __('Area Management') }}</div>
                        <div class="badge">{{ \App\Models\Country::count() }}</div>
                    </a>
                </div>
            </x-filament::fieldset>
            {{-- Inventory Section --}}
            @if (
                ($currentTenant &&
                    is_array($currentTenant->modules) &&
                    in_array(CustomTenantModel::MODULE_STOCK, $currentTenant->modules)) ||
                    is_null($currentTenant))
                <x-filament::fieldset label="{{ __('Inventory Management') }}">
                    <div class="tile-grid grid-6">
                        <a href="{{ route('filament.admin.main-orders') }}" class="quick-link">
                            <x-heroicon-m-sparkles />
                            <div class="label">{{ __('Orders') }}</div>
                            <div class="badge">{{ \App\Models\Order::count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.product-unit.resources.products.index') }}"
                            class="quick-link">
                            <x-heroicon-o-cube />
                            <div class="label">{{ __('Products') }}</div>
                            <div class="badge">{{ \App\Models\Product::active()->count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.supplier') }}" class="quick-link">
                            <x-heroicon-o-building-storefront />
                            <div class="label">{{ __('Suppliers') }}</div>
                            <div class="badge">{{ \App\Models\Supplier::count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.supplier-stores-reports.resources.stores.index') }}"
                            class="quick-link">
                            <x-heroicon-o-home-modern />
                            <div class="label">{{ __('Stores') }}</div>
                            <div class="badge">{{ \App\Models\Store::count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.supplier.resources.purchase-invoices.index') }}"
                            class="quick-link">
                            <x-heroicon-o-receipt-percent />
                            <div class="label">{{ __('Purchases') }}</div>
                            <div class="badge">{{ \App\Models\PurchaseInvoice::count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.inventory-report.resources.inventory-report.index') }}"
                            class="quick-link">
                            <x-heroicon-o-newspaper />
                            <div class="label">{{ __('Inventory') }}</div>
                        </a>
                    </div>
                </x-filament::fieldset>
            @endif

            {{-- HR Section --}}
            @if (
                ($currentTenant &&
                    is_array($currentTenant->modules) &&
                    in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules)) ||
                    is_null($currentTenant))
                {{-- Human Resources --}}
                <x-filament::fieldset label="{{ __('Human Resources') }}">
                    <div class="tile-grid grid-6">
                        <a href="{{ route('filament.admin.h-r.resources.employees.index') }}" class="quick-link">
                            <x-heroicon-o-user-group />
                            <div class="label">{{ __('Employees') }}</div>
                            <div class="badge">{{ \App\Models\Employee::active()->count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.h-r-attenance.resources.attendnaces.index') }}"
                            class="quick-link">
                            <x-heroicon-o-calendar-days />
                            <div class="label">{{ __('Attendance') }}</div>
                            <div class="badge">{{ \App\Models\Attendance::count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.resources.departments.index') }}" class="quick-link">
                            <x-heroicon-o-building-office-2 />
                            <div class="label">{{ __('Departments') }}</div>
                            <div class="badge">{{ \App\Models\Department::count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.h-r-tasks-system.resources.tasks.index') }}"
                            class="quick-link">
                            <x-heroicon-o-pencil-square />
                            <div class="label">{{ __('Tasks') }}</div>
                            <div class="badge">{{ \App\Models\Task::count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.h-r-circular.resources.circulars.index') }}"
                            class="quick-link">
                            <x-heroicon-o-building-office-2 />
                            <div class="label">{{ __('Engagement') }}</div>
                            <div class="badge">{{ \App\Models\Circular::count() }}</div>
                        </a>

                        <a href="{{ route('filament.admin.h-r-salary.resources.month-salaries.index') }}"
                            class="quick-link">
                            <x-heroicon-o-banknotes />
                            <div class="label">{{ __('Payroll') }}</div>
                            <div class="badge">{{ \App\Models\MonthSalary::count() }}</div>
                        </a>
                    </div>
                </x-filament::fieldset>
            @endif

        </x-filament::card>
    </div>
</x-filament::widget>
