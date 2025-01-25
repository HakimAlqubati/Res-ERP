<?php

use App\Filament\Pages\AttendanecEmployee;
use App\Filament\Pages\AttendanecEmployee2;
use App\Filament\Pages\AttendanecEmployeeTest;
use App\Http\Controllers\EmployeeAWSController;
use App\Http\Controllers\EmployeeImageAwsIndexesController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MigrateDataController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SearchByCameraController;
use App\Http\Controllers\TestController2;
use App\Http\Controllers\TestController;
use App\Models\Approval;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Task;
use App\Models\UnitPrice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::middleware('tenant')->group(function () {
    // routes
    Route::get('/dd', function () {

        // $tasks = Task::all();
        $per = DB::table('permissions')->orderBy('id', 'desc')->first();


        config([
            'database.connections.tenant.host' =>  $_ENV['DB_HOST'],
            'database.connections.tenant.database' =>  'tenant_tenant108',
            'database.connections.tenant.username' =>  $_ENV['DB_USERNAME'],
            'database.connections.tenant.password' =>  $_ENV['DB_PASSWORD'],
            'database.connections.tenant.port' =>  $_ENV['DB_PORT'],
        ]);
        dd(
            DB::purge('tenant'),

            DB::reconnect('tenant'),
            Schema::connection('tenant')->getConnection()->reconnect(),
            Schema::connection('tenant')->getConnection()->reconnect(),
            app('currentTenant')->name,
            $per,
            app(IsTenant::class)::checkCurrent(),
            DB::connection()->getDatabaseName()
        );
        // $tenant = Tenant::current();
        // dd($tenant);
        return view('welcome');
        return "Welcome to tenant: " . $tenant;
    });
});
// return;
Route::get('/test_deduction_report/{empId}/{year}', function ($employeeId, $year) {
    return (new TestController2())->showDeductions($employeeId, $year);
});
Route::get('/totestpdf/{empId}/{startMonth}/{endMonth}', function ($employeeId, $startMonth, $endMonth) {
    return (new TestController2())->getDeductionEmployeeMonthly($employeeId, $startMonth, $endMonth);

    // return generateSalarySlipPdf_(82,170);
    $employee = Employee::find(143);
    $branch = Employee::find(6);
    $task = Task::with('steps')->find(69);
    return view('export.reports.hr.tasks.employee-task-report2', compact('employee', 'branch', 'task'));
    return view('export.reports.hr.salaries.salary-slip');
    $order = Order::find(52);
    $orderDetails = $order?->orderDetails;
    return view('export.order_pdf', compact('order', 'orderDetails'));
});
Route::get('/to_test_salary_slip/{empId}/{sid}', [TestController2::class, 'to_test_salary_slip']);
Route::get('/to_test_schedule_task/{date}', [TestController::class, 'to_test_schedule_task']);
Route::get('/to_test_calculate_salary/{empId}/{date}', [TestController2::class, 'to_test_calculate_salary']);
Route::get('/to_test_calculate_auto_leave/{yearMonth}/{empId}', [TestController2::class, 'to_test_calculate_auto_leave']);
Route::get('/to_test_calculate_auto_leave_by_branch/{yearMonth}/{branchId}', [TestController2::class, 'to_test_calculate_auto_leave_by_branch']);
Route::get('/to_test_calculate_salary_with_attendances_deducations/{empId}/{date}', [TestController2::class, 'to_test_calculate_salary_with_attendances_deducations']);
Route::get('/to_test_emplployee_attendance_time', [TestController2::class, 'to_test_emplployee_attendance_time']);
Route::get('/to_get_employee_attendances', [TestController2::class, 'to_get_employee_attendances']);
Route::get('/to_get_employee_attendance_period_details', [TestController2::class, 'to_get_employee_attendance_period_details']);
Route::get('/to_get_multi_employees_attendances', [TestController2::class, 'to_get_multi_employees_attendances']);
Route::get('/migrateEmployeePeriodHistory', [MigrateDataController::class, 'migrateEmployeePeriodHistory']);
Route::get('/toviewrepeated', function () {
    /**
     * order IDs
     * (71,82,84,86,89,90,91,92,95,103,104,106,107,110,111,112,115)
     *
     */
    $repeated_order_details = DB::table('orders_details')->select(
        'order_id',
        'product_id',
        'id',
        'unit_id',
        'available_quantity',
        'quantity',
        'created_by'
    )->get();
    foreach ($repeated_order_details as $key => $value) {
        $res[$value->order_id][$value->product_id][$value->unit_id][] = $value;
    }
    foreach ($res as $k => $v) {

        foreach ($v as $kk => $vv) {
            foreach ($vv as $kkk => $vvv) {
                # code...
                if (count($vvv) > 1) {
                    $ress[] = $vvv;
                }
            }
        }
    }
    // return $ress;
    foreach ($ress as $r => $rv) {
        $sum_qty = 0;
        $sum_av_qty = 0;
        foreach ($rv as $rr => $rrvv) {
            $sum_qty += $rrvv->quantity;
            $sum_av_qty += $rrvv->available_quantity;

            if ($rr == 0) {
                OrderDetails::find($rrvv->id)->delete();
            }
            if ($rr == 1) {
                OrderDetails::find($rv[1]->id)->update([
                    'quantity' => $sum_qty,
                    'available_quantity' => $sum_av_qty,
                ]);
                // $ressss[$rrvv->order_id][] = [
                //     'id' => $rrvv->id,
                //     'sum_qty' => $sum_qty,
                //     'sum_av_qty' => $sum_av_qty,
                // ];
            }
        }
    }
    return $ress;
});
Route::get('/tomodifypricinginpurchaseinvoices', function () {
    $purchase_invoice_details = PurchaseInvoiceDetail::get();
    // return $purchase_invoice_details;
    foreach ($purchase_invoice_details as $key => $value) {
        $val = (object) $value;
        $unit_price = UnitPrice::where('product_id', $val->product_id)->where('unit_id', $val->unit_id)?->first()?->price;

        // $res[] = [
        //     'id' => $val->id,
        //     'product_id' => $val->product_id,
        //     'product_name' =>  Product::find($val->product_id)->name,
        //     'unit_id' => $val->unit_id,
        //     'quantity' => $val->quantity,
        //     'price' => $val->price,
        //     'unit_price' => $unit_price,
        //     'product_unit_prices' => UnitPrice::where('product_id', $val->product_id)->get()->toArray(),
        // ];

        if ($unit_price == null) {
            $res['nullable'][] = [
                'id' => $val->id,
                'product_id' => $val->product_id,
                'product_name' => Product::find($val->product_id)->name,
                'unit_id' => $val->unit_id,
                'quantity' => $val->quantity,
                'price' => $val->price,
                'unit_price' => $unit_price,
                'product_unit_prices' => UnitPrice::where('product_id', $val->product_id)->get()->toArray(),
            ];
        } else {
            $res['have'][] = [
                'id' => $val->id,
                'product_id' => $val->product_id,
                'product_name' => Product::find($val->product_id)->name,
                'unit_id' => $val->unit_id,
                'quantity' => $val->quantity,
                'price' => $val->price,
                'unit_price' => $unit_price,
                'product_unit_prices' => UnitPrice::where('product_id', $val->product_id)->get()->toArray(),
            ];
        }
    }

    foreach ($res['have'] as $kn => $vn) {

        // if (count($vn['product_unit_prices']) > 0 && !in_array($vn['unit_id'], array_column($vn['product_unit_prices'], 'unit_id'))) {
        if ($vn['price'] == 1 && count($vn['product_unit_prices']) > 0 && in_array($vn['unit_id'], array_column($vn['product_unit_prices'], 'unit_id'))) {
            // PurchaseInvoiceDetail::find($vn['id'])->update(
            //     [
            //         'unit_id' => $vn['product_unit_prices'][0]['unit_id'],
            //         'price' => $vn['product_unit_prices'][0]['price'],
            //     ]
            // );

            // $res2[] = [
            //     'product_id' => $vn['product_id'],
            //     'product_name' => $vn['product_name']
            // ];
            $res2[] = $vn;
        }
        // if ($vn['unit_id'] == 0) {

        //     PurchaseInvoiceDetail::find($vn['id'])->update(
        //         [
        //             'unit_id' => $vn['product_unit_prices'][0]->unit_id,
        //             'price' => $vn['product_unit_prices'][0]->price,
        //         ]
        //     );
        // }
    }
    return $res2;
    return $res['nullable'];
    return $res;
    return $res['have'];
    return $purchase_invoice_details;
});
Route::get('/', function () {

    return redirect(url('/admin'));
});

