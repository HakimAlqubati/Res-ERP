<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Providers;

use App\Models\EmployeeFileType;
use App\Models\EmployeeFileTypeField;
use App\Modules\HR\EmployeeDocuments\Contracts\DocumentReportServiceInterface;
use App\Modules\HR\EmployeeDocuments\Notifications\DocumentExpiryDeduplicator;
use App\Modules\HR\EmployeeDocuments\Notifications\DocumentExpiryNotifier;
use App\Modules\HR\EmployeeDocuments\Notifications\DocumentExpiryRecipientResolver;
use App\Modules\HR\EmployeeDocuments\Observers\EmployeeFileTypeCacheObserver;
use App\Modules\HR\EmployeeDocuments\Services\DocumentBackfillService;
use App\Modules\HR\EmployeeDocuments\Services\DocumentRenewalService;
use App\Modules\HR\EmployeeDocuments\Services\DocumentReportService;
use App\Modules\HR\EmployeeDocuments\Support\DocumentStatusCalculator;
use App\Modules\HR\EmployeeDocuments\Support\DynamicFieldDateExtractor;
use App\Modules\HR\EmployeeDocuments\Support\EmployeeFileTypeCatalog;
use Illuminate\Support\ServiceProvider;

final class EmployeeDocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Scoped (not singleton) so Octane flushes request-level memoization cleanly.
        $this->app->scoped(EmployeeFileTypeCatalog::class);

        $this->app->singleton(DocumentStatusCalculator::class);
        $this->app->singleton(DynamicFieldDateExtractor::class);
        $this->app->singleton(DocumentExpiryRecipientResolver::class);
        $this->app->singleton(DocumentExpiryDeduplicator::class);

        $this->app->bind(
            DocumentReportServiceInterface::class,
            DocumentReportService::class
        );

        $this->app->bind(DocumentBackfillService::class);
        $this->app->bind(DocumentRenewalService::class);
        $this->app->bind(DocumentExpiryNotifier::class);
    }

    public function boot(): void
    {
        EmployeeFileType::observe(EmployeeFileTypeCacheObserver::class);
        EmployeeFileTypeField::observe(EmployeeFileTypeCacheObserver::class);
    }
}
