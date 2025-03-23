<?php

namespace App\Console\Commands;

use App\Mail\GeneralNotificationMail;
use App\Models\NotificationSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCustomNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:custom';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        NotificationSetting::where('active', true)->chunk(50, function ($settings) use ($now) {
            foreach ($settings as $setting) {
                $shouldRun = match ($setting->frequency) {
                    'every_minute' => true,
                    'hourly' => $now->minute === 0,
                    'daily' => $setting->daily_time && $now->format('H:i') === Carbon::parse($setting->daily_time)->format('H:i'),
                    default => false,
                };

                if (! $shouldRun) {
                    continue;
                }

                // You can customize this logic to send to specific users per setting
                $users = User::all(); // or scoped to roles, departments, etc.

                foreach ($users as $user) {
                    $title = $this->getTitle($setting->type);
                    $message = $this->getMessage($setting->type, $user);

                    if ($message) {
                        Mail::to($user->email)->send(new GeneralNotificationMail($title, $message));
                    }
                }
            }
        });

        $this->info('Notifications dispatched based on active settings.');
    }
    protected function getTitle(string $type): string
    {
        return match ($type) {
            'stock_min_quantity' => 'تنبيه: منتجات اقتربت من الحد الأدنى',
            'employee_forget_attendance' => 'موظفون نسوا تسجيل الحضور',
            'absent_employees' => 'تقرير الغياب اليومي',
            'task_scheduling' => 'مهام مجدولة اليوم',
            default => 'تنبيه عام',
        };
    }

    protected function getMessage(string $type, $user): ?string
    {
        // Place your logic here per type:
        return match ($type) {
            'stock_min_quantity' => $this->stockAlertMessage(),
            'employee_forget_attendance' => $this->employeeForgetMessage(),
            'absent_employees' => $this->absentEmployeesMessage(),
            'task_scheduling' => $this->taskScheduleMessage($user),
            default => null,
        };
    }

    protected function stockAlertMessage(): ?string
    {
        // $inventoryService = new \App\Services\MultiProductsInventoryService();
        // $items = $inventoryService->getProductsBelowMinimumQuantity();

        // if ($items->isEmpty()) return null;

        return 'المنتجات التالية اقتربت من النفاد';
        // $lines = [];
        // foreach ($items as $item) {
        //     $name = json_decode($item['product_name'], true);
        //     $lines[] = "- {$name['ar'] ?? '---'} (باقي: {$item['remaining_qty']} {$item['unit_name']})";
        // }

        // return "المنتجات التالية اقتربت من النفاد:\n" . implode("\n", $lines);
    }

    protected function employeeForgetMessage(): ?string
    {
        // Replace with your own logic
        return "يوجد موظفون لم يسجلوا حضورهم اليوم.";
    }

    protected function absentEmployeesMessage(): ?string
    {
        // Replace with your own logic
        return "هذه قائمة بالموظفين المتغيبين اليوم.";
    }

    protected function taskScheduleMessage($user): ?string
    {
        // Example: you may fetch today's tasks for the user
        return "تذكير: لديك مهام مجدولة اليوم، يرجى مراجعتها.";
    }
}
