<?php

use App\Mail\GeneralNotificationMail;
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
    $now = Carbon::now();

    NotificationSetting::where('active', true)->each(function ($setting) use ($now) {
        $shouldRun = match ($setting->frequency) {
            'every_minute' => true,
            'hourly' => $now->minute === 0,
            'daily' => $setting->daily_time && $now->format('H:i') === Carbon::parse($setting->daily_time)->format('H:i'),
            default => false,
        };

        if (! $shouldRun) {
            return;
        }

        // Send based on type
        $title = match ($setting->type) {
            'stock_min_quantity' => 'تنبيه: منتجات اقتربت من الحد الأدنى',
            'employee_forget_attendance' => 'موظفون نسوا تسجيل الحضور',
            'absent_employees' => 'تقرير الغياب اليومي',
            'task_scheduling' => 'مهام مجدولة اليوم',
            default => 'تنبيه عام',
        };

        $message = match ($setting->type) {
            'stock_min_quantity' => 'قائمة بالمنتجات التي اقتربت من الحد الأدنى.',
            'employee_forget_attendance' => 'يوجد موظفون لم يسجلوا حضورهم اليوم.',
            'absent_employees' => 'قائمة بالموظفين المتغيبين اليوم.',
            'task_scheduling' => 'لديك مهام مجدولة اليوم.',
            default => null,
        };

        if ($message) {

            // Send to all users (or customize per setting)
            // User::all()->each(function ($user) use ($title, $message) {

            $recipients = [
                'adelalqubati12@gmail.com',
                'hakimahmed123321@gmail.com',
            ];

            foreach ($recipients as $email) {
                Mail::to($email)->send(new GeneralNotificationMail($title, $message));
            }

            // });
        }
    });
})->everyMinute();
