<?php
// app/Services/HR/MonthClosureService.php

namespace App\Services\HR\MonthClosure;

use App\Models\MonthClosure;
use App\Repositories\Hr\MonthClosuers\MonthClosureRepository;
use Carbon\Carbon;

class MonthClosureService
{
    protected $repository;

    public function __construct(MonthClosureRepository $repository)
    {
        $this->repository = $repository;
    }

    public function checkIfMonthIsOpen($date)
    {
        $carbon = Carbon::parse($date);
        // الشهر يعتبر مفتوح إذا لم يكن في جدول الإقفالات بحالة closed أو approved
        $closure = $this->repository->getClosure($carbon->year, $carbon->month);
        return ! $closure || ! in_array($closure->status, [
            MonthClosure::STATUS_CLOSED,
            MonthClosure::STATUS_APPROVED,
        ]);
    }

    public function ensureMonthIsOpen($date)
    {
        $carbonDate = \Carbon\Carbon::parse($date);
        $monthName  = $carbonDate->format('F'); // July, August, etc.
        $year       = $carbonDate->format('Y');

        if (! $this->checkIfMonthIsOpen($date)) {
            throw new \Exception("The month of {$monthName} {$year} is closed. No operations can be performed for this period.");
        }
    }

    public function closeMonth($year, $month, $notes = null, $closedBy = null, $meta = null)
    {
        return $this->repository->addClosure($year, $month, $notes, $closedBy, $meta);
    }

    public function approveMonth($year, $month)
    {
        return $this->repository->approveClosure($year, $month);
    }

    public function openMonth($year, $month)
    {
        return $this->repository->openClosure($year, $month);
    }

    public function getClosure($year, $month)
    {
        return $this->repository->getClosure($year, $month);
    }

    public function ensureMonthIsOpenByYearMonth($year, $month)
    {
        $date = "{$year}-{$month}-01"; // بداية الشهر
        return $this->ensureMonthIsOpen($date);
    }
}