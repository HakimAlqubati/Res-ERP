<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SalaryIncreaseRequest;
use Illuminate\Validation\ValidationException;

class SalaryIncreaseRequestObserver
{
    public function deleting(SalaryIncreaseRequest $request): void
    {
        if ($request->isApproved()) {
            throw ValidationException::withMessages([
                'status' => ['Approved salary increase requests cannot be deleted.'],
            ]);
        }
    }

    public function forceDeleting(SalaryIncreaseRequest $request): void
    {
        if ($request->isApproved()) {
            throw ValidationException::withMessages([
                'status' => ['Approved salary increase requests cannot be permanently deleted.'],
            ]);
        }
    }

    public function restoring(SalaryIncreaseRequest $request): void
    {
        if ($request->isApproved()) {
            throw ValidationException::withMessages([
                'status' => ['Approved salary increase requests cannot be restored.'],
            ]);
        }
    }
}
