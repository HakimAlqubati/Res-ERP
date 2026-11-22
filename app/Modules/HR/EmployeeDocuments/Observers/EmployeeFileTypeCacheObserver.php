<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Observers;

use App\Modules\HR\EmployeeDocuments\Support\EmployeeFileTypeCatalog;

/**
 * Invalidates the tenant-isolated EmployeeFileTypeCatalog cache whenever
 * an EmployeeFileType or EmployeeFileTypeField changes.
 */
final class EmployeeFileTypeCacheObserver
{
    public function __construct(
        private readonly EmployeeFileTypeCatalog $catalog,
    ) {}

    public function saved(): void
    {
        $this->catalog->flush();
    }

    public function deleted(): void
    {
        $this->catalog->flush();
    }

    public function restored(): void
    {
        $this->catalog->flush();
    }
}
