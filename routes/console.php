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
 
// Schedule::call(function () {})->everyTwoMinutes();
Schedule::command('tenant:backup')
    ->everyTwoMinutes();
Schedule::command('test:cron')->everyMinute();