Route::get('orders/export/{id}', [OrderController::class, 'export']);
Route::get('orders/export-transfer/{id}', [OrderController::class, 'exportTransfer']);

Route::get('/import_page_units', [ImportController::class, 'import_units_view']);
Route::post('/import_units', [
    ImportController::class,
    'importUnits',
])->name('import_units');

Route::get('/import_page_products', [ImportController::class, 'import_products_view']);
Route::post('/import_products', [
    ImportController::class,
    'importProducts',
])->name('import_products');

Route::get('/import_page_purchase_invoice_details', [ImportController::class, 'import_purchase_invoice_details_view']);
Route::post('/import_purchase_invoice_details', [
    ImportController::class,
    'importpurchaseInvoiceDetails',
])->name('import_purchase_invoice_details');

Route::get('/import_page_item_types', [ImportController::class, 'import_item_types_view']);
Route::post('/import_item_types', [
    ImportController::class,
    'importItemTypes',
])->name('import_item_types');

Route::get('/import_page_categories', [ImportController::class, 'import_categories_view']);
Route::post('/import_categories', [
    ImportController::class,
    'importCategories',
])->name('import_categories');

Route::get('/import_page_unit_prices', [ImportController::class, 'import_unit_prices_view']);
Route::post('/import_unit_prices', [
    ImportController::class,
    'importUnitPrices',
])->name('import_unit_prices');

