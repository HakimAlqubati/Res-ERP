<?php

namespace App\Filament\Dev\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class QueueManagerWidget extends Widget
{
    protected string $view = 'filament.dev.widgets.queue-manager-widget';

    protected static ?int $sort = 3;

    public function clearQueue()
    {
        Artisan::call('queue:clear');
        Notification::make()->title('Queue Cleared')->body('All pending jobs have been removed.')->success()->send();
    }

    public function flushFailedJobs()
    {
        Artisan::call('queue:flush');
        Notification::make()->title('Failed Jobs Flushed')->body('All failed jobs have been deleted permanently.')->success()->send();
    }

    public function restartQueue()
    {
        Artisan::call('queue:restart');
        Notification::make()->title('Queue Restarted')->body('A signal has been sent to workers to restart upon completing their current job.')->success()->send();
    }

    public function retryAllFailedJobs()
    {
        Artisan::call('queue:retry', ['id' => 'all']);
        Notification::make()->title('Retry Submitted')->body('All failed jobs have been pushed back to the queue for retry.')->success()->send();
    }
}
