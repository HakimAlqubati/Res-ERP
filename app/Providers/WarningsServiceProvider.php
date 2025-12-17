<?php
// app/Providers/WarningsServiceProvider.php
namespace App\Providers;

use App\Models\AppLog;
use Illuminate\Support\ServiceProvider;
use App\Services\Warnings\WarningSender;
use App\Services\Warnings\CompositeWarningSender;
use App\Services\Warnings\DatabaseWarningSender;
use App\Services\Warnings\EmailWarningSender;

final class WarningsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WarningSender::class, function () {
            $composite = new CompositeWarningSender();

            // Database channel (always enabled by default)
            if (config('notifications.channels.database.enabled', true)) {
                $composite->addSender(new DatabaseWarningSender());
            }

            // Email channel (disabled by default)
            $emailEnabled = config('notifications.channels.email.enabled', false);
            AppLog::write(
                "[WarningsServiceProvider] Email channel enabled: " . ($emailEnabled ? 'YES' : 'NO'),
                AppLog::LEVEL_INFO,
                'WarningsServiceProvider'
            );

            if ($emailEnabled) {
                $composite->addSender(new EmailWarningSender());
                AppLog::write(
                    "[WarningsServiceProvider] EmailWarningSender added",
                    AppLog::LEVEL_INFO,
                    'WarningsServiceProvider'
                );
            }

            // Future: Add more channels here
            // if (config('notifications.channels.fcm.enabled', false)) {
            //     $composite->addSender(new FCMWarningSender());
            // }

            return $composite;
        });

        $this->app->alias(WarningSender::class, 'warnings'); // app('warnings')
    }
}