Route::get('/test-tasks-job', function () {

    return 'dailly tasks added';
});

Route::get('/updated_user_type_for_branch_managers', function () {
    $branchManagers = Role::find(7)->users;
    $arr = [];
    foreach ($branchManagers as $branchManager) {
        $user = User::find($branchManager['id']);
        $user->update(['user_type' => 2]);
        if ($user->employee()->exists()) {
            $user->employee()->update(['employee_type' => 2]);
        }
        $arr[] = $user;
    }
    return $arr;
});

Route::get('/updated_user_type_for_managers', function () {
    $managers = Role::find(3)->users;
    $arr = [];
    foreach ($managers as $manager) {
        $user = User::find($manager['id']);
        $user->update(['user_type' => 3]);
        if ($user->employee()->exists()) {
            $user->employee()->update(['employee_type' => 3]);
        }
        $arr[] = $user;
    }
    return $arr;
});
Route::get('/updated_user_type_for_stuff_branches_users', function () {
    $stuffManagers = Role::find(8)->users;
    $arr = [];
    foreach ($stuffManagers as $stuffManager) {
        $user = User::find($stuffManager['id']);
        $user->update(['user_type' => 4]);
        if ($user->employee()->exists()) {

            $user->employee()->update(['employee_type' => 4]);
        }
        $arr[] = $user;
    }
    return $arr;
});

Route::get('/updated_user_type_for_top_management_users', function () {
    $maintenanceManagers = Role::find(14)->users;
    $arr = [];
    foreach ($maintenanceManagers as $maintenanceManager) {
        $user = User::find($maintenanceManager['id']);
        $user->update(['user_type' => 1]);
        if ($user->employee()->exists()) {
            $user->employee()->update(['employee_type' => 1]);
        }
        $arr[] = $user;
    }
    return $arr;
});

Route::get('/migration_branch_manager_users', function () {
    $branchManagers = Role::find(7)->users;
    // dd($branchManagers);
    foreach ($branchManagers as $branchManager) {
        Employee::create([
            'name' => $branchManager->name,
            'position_id' => 1,
            'email' => $branchManager->email,
            'phone_number' => $branchManager->phone_number,
            'job_title' => 'Branch manager',
            'user_id' => $branchManager->id,
            'branch_id' => $branchManager?->branch?->id,
            'employee_no' => '12005' . $branchManager->id,
            'active' => 1,
        ]);
    }

    dd($branchManagers);
});
Route::get('/migration_users_of_branch', function () {
    $users = Role::find(8)->users;
    foreach ($users as $user) {
        Employee::create([
            'name' => $user->name,
            'position_id' => 2,
            'email' => $user?->email,
            'phone_number' => $user?->phone_number,
            'job_title' => 'Department employee',
            'user_id' => $user->id,
            'branch_id' => $user?->owner?->branch?->id,
            'employee_no' => '12005' . $user->id,
            'active' => 1,
        ]);
    }

    dd($users);
});

Route::get('/migration_store_users', function () {
    $users = Role::find(5)->users;
    foreach ($users as $user) {
        Employee::create([
            'name' => $user->name,
            'position_id' => 3,
            'email' => $user?->email,
            'phone_number' => $user?->phone_number,
            'job_title' => 'Store responsiple',
            'user_id' => $user->id,

            'employee_no' => '12005' . $user->id,
            'active' => 1,
        ]);
    }

    dd($users);
});

Route::get('/migration_accountants_users', function () {
    $users = Role::find(9)->users;
    foreach ($users as $user) {
        Employee::create([
            'name' => $user->name,
            'position_id' => 4,
            'email' => $user?->email,
            'phone_number' => $user?->phone_number,
            'job_title' => 'Accountant',
            'user_id' => $user->id,
            'employee_no' => '12005' . $user->id,
            'active' => 1,
        ]);
    }

    dd($users);
});

Route::get('/update_user_branch_id_for_all_users', function () {
    $users = User::whereNull('branch_id')->withTrashed()->get();
    $branchUsers = [];
    foreach ($users as $user) {
        // Check if the user has an owner
        $owner = $user->owner()->exists(); // Check if the owner relationship exists
        $branch = $user->branch()->exists(); // Check if the branch relationship exists
        $branchId = 0;
        if ($owner && (!is_null($user?->owner?->branch?->id))) {
            $branchId = $user->owner->branch->id;
            $branchUsers[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'branch_id' => $branchId,

            ];
        } else if ($branch && (!is_null($user?->branch?->id))) {
            $branchId = $user?->branch?->id;
            $branchUsers[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'branch_id' => $branchId,

            ];
        }
    }
    foreach ($branchUsers as $branchUser) {

        $userObj = User::find($branchUser['user_id']);
        $userObj->update(['branch_id' => $branchUser['branch_id']]);
    }
    return $branchUsers;
});

Route::get('/attendance', AttendanecEmployee2::class)
    ->name('attendance')->middleware('check');
Route::get('/attendanceSecret__', AttendanecEmployee2::class)
    ->name('attendanceSecret__');
Route::get('/attendanceTest', AttendanecEmployeeTest::class)
    ->name('attendanceTest')->middleware('check');


Route::get('get_employees_attendnaces/{check_date}', [MigrateDataController::class, 'get_employees_attendnaces']);
Route::get('get_employees_without_attendances/{check_date}', [MigrateDataController::class, 'get_employees_without_attendances']);

Route::get('/migrateAdvanceRequest', [MigrateDataController::class, 'migrateAdvanceRequest']);
Route::get('/migrateMissedCheckinRequest', [MigrateDataController::class, 'migrateMissedCheckinRequest']);
Route::get('/migrateMissedCheckoutRequest', [MigrateDataController::class, 'migrateMissedCheckoutRequest']);
Route::get('/migrateLeaveRequest', [MigrateDataController::class, 'migrateLeaveRequest']);
Route::get('/send-test-email', function () {


    // $sampleEmployees = [
    //     (object)['name' => 'John Doe'],
    //     (object)['name' => 'Jane Smith']
    // ];

    // \Illuminate\Support\Facades\Mail::to('hakimahmed123321@gmail.com')->send(new \App\Mail\AbsentEmployeesMail($sampleEmployees, '2024-10-22'));
    // return 'Email sent!';
});

Route::get('/reportAbsentEmployees/{date}/{branchId}/{currentTime}', [TestController2::class, 'reportAbsentEmployees']);

Route::get('/updateAllPeriodsToDayAndNight', [MigrateDataController::class, 'updateAllPeriodsToDayAndNight']);

Route::get('/addAWSEmployee', [EmployeeAWSController::class, 'addEmployee']);
Route::get('/indexImages', [EmployeeImageAwsIndexesController::class, 'indexImages']);

Route::post('/filament/search-by-camera/process', [SearchByCameraController::class, 'process'])->name('filament.pages.search-by-camera.process');


