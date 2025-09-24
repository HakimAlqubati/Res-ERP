<?php 
namespace App\Notifications;

use Filament\Notifications\DatabaseNotification as BaseDatabaseNotification;
use Illuminate\Database\Eloquent\Model;

class SyncDatabaseNotification extends BaseDatabaseNotification
{
    // نحذف ShouldQueue
    // بحيث تنفذ مباشرة بدون queue
}
