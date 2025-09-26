<?php
// app/Providers/WarningsServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Warnings\{WarningSender, DatabaseWarningSender};

final class WarningsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WarningSender::class, fn() => new DatabaseWarningSender());
        $this->app->alias(WarningSender::class, 'warnings'); // app('warnings')
    }
}