Route::get('workbench_webcam', function () {

    // Check if the user is authenticated
    if (!Auth::check()) {
        return redirect()->route('login')->with('error', 'You need to be logged in to access this page.');
    }

    $userId = auth()->id();
    // Check if an approval record exists for the user
    $approval = Approval::where('route_name', 'workbench_webcam')
        // ->where('date', $date)
        // ->where('time', $time)
        ->where('created_by', $userId)
        ->first();

    if (!$approval) {
        // If no approval record exists, create one
        $approval = Approval::create([
            'route_name' => 'workbench_webcam',
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'is_approved' => false,
            'approved_by' => null,
            'created_by' => $userId,
        ]);

        // For simplicity, we'll just inform the user
        return redirect()->route('pending.approval')->with('info', 'Your request for access has been submitted for approval.');
    } elseif ($approval->is_approved) {
        // If the approval is approved, allow access and log the visit
        // \App\Models\VisitLog::create([
        //     'user_id' => $userId,
        //     'route_name' => 'workbench_webcam',
        //     'date' => now()->toDateString(),
        //     'time' => now()->toTimeString(),
        //     'visited_at' => Carbon::now(),
        // ]);

        // Retrieve settings
        $timeoutWebCamValue = \App\Models\Setting::getSetting('timeout_webcam_value') ?? 60000;
        $webCamCaptureTime = \App\Models\Setting::getSetting('webcam_capture_time') ?? 3000;
        $currentTime = now()->toTimeString(); // Current hour in 24-hour format
        return view('filament.clusters.h-r-attenance-cluster.resources.test-search-by-image-resource.pages.view-camera', compact('currentTime', 'timeoutWebCamValue', 'webCamCaptureTime'));
    } else {
        // If the approval is pending, inform the user
        return redirect()->back()->with('warning', 'Your request for access is still pending approval.');
    }
})->name('workbench_webcam')
    ->middleware('check')
;

Route::get('pending_approval', function () {
    return view('pending-approval.v1.pending-approval');
})->name('pending.approval');

Route::get('/images', [ImageController::class, 'displayAllImages']);

// Route::get('workbench_webcam/{date}/{time}', function () {
//     $timeoutWebCamValue = \App\Models\Setting::getSetting('timeout_webcam_value') ?? 60000;
//     $webCamCaptureTime = \App\Models\Setting::getSetting('webcam_capture_time') ?? 3000;
//     $currentTime = \Carbon\Carbon::now()->format('H'); // Current hour in 24-hour format
//     return view('filament.clusters.h-r-attenance-cluster.resources.test-search-by-image-resource.pages.view-camera', compact('currentTime', 'timeoutWebCamValue', 'webCamCaptureTime'));
// })
//     ->middleware('check')
// ;


Route::get('workbench_webcam_v2', function () {
    $currentTime = \Carbon\Carbon::now()->format('H'); // Current hour in 24-hour format
    
    return view('filament.clusters.h-r-attenance-cluster.resources.test-search-by-image-resource.pages.view-camera-v3', compact('currentTime'));
});

Route::post('/upload-captured-image', [EmployeeAWSController::class, 'uploadCapturedImage_old'])->name('upload.captured.image');


Route::get('getAttendancesEarlyDeparture', function () {
    $attendances = Attendance::earlyDepartures()
        ->whereYear('check_date', '2024')
        ->whereMonth('check_date', '11')
        // ->where('employee_id', 83)
        ->select('id', 'employee_id', 'check_date', 'check_time', 'early_departure_minutes', 'period_id')
        ->where('check_type', Attendance::CHECKTYPEOUT)
        ->where('early_departure_minutes', '<=', 20)
        ->get()
        ->groupBy('employee_id')
        ->map(function ($attendances) {
            return $attendances->toArray();
        })
        ->toArray();
    $result = [];
    foreach ($attendances as $key => $value) {
        $result[Employee::find($key)->name . '-' . $key] = $value;
        foreach ($value as  $val) {
            DB::beginTransaction();
            try {
                Attendance::find($val['id'])->update([
                    'status' => Attendance::STATUS_ON_TIME,
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }

    dd($result);
});
Route::get('getAttendancesLateArrival', function () {
    $attendances = Attendance::lateArrival()
        ->whereYear('check_date', '2024')
        ->whereMonth('check_date', '11')
        // ->where('employee_id', 83)
        ->select('id', 'employee_id', 'check_date', 'check_time', 'delay_minutes', 'period_id')
        ->get()
        // ->groupBy('employee_id')
        // ->map(function ($attendances) {
        //     return $attendances->toArray();
        // })
        ->toArray();
    dd($attendances);
    $result = [];
    foreach ($attendances as $key => $value) {
        $result[Employee::find($key)->name . '-' . $key] = $value;
        foreach ($value as  $val) {
            DB::beginTransaction();
            try {
                // Attendance::find($val['id'])->update([
                //     'status' => Attendance::STATUS_ON_TIME,
                // ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }

    dd($result);
});
