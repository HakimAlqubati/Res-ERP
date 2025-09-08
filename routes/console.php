<?php

use App\Filament\Resources\TenantResource;
use App\Jobs\TestJob;
use App\Mail\GeneralNotificationMail;
use App\Models\CustomTenantModel;
use App\Models\NotificationSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Log::info('✅ Closure every minute — ' . now());

    // $tenants = CustomTenantModel::where('active', 1)->get();

    // foreach ($tenants as $tenant) {
    //     try {
    //         TenantResource::generateTenantBackup($tenant);
    //         Log::info('Backup successful for tenant: ' . $tenant->name);
    //     } catch (\Throwable $e) {
    //         Log::error('Backup failed for tenant: ' . $tenant->name, [
    //             'error' => $e->getMessage(),
    //         ]);
    //     }
    // }
    // Artisan::command('test:cron', function () {
    //     $this->info('Test Cron executed at ' . now());
    //     Log::info('🟢 TestCron executed at ' . now());
    // })->describe('My test cron command');
})->everyTwoMinutes();
// })->everySixHours();
Schedule::command('test:cron')->everyMinute();