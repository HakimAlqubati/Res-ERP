<x-filament::widget>
    <x-filament::card>
        {{-- First row --}}
        <x-filament::fieldset label="{{ __('Inventory Management') }}">
            <x-filament::grid style="--cols-lg: repeat(6, minmax(0, 1fr));" class="lg:grid-cols-[--cols-lg]">
                {{-- Orders --}}
                <x-filament::link :href="route('filament.admin.main-orders')" class="link" badge-color="danger" color="primary"
                    icon="heroicon-m-sparkles" icon-position="before" tooltip="Go to Orders Page">


                    {{ __('Orders') }}
                    <x-slot name="badge">
                        {{ \App\Models\Order::count() }}
                    </x-slot>

                </x-filament::link>

                {{-- Products --}}
                <x-filament::link badge-color="info" color="primary" icon="heroicon-o-cube" icon-position="before"
                    tooltip="Go to Products Page" class="link">
                    {{ __('Products') }}
                    <x-slot name="badge">
                        {{ \App\Models\Product::active()->count() }}
                    </x-slot>
                </x-filament::link>

                {{-- Suppliers --}}
                <x-filament::link class="link" badge-color="success" color="primary"
                    icon="heroicon-o-building-storefront" icon-position="before" tooltip="Go to Suppliers Page">
                    {{ __('Suppliers') }}
                    <x-slot name="badge">
                        {{ \App\Models\Supplier::count() }}
                    </x-slot>
                </x-filament::link>

                {{-- Stores --}}
                <x-filament::link badge-color="warning" color="primary" icon="heroicon-o-home-modern"
                    icon-position="before" tooltip="Go to Stores Page" class="link">
                    {{ __('Stores') }}
                    <x-slot name="badge">
                        {{ \App\Models\Store::count() }}
                    </x-slot>
                </x-filament::link>

                {{-- Users --}}
                <x-filament::link badge-color="purple" color="primary" icon="heroicon-o-user" icon-position="before"
                    class="link" tooltip="Go to Users Page">
                    {{ __('Users') }}
                    <x-slot name="badge">
                        {{ \App\Models\User::count() }}
                    </x-slot>
                </x-filament::link>

                {{-- Purchase Invoices --}}
                <x-filament::link badge-color="gray" color="primary" icon="heroicon-o-receipt-percent"
                    icon-position="before" tooltip="Go to Purchase Invoices" class="link">
                    {{ __('Purchases') }}
                    <x-slot name="badge">
                        {{ \App\Models\PurchaseInvoice::count() }}
                    </x-slot>
                </x-filament::link>
            </x-filament::grid>
        </x-filament::fieldset>

        {{-- HR section --}}
        <x-filament::fieldset label="{{ __('Human Resources') }}">
            <x-filament::grid style="--cols-lg: repeat(6, minmax(0, 1fr));" class="lg:grid-cols-[--cols-lg]">
                {{-- Employees --}}
                <x-filament::link :href="route('filament.admin.h-r.resources.employees.index')" badge-color="danger" color="primary" icon="heroicon-o-user-group"
                    icon-position="before" tooltip="Go to Employees Page" class="link">
                    {{ __('Employees') }}
                    <x-slot name="badge">
                        {{ \App\Models\Employee::active()->count() }}
                    </x-slot>
                </x-filament::link>

                {{-- Attendance --}}
                <x-filament::link badge-color="info" color="primary" icon="heroicon-o-calendar-days"
                    icon-position="before" tooltip="Attendance Records" class="link">
                    {{ __('Attendance') }}
                    <x-slot name="badge">
                        {{ \App\Models\Attendance::count() }}
                    </x-slot>
                </x-filament::link>

                {{-- Departments --}}
                <x-filament::link badge-color="success" color="primary" icon="heroicon-o-building-office-2"
                    icon-position="before" tooltip="Go to Departments" class="link">
                    {{ __('Departments') }}
                    <x-slot name="badge">
                        {{ \App\Models\Department::count() }}
                    </x-slot>
                </x-filament::link>
                {{-- Tasks --}}
                <x-filament::link badge-color="success" color="primary" icon="heroicon-o-pencil-square"
                    icon-position="before" tooltip="Go to Tasks" class="link">
                    {{ __('Tasks') }}
                    <x-slot name="badge">
                        {{ \App\Models\Task::count() }}
                    </x-slot>
                </x-filament::link>
                {{-- Circular --}}
                <x-filament::link badge-color="success" color="primary" icon="heroicon-o-building-office-2"
                    icon-position="before" tooltip="Go to Engagement" class="link">
                    {{ __('Engagement') }}
                    <x-slot name="badge">
                        {{ \App\Models\Circular::count() }}
                    </x-slot>
                </x-filament::link>
                {{-- Payroll --}}
                <x-filament::link badge-color="success" color="primary" icon="heroicon-o-banknotes"
                    icon-position="before" tooltip="Go to Payroll" class="link">
                    {{ __('Payroll') }}
                    <x-slot name="badge">
                        {{ \App\Models\MonthSalary::count() }}
                    </x-slot>
                </x-filament::link>
            </x-filament::grid>
        </x-filament::fieldset>
    </x-filament::card>
</x-filament::widget>
