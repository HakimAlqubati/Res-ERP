<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Providers;

use App\Modules\HR\Holidays\Contracts\HijriDateConverterInterface;
use App\Modules\HR\Holidays\Contracts\HolidayOccurrenceServiceInterface;
use App\Modules\HR\Holidays\Services\HijriDateConverterService;
use App\Modules\HR\Holidays\Services\HolidayDateResolverService;
use App\Modules\HR\Holidays\Services\HolidayOccurrenceService;
use Illuminate\Support\ServiceProvider;

final class HolidayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            HijriDateConverterInterface::class,
            HijriDateConverterService::class
        );

        $this->app->singleton(HolidayDateResolverService::class);

        $this->app->bind(
            HolidayOccurrenceServiceInterface::class,
            HolidayOccurrenceService::class
        );
    }

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            HijriDateConverterInterface::class,
            HolidayDateResolverService::class,
            HolidayOccurrenceServiceInterface::class,
        ];
    }
}
