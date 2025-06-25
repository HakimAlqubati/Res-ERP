<x-filament::widget>
    <style>
        .link {
            transition: all 0.2s ease-in-out;
            border-radius: 1rem;
            background-color: #0d7c66;
            /* خلفية خضراء */
            color: #ffffff;
            /* النص والأيقونة بيضاء */
            display: flex;
            flex-direction: column;
            /* ترتيب الأيقونة والنص عمودياً */
            align-items: center;
            justify-content: center;
            padding: 1rem;
            text-align: center;
            width: 160px;
            height: 160px;
        }

        .link:hover {
            /* عند التمرير، تغيير الألوان */
            /* background-color: #ffffff; */
            /* الخلفية بيضاء */
            color: #0d7c66;
            /* background-color: #ffffff; */
            /* النص والأيقونة خضراء */
            transform: translateY(-4px);
            font-weight: 700;
            /* جعل النص عريض */
            border: 1px solid #0d7c66;
            /* تغيير الحدود للأخضر عند التمرير */
        }

        /* === إصلاح الوضع الليلي === */
        .dark .link {
            box-shadow: none !important;
            border: 1px solid white !important;
        }

        .dark .link:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #0d7c66;
            /* تغيير النص إلى اللون الأخضر */
            border-color: #0d7c66;
            /* تغيير الحدود عند التمرير */
        }

        .link svg {
            width: 5.5rem;
            /* حجم الأيقونة */
            height: 5.5rem;
            /* حجم الأيقونة */
            color: #ffffff;
            /* الأيقونة بيضاء */
            margin-bottom: 0.5rem;
            /* المسافة بين الأيقونة والنص */
        }

        .link:hover svg {
            /* color: #ffffff; */
            /* الأيقونة خضراء عند التمرير */
        }

        .link:hover {
            /* عند التمرير، تغيير الألوان */
            /* background-color: #ffffff; */
            /* الخلفية بيضاء */
            color: #0d7c66;
            /* تغيير الحدود للأخضر عند التمرير */
        }


        .link_span {
            color: #fff !important;
            /* النص باللون الأبيض */
            font-size: 1.125rem;
            /* حجم النص */
        }

        /* تصميم للـ badge داخل الرابط */
        .badge {

            /* خلفية الـ badge بيضاء */
            color: #ffffff;

        }

        /* تنسيق لجعل العدد بجانب النص */
        .link-text {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            /* المسافة بين النص والعدد */
        }

        /* Dark mode adjustments */
        .dark .link svg {
            color: #ffffff !important;
            /* Change icon color to white in dark mode */
        }
    </style>

    <x-filament::card>
        {{-- First row --}}
        <x-filament::fieldset style="    text-align: -webkit-center" label="{{ __('General') }}">
            <x-filament::grid
                style="--cols-lg: repeat(3, minmax(0, 1fr)); display: grid; justify-items: center; align-items: center; gap: 0.5rem;width:70%"
                class="lg:grid-cols-[--cols-lg]">
                {{-- Users --}}
                <x-filament::link :href="route('filament.admin.resources.users.index')" badge-color="warning" color="primary" icon="heroicon-o-user"
                    icon-position="before" class="link" tooltip="Go to Users Page">
                    <div class="link-text">
                        <span class="link_span">
                            {{ __('Users') }}
                        </span>
                        <span class="badge">
                            {{ \App\Models\User::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Branches --}}
                <x-filament::link :href="route('filament.admin.resources.branches.index')" badge-color="warning" color="primary"
                    icon="heroicon-o-building-office-2" icon-position="before" class="link"
                    tooltip="Go to Branches Page">
                    <div class="link-text">
                        <span class="link_span">
                            {{ __('Branches') }}
                        </span>
                        <span class="badge">
                            {{ \App\Models\Branch::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Area Management --}}
                <x-filament::link :href="route('filament.admin.area-management.resources.countries.index')" badge-color="warning" color="primary" icon="heroicon-o-globe-alt"
                    icon-position="before" class="link" tooltip="Go to Area Management Page">
                    <div class="link-text">
                        <span class="link_span">
                            {{ __('Area Management') }}
                        </span>
                        <span class="badge">
                            {{ \App\Models\Country::count() }}
                        </span>
                    </div>
                </x-filament::link>
            </x-filament::grid>

        </x-filament::fieldset>

        {{-- Inventory Management Section --}}
        <x-filament::fieldset label="{{ __('Inventory Management') }}">
            <x-filament::grid style="--cols-lg: repeat(6, minmax(0, 1fr));" class="lg:grid-cols-[--cols-lg]">
                {{-- Orders --}}
                <x-filament::link :href="route('filament.admin.main-orders')" class="link" badge-color="danger" color="primary"
                    icon="heroicon-m-sparkles" icon-position="before" tooltip="Go to Orders Page">
                    <div class="link-text">
                        <span class="link_span">
                            {{ __('Orders') }}
                        </span>
                        <span class="badge">
                            {{ \App\Models\Order::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Products --}}
                <x-filament::link :href="route('filament.admin.product-unit.resources.products.index')" badge-color="info" color="primary" icon="heroicon-o-cube"
                    icon-position="before" tooltip="Go to Products Page" class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Products') }} </span>
                        <span class="badge">
                            {{ \App\Models\Product::active()->count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Suppliers --}}
                <x-filament::link :href="route('filament.admin.supplier')" class="link" badge-color="success" color="primary"
                    icon="heroicon-o-building-storefront" icon-position="before" tooltip="Go to Suppliers Page">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Suppliers') }} </span>
                        <span class="badge">
                            {{ \App\Models\Supplier::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Stores --}}
                <x-filament::link :href="route('filament.admin.supplier-stores-reports.resources.stores.index')" badge-color="warning" color="primary" icon="heroicon-o-home-modern"
                    icon-position="before" tooltip="Go to Stores Page" class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Stores') }}</span>
                        <span class="badge">
                            {{ \App\Models\Store::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Purchase Invoices --}}
                <x-filament::link :href="route('filament.admin.supplier.resources.purchase-invoices.index')" badge-color="gray" color="primary" icon="heroicon-o-receipt-percent"
                    icon-position="before" tooltip="Go to Purchase Invoices" class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Purchases') }}</span>
                        <span class="badge">
                            {{ \App\Models\PurchaseInvoice::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Inventory Reports --}}
                <x-filament::link :href="route('filament.admin.inventory-report.resources.inventory-report.index')" badge-color="warning" color="primary" icon="heroicon-o-newspaper"
                    icon-position="before" class="link" tooltip="Go to Inventory Reports">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Inventory') }} </span>

                    </div>
                </x-filament::link>
            </x-filament::grid>
        </x-filament::fieldset>

        {{-- Human Resources Section --}}
        <x-filament::fieldset label="{{ __('Human Resources') }}">
            <x-filament::grid style="--cols-lg: repeat(6, minmax(0, 1fr));" class="lg:grid-cols-[--cols-lg]">
                {{-- Employees --}}
                <x-filament::link :href="route('filament.admin.h-r.resources.employees.index')" badge-color="danger" color="primary" icon="heroicon-o-user-group"
                    icon-position="before" tooltip="Go to Employees Page" class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Employees') }} </span>
                        <span class="badge">
                            {{ \App\Models\Employee::active()->count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Attendance --}}
                <x-filament::link :href="route('filament.admin.h-r-attenance.resources.attendnaces.index')" badge-color="info" color="primary"
                    icon="heroicon-o-calendar-days" icon-position="before" tooltip="Attendance Records"
                    class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Attendance') }} </span>
                        <span class="badge">
                            {{ \App\Models\Attendance::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Departments --}}
                <x-filament::link :href="route('filament.admin.resources.departments.index')" badge-color="success" color="primary"
                    icon="heroicon-o-building-office-2" icon-position="before" tooltip="Go to Departments"
                    class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Departments') }} </span>
                        <span class="badge">
                            {{ \App\Models\Department::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Tasks --}}
                <x-filament::link :href="route('filament.admin.h-r-tasks-system.resources.tasks.index')" badge-color="success" color="primary"
                    icon="heroicon-o-pencil-square" icon-position="before" tooltip="Go to Tasks" class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Tasks') }} </span>
                        <span class="badge">
                            {{ \App\Models\Task::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Engagement --}}
                <x-filament::link :href="route('filament.admin.h-r-circular.resources.circulars.index')" badge-color="success" color="primary"
                    icon="heroicon-o-building-office-2" icon-position="before" tooltip="Go to Engagement"
                    class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Engagement') }} </span>
                        <span class="badge">
                            {{ \App\Models\Circular::count() }}
                        </span>
                    </div>
                </x-filament::link>

                {{-- Payroll --}}
                <x-filament::link :href="route('filament.admin.h-r-salary.resources.month-salaries.index')" badge-color="success" color="primary"
                    icon="heroicon-o-banknotes" icon-position="before" tooltip="Go to Payroll" class="link">
                    <div class="link-text">
                        <span class="link_span"> {{ __('Payroll') }} </span>
                        <span class="badge">
                            {{ \App\Models\MonthSalary::count() }}
                        </span>
                    </div>
                </x-filament::link>
            </x-filament::grid>
        </x-filament::fieldset>
    </x-filament::card>
</x-filament::widget>
