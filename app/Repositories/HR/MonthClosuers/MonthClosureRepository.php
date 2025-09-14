<?php 
// app/Repositories/Hr/MonthClosureRepository.php

namespace App\Repositories\HR\MonthClosuers;

use App\Models\MonthClosure;

class MonthClosureRepository
{
     public function addClosure($year, $month, $notes = null, $closedBy = null, $meta = null)
    {
        return MonthClosure::updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'status' => MonthClosure::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => $closedBy,
                'notes' => $notes,
                'meta' => $meta,
            ]
        );
    }

    public function approveClosure($year, $month)
    {
        return MonthClosure::where('year', $year)
            ->where('month', $month)
            ->update([
                'status' => MonthClosure::STATUS_APPROVED,
            ]);
    }

    public function openClosure($year, $month)
    {
        return MonthClosure::where('year', $year)
            ->where('month', $month)
            ->update([
                'status' => MonthClosure::STATUS_OPEN,
            ]);
    }

    public function isMonthClosed($year, $month)
    {
        return MonthClosure::where('year', $year)
            ->where('month', $month)
            ->where('status', MonthClosure::STATUS_CLOSED)
            ->exists();
    }

    public function getClosure($year, $month)
    {
        return MonthClosure::where('year', $year)
            ->where('month', $month)
            ->first();
    }
}