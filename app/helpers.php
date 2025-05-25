<?php

use App\Models\Allowance;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CustomTenantModel;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\MonthSalaryDetail;
use App\Models\Order;
use Spatie\Permission\Models\Role;
use App\Models\Store;
use App\Models\SystemSetting;
use App\Models\UnitPrice;
use App\Models\User;
use App\Models\UserType;
use App\Models\WeeklyHoliday;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Contracts\IsTenant;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

if (!function_exists('getName')) {
    function getName()
    {
        return 'Eng. Hakeem';
    }
}
/**
 * to format money
 */
if (!function_exists('formatMoney')) {
    function formatMoney($amount)
    {
        $currency = settingWithDefault('currency_symbol', 'RM');
        return $currency .' '.  number_format($amount, 2) ;
    }
}

/**
 * to return current role
 */
if (!function_exists('getCurrentRole')) {
    function getCurrentRole()
    {
        $roleId = 0;
        if (isset(auth()?->user()?->roles) && count(auth()?->user()?->roles) > 0) {
            $roleId = auth()?->user()?->roles[0]?->id;
        }
        return $roleId;
    }
}

/**
 * to check if current user is super admin
 */
if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin()
    {
        $currentRole = getCurrentRole();
        if ($currentRole == 1) {
            return true;
        }
        return false;
    }
}
/**
 * to get branch id
 */
if (!function_exists('getBranchId')) {
    function getBranchId()
    {
        return auth()->user()?->branch?->id;
    }
}

/**
 * to add filament request select
 */
if (!function_exists('__filament_request_select')) {
    function __filament_request_select($key, $default = null)
    {
        if (request()->isMethod('post')) {
            $qu = request()->all();
            $data = data_get($qu, "serverMemo.data.tableFilters." . $key . ".value");

            if (data_get($qu, "updates.0.payload.name") == "tableFilters.$key.value") {
                $data = data_get($qu, "updates.0.payload.value", $data);
            }

            if (data_get($qu, "updates.0.payload.params.0") == "tableFilters.$key.value") {
                $data = data_get($qu, "updates.0.payload.params.1", $data);
            }

            if (is_array($data)) {
                return $default;
            }

            return $data ?? $default;
        } else {
            $qu = request()->query();
            $data = data_get($qu, "tableFilters." . $key . ".value", $default);
            if (is_array($data)) {
                return $default;
            }
            return $data;
        }
    }
}
if (!function_exists('__filament_request_select_multiple')) {
    function __filament_request_select_multiple($key, $default = null, $multiple = false, $type = null)
    {
        if (!empty($type)) {
            $valueType = $type;
            $multiple = true;
        } else {
            $valueType = $multiple ? 'values' : 'value';
        }

        if (request()->isMethod('post')) {
            $qu = request()->all();
            $data = data_get_recursive($qu, "serverMemo.data.tableFilters." . $key . ".$valueType");

            if (data_get_recursive($qu, "updates.0.payload.name") == "tableFilters.$key.$valueType") {
                $data = data_get_recursive($qu, "updates.0.payload.$valueType", $data);
            }

            if (data_get_recursive($qu, "updates.0.payload.params.0") == "tableFilters.$key.$valueType") {
                $data = data_get_recursive($qu, "updates.0.payload.params.1", $data);
            }

            if ($multiple) {
                return is_array($data) ? $data : $default;
            }

            return $data ?? $default;
        } else {
            $qu = request()->query();
            $data = data_get($qu, "tableFilters." . $key . ".$valueType", $default);
            if (is_array($data) && !$multiple) {
                return $default;
            }
            return $data;
        }
    }
}
if (!function_exists('data_get_recursive')) {
    function data_get_recursive($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $value = data_get_recursive($item, $key);

                    if (is_array($value)) {
                        $result = array_merge($result, $value);
                    } else {
                        $result[] = $value;
                    }
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}
/**
 * to add filament request date filter
 */
if (!function_exists('__filament_request_key')) {
    function __filament_request_key($key, $default = null)
    {
        if (request()->isMethod('post')) {
            $qu = request()->all();
            $data = data_get($qu, "serverMemo.data.tableFilters." . $key);

            if (data_get($qu, "updates.0.payload.params.0") == "tableFilters.$key") {
                $data = data_get($qu, "updates.0.payload.params.1", $data);
            }

            if (is_array($data)) {
                return $default;
            }

            return $data ?? $default;
        } else {
            $qu = request()->query();
            $data = data_get($qu, "tableFilters." . $key, $default);

            if (is_array($data)) {
                return $default;
            }
            return $data;
        }
    }
}
/**
 * get admins to notify [Super admin - Manager] roles
 */
if (!function_exists('getAdminsToNotify')) {
    function getAdminsToNotify()
    {
        $adminIds = [];
        $adminIds = User::whereHas("roles", function ($q) {
            $q->whereIn("id", [1, 3]);
        })->select('id', 'name')->get()->pluck('id')->toArray();
        $recipients = User::whereIn('id', $adminIds)->get(['id', 'name']);
        return $recipients;
    }
}
/**
 * get default store
 */
if (!function_exists('getDefaultStore')) {
    function getDefaultStore()
    {
        $defaultStoreId = Store::where('default_store', 1)->where('active', 1)->select('id')->first()?->id;
        if (is_null($defaultStoreId)) {
            $defaultStoreId = 0;
        }
        return $defaultStoreId;
    }
}
/**
 * to get default currency
 */
if (!function_exists('getDefaultCurrency')) {
    function getDefaultCurrency()
    {
        return settingWithDefault('currency_symbol', 'RM');
    }
}

/**
 * to get method of calculating prices of orders
 */
if (!function_exists('getCalculatingPriceOfOrdersMethod')) {
    function getCalculatingPriceOfOrdersMethod()
    {
        return 'fifo';
        return setting('calculating_orders_price_method') ?? Order::METHOD_UNIT_PRICE;
    }
}

/**
 * get price from unit price by product_id & unit_id
 */
if (!function_exists('getUnitPrice')) {
    function getUnitPrice($product_id, $unit_id)
    {
        return UnitPrice::where(
            'product_id',
            $product_id
        )->where('unit_id', $unit_id)?->first()?->price;
    }
}
/**
 * get price from unit price by product_id & unit_id
 */
if (!function_exists('getUnitPricePackageSize')) {
    function getUnitPricePackageSize($product_id, $unit_id)
    {
        return UnitPrice::where(
            'product_id',
            $product_id
        )->where('unit_id', $unit_id)?->first()?->package_size;
    }
}
/**
 * function to check if user has pending approval order when submit order
 */
if (!function_exists('checkIfUserHasPendingForApprovalOrder')) {
    function checkIfUserHasPendingForApprovalOrder($branchId)
    {
        $order = Order::where('status', Order::PENDING_APPROVAL)
            ->where('branch_id', $branchId)
            ->where('active', 1)
            ->first();

        return $order ? $order->id : null;
    }
}

/**
 * function to return no last days to return orders in mobile
 */
if (!function_exists('getLimitDaysOrders')) {
    function getLimitDaysOrders()
    {
        return setting('limit_days_orders');
    }
}

/**
 * function to return default user orders status
 */
if (!function_exists('getEnableUserOrdersToStore')) {
    function getEnableUserOrdersToStore()
    {
        return setting('enable_user_orders_to_store');
    }
}

/**
 * to return days as static with array
 */
if (!function_exists('getDays')) {
    function getDays()
    {
        return [
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Friday' => 'Friday',
            'Saturday' => 'Saturday',
            'Sunday' => 'Sunday',
        ];
    }
}

/**
 * to get user types as pluck(name,id)
 */
if (!function_exists('getUserTypes')) {
    function getUserTypes()
    {
        return UserType::select('name', 'id')
            ->when(isStuff(), function ($query) {
                $query->whereNotIn('id', [3, 4]); // Adjust this condition as needed
            })
            ->get()->pluck('name', 'id');
    }
}
/**
 * to get roles based on user_type_id
 */
if (!function_exists('getRolesByTypeId')) {
    function getRolesByTypeId($id)
    {
        $user_types = UserType::find($id)?->role_ids;
        return $user_types;
    }
}

/**
 * to get months
 */
if (!function_exists('getMonthsArray')) {
    function getMonthsArray()
    {
        return [
            'January' => [
                'name' => __('lang.month.january'), // English Translation
                'start_month' => '2024-01-01',
                'end_month' => '2024-01-31',
            ],
            'February' => [
                'name' => __('lang.month.february'), // English Translation
                'start_month' => '2024-02-01',
                'end_month' => '2024-02-29', // Adjust for leap years as needed
            ],
            'March' => [
                'name' => __('lang.month.march'), // English Translation
                'start_month' => '2024-03-01',
                'end_month' => '2024-03-31',
            ],
            'April' => [
                'name' => __('lang.month.april'), // English Translation
                'start_month' => '2024-04-01',
                'end_month' => '2024-04-30',
            ],
            'May' => [
                'name' => __('lang.month.may'), // English Translation
                'start_month' => '2024-05-01',
                'end_month' => '2024-05-31',
            ],
            'June' => [
                'name' => __('lang.month.june'), // English Translation
                'start_month' => '2024-06-01',
                'end_month' => '2024-06-30',
            ],
            'July' => [
                'name' => __('lang.month.july'), // English Translation
                'start_month' => '2024-07-01',
                'end_month' => '2024-07-31',
            ],
            'August' => [
                'name' => __('lang.month.august'), // English Translation
                'start_month' => '2024-08-01',
                'end_month' => '2024-08-31',
            ],
            'September' => [
                'name' => __('lang.month.september'), // English Translation
                'start_month' => '2024-09-01',
                'end_month' => '2024-09-30',
            ],
            'October' => [
                'name' => __('lang.month.october'), // English Translation
                'start_month' => '2024-10-01',
                'end_month' => '2024-10-31',
            ],
            'November' => [
                'name' => __('lang.month.november'), // English Translation
                'start_month' => '2024-11-01',
                'end_month' => '2024-11-30',
            ],
            'December' => [
                'name' => __('lang.month.december'), // English Translation
                'start_month' => '2024-12-01',
                'end_month' => '2024-12-31',
            ],
        ];
    }
}
if (!function_exists('getMonthsArray2')) {
    function getMonthsArray2()
    {
        $months = [];
        $currentDate = new DateTime(); // Current date
        for ($i = 0; $i < 12; $i++) {
            $monthDate = (clone $currentDate)->sub(new DateInterval("P{$i}M")); // Subtract months
            $startMonth = $monthDate->format('Y-m-01');
            $endMonth = $monthDate->format('Y-m-t');
            $monthName = $monthDate->format('F'); // Full month name

            $months[$monthName] = [
                'name' => __("lang.month." . strtolower($monthName)), // Dynamic language key
                'start_month' => $startMonth,
                'end_month' => $endMonth,
            ];
        }

        return array_reverse($months); // Reverse to keep the order from past to current
    }
}
if (!function_exists('getMonthArrayWithKeys')) {
    function getMonthArrayWithKeys()
    {
        return [
            '01' => __('lang.month.january'),  // January
            '02' => __('lang.month.february'), // February
            '03' => __('lang.month.march'),    // March
            '04' => __('lang.month.april'),    // April
            '05' => __('lang.month.may'),      // May
            '06' => __('lang.month.june'),     // June
            '07' => __('lang.month.july'),     // July
            '08' => __('lang.month.august'),   // August
            '09' => __('lang.month.september'), // September
            '10' => __('lang.month.october'),  // October
            '11' => __('lang.month.november'), // November
            '12' => __('lang.month.december'), // December
        ];
    }
}

/**
 * to get setting by key field
 */
if (!function_exists('setting')) {
    function setting($key)
    {
        return \App\Models\Setting::getSetting($key);
    }
}
if (!function_exists('settingWithDefault')) {
    function settingWithDefault($key, $default = null)
    {
        return \App\Models\Setting::getSetting($key, $default);
    }
}

/**
 * to get nationalities
 */

if (!function_exists('getNationalities')) {
    function getNationalities(): array
    {
        $path = storage_path('app/data/nationalities.json');

        $nationalities = [];

        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            foreach ($data as $item) {
                if ($item['active']) {
                    $nationalities[$item['code']] = $item['name']['en']; // Change 'en' to your app's default language if needed
                }
            }
        }

        return $nationalities;
    }
}
if (!function_exists('getNationalitiesAsCountries')) {
    function getNationalitiesAsCountries(): array
    {
        $path = storage_path('app/data/nationalities.json');

        $nationalities = [];

        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            foreach ($data as $item) {
                if ($item['active']) {
                    $nationalities[$item['code']] = $item['country']['en']; // Change 'en' to your app's default language if needed
                }
            }
        }

        return $nationalities;
    }
}
if (!function_exists('replaceZeroInstedNegative')) {
    function replaceZeroInstedNegative($value)
    {
        if ($value < 0) {
            return 0;
        }
        return $value;
    }
}
if (!function_exists('showSuccessNotifiMessage')) {
    function showSuccessNotifiMessage($title, $body = null)
    {
        return Notification::make()->success()->title($title)->body($body)->send();
    }
}
if (!function_exists('showWarningNotifiMessage')) {
    function showWarningNotifiMessage($title, $body = null)
    {
        return Notification::make()->warning()->title($title)->body($body)->send();
    }
}
if (!function_exists('isLocal')) {
    function isLocal(): bool
    {
        if (env('APP_ENV') == 'local') {
            return true;
        }
        return false;
    }
}

if (!function_exists('hideHrForTenant')) {
    function hideHrForTenant()
    {
        if (!app(IsTenant::class)::checkCurrent()) {
            return false;
        }
        $currentTenant = app(IsTenant::class)::current();
        if ($currentTenant) {
            $currentTenant = CustomTenantModel::find($currentTenant->id);
        }

        if ($currentTenant && is_array($currentTenant->modules) && !in_array(CustomTenantModel::MODULE_HR, $currentTenant->modules)) {
            return true;
        } elseif ($currentTenant && is_null($currentTenant->modules)) {
            return true;
        }
        return false;
    }
}
if (!function_exists('getDefaultStoreForCurrentStoreKeeper')) {
    function getDefaultStoreForCurrentStoreKeeper()
    {
        if (isStoreManager()) {
            $stores = auth()->user()->managedStores()->get(['id', 'default_store'])->toArray() ?? [];

            switch (count($stores)) {
                case 0:
                    return null; // No stores

                case 1:
                    return $stores[0]['id']; // Only one store, return it

                default:
                    // More than one store, check for a default store
                    foreach ($stores as $store) {
                        if ($store['default_store'] == 1) {
                            return $store['id']; // Return the default store
                        }
                    }
                    return $stores[0]['id']; // No default store, return the first one
            }
        }

        return null;
    }
}


if (!function_exists('toTopic')) {
    function toTopic($topic, $data)
    {
        try {
            $factory = (new Factory())
                ->withServiceAccount(storage_path('app/public/firebase/google-services.json'));
            $messaging = $factory->createMessaging();
            $message = CloudMessage::new()
                ->toTopic($topic)
                ->withNotification($data);
            $messaging->send($message);
        } catch (Exception $e) {
            Log::debug('when send to Topic');
            Log::debug($e->getMessage());
        }
    }
}
if (!function_exists('toToken')) {
    function toToken($deviceToken, $data)
    {
        Log::debug($deviceToken);
        Log::debug($data);
        try {
            $factory = (new Factory())
                ->withServiceAccount(storage_path('app/public/firebase/google-services.json'));
            $messaging = $factory->createMessaging();
            $message = CloudMessage::new()->toToken($deviceToken)
                ->withNotification($data);

            $messaging->send($message);
        } catch (Exception $e) {
            Log::debug('when send to token');
            Log::debug($e->getMessage());
        }
    }
}

if (!function_exists('sendNotification')) {
    function sendNotification($deviceToken, $title, $body, $data = [], $priority = 'high')
    {
        try {
            $factory = (new Factory())
                ->withServiceAccount(storage_path('app/public/firebase/google-services.json'));
            $messaging = $factory->createMessaging();

            $notificationData = [
                'title' => $title,
                'body' => $body,
            ];

            $message = CloudMessage::new()
                ->toToken($deviceToken)
                ->withNotification($notificationData)
                ->withData($data) // Additional custom data
                ->withHighestPossiblePriority(); // Ensures high priority

            $messaging->send($message);
            $response = json_encode([
                'status' => 'success',
                'message' => 'Notification sent successfully',
                'data' => [
                    'deviceToken' => $deviceToken,
                    'title' => $title,
                    'body' => $body,
                    'payload' => $data
                ]
            ]);
            Log::info($response);
            return $response;
        } catch (Exception $e) {
            $response = json_encode([
                'status' => 'error',
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Log::error($response);
            return $response;
        }
    }
}

if (!function_exists('formUserForExistingEmployee')) {
    function formUserForExistingEmployee()
    {
        return [

            Fieldset::make()->schema([Select::make('search_employee')->label('Search for employee')
                ->helperText('You can search using .. Employee (Name, Email, ID, Employee number)...')
                // ->options(Employee::where('active', 1)->select('name', 'id', 'phone_number', 'email')->get()->pluck('name', 'id'))
                ->getSearchResultsUsing(
                    fn(string $search): array =>
                    Employee::where('active', 1)
                        ->whereDoesntHave('user')
                        ->where(function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('id', $search)
                                ->orWhere('phone_number', 'like', "%{$search}%")
                                ->orWhere('job_title', 'like', "%{$search}%");
                        })
                        ->limit(5)
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->getOptionLabelUsing(fn($value): ?string => Employee::find($value)?->name)
                ->columnSpanFull()
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {

                    $employee = Employee::find($state);
                    if ($employee) {
                        $set('name', $employee->name);
                        $set('email', $employee->email);
                        $set('phone_number', $employee->phone_number);
                        $set('branch_id', $employee->branch_id);
                        $set('nationality', $employee->nationality);
                        // $set('avatar', $employee->avatar);
                        // $set('avatar', [
                        //     'url' => Storage::disk('s3')->url($employee->avatar),
                        //     'name' => basename($employee->avatar),
                        // ]);
                        $positionId = $employee?->position_id;
                        if ($positionId == 2) {
                            if (isset($employee?->branch_id)) {
                                $branchManagerId = Branch::find($employee?->branch_id)?->user?->id;
                                if ($branchManagerId) {
                                    $set('owner_id', $branchManagerId);
                                }
                            }
                            $set('roles', [8]);
                        }
                    }
                })]),
            Fieldset::make('')->label('')->schema([
                Grid::make()->columns(3)->schema([
                    TextInput::make('name')->disabled()->unique(ignoreRecord: true),
                    TextInput::make('email')->disabled()->unique(ignoreRecord: true)->email()->required(),
                    PhoneInput::make('phone_number')->disabled()
                        // ->numeric()
                        ->initialCountry('MY')
                        ->onlyCountries([
                            'MY',
                            'US',
                            'YE',
                            'AE',
                            'SA',
                        ])
                        ->displayNumberFormat(PhoneInputNumberType::E164)
                        ->autoPlaceholder('aggressive')
                    // ->validateFor(
                    //     country: 'MY',
                    //     lenient: true, // default: false
                    // )
                    ,

                ]),
                Fieldset::make()->columns(3)->label('Set user type, role and manager')->schema([
                    Select::make('user_type')->required()
                        ->label('User type')
                        ->options(getUserTypes())
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {

                            //  dd($roles,$state);
                        }),
                    CheckboxList::make('roles')
                        ->label('Role')
                        ->relationship('roles')->required()
                        ->maxItems(1)
                        ->options(function (Get $get) {
                            // dd($get('user_type'),'hi');
                            if ($get('user_type')) {
                                $roles = getRolesByTypeId($get('user_type'));
                                // dd($roles,gettype($roles));
                                return Role::select('name', 'id')->whereIn('id', $roles)
                                    ->orderBy('name', 'asc')
                                    ->get()->pluck('name', 'id');
                            }
                        }),
                    Select::make('owner_id')
                        ->label('Manager')
                        ->searchable()
                        ->options(function () {
                            return User::query()->select('name', 'id')->get()->pluck('name', 'id');
                        }),
                    Select::make('branch_id')
                        ->label('Branch')
                        ->searchable()
                        ->disabled()
                        ->options(function () {
                            return Branch::query()->get()->pluck('name', 'id');
                        }),
                    Select::make('nationality')
                        ->label('Nationality')
                        ->options(getNationalities()) // Loads nationalities from JSON file
                        ->searchable()
                        ->nullable(),
                ]),
                Grid::make()->columns(2)->schema([
                    TextInput::make('password')
                        ->password()
                        ->required(fn(string $context) => $context === 'create')
                        ->reactive()
                        ->dehydrateStateUsing(fn($state) => Hash::make($state)),
                    TextInput::make('password_confirmation')
                        ->password()
                        ->required(fn(string $context) => $context === 'create')
                        ->same('password')
                        ->label('Confirm Password'),
                ]),

            ]),

        ];
    }
}
