<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up any stale lock on hr_employee_overtime if a previous migration attempt was interrupted
        try {
            foreach (DB::select('SHOW FULL PROCESSLIST') as $proc) {
                $info = $proc->Info ?? '';
                if (
                    is_string($info)
                    && str_contains($info, 'hr_employee_overtime')
                    && ! str_contains($info, 'PROCESSLIST')
                ) {
                    DB::statement('KILL ' . (int) $proc->Id);
                }
            }
        } catch (\Throwable $e) {
            //
        }

        if (! Schema::hasColumn('hr_employee_overtime', 'period_id')) {
            Schema::table('hr_employee_overtime', function (Blueprint $table) {
                $table->unsignedBigInteger('period_id')->nullable()->after('date');
            });
        }

        try {
            Schema::table('hr_employee_overtime', function (Blueprint $table) {
                $table->index(
                    ['employee_id', 'date', 'type', 'period_id'],
                    'hr_emp_overtime_emp_date_type_period_idx'
                );
            });
        } catch (\Throwable $e) {
            // Index already exists
        }

        try {
            Schema::table('hr_employee_overtime', function (Blueprint $table) {
                $table->dropUnique(['employee_id', 'date', 'type']);
            });
        } catch (\Throwable $e) {
            // Index already dropped or does not exist
        }

        // Non-locking MVCC backfill of legacy based_on_day overtime rows
        if (Schema::hasTable('hr_attendances')) {
            $overtimeRows = DB::table('hr_employee_overtime')
                ->where('type', 'based_on_day')
                ->whereNull('period_id')
                ->get(['id', 'employee_id', 'date', 'start_time']);

            if ($overtimeRows->isNotEmpty()) {
                $empIds = $overtimeRows->pluck('employee_id')->unique()->values()->all();
                $dates = $overtimeRows->pluck('date')->unique()->values()->all();

                $attendances = DB::table('hr_attendances')
                    ->whereIn('employee_id', $empIds)
                    ->whereIn('check_date', $dates)
                    ->where('accepted', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('id', 'asc')
                    ->get(['employee_id', 'check_date', 'check_time', 'check_type', 'period_id', 'status'])
                    ->groupBy(fn ($a) => $a->employee_id . '_' . $a->check_date);

                foreach ($overtimeRows as $eo) {
                    $key = $eo->employee_id . '_' . $eo->date;
                    $dayAtts = $attendances->get($key);
                    if (! $dayAtts || $dayAtts->isEmpty()) {
                        continue;
                    }

                    $resolvedPeriodId = null;

                    if (! empty($eo->start_time)) {
                        $targetHm = substr((string) $eo->start_time, 0, 5);
                        $matchedCheckIn = $dayAtts
                            ->where('check_type', 'checkin')
                            ->first(fn ($a) => $a->period_id !== null && substr((string) $a->check_time, 0, 5) === $targetHm);

                        if ($matchedCheckIn) {
                            $resolvedPeriodId = (int) $matchedCheckIn->period_id;
                        }
                    }

                    if ($resolvedPeriodId === null) {
                        $hasNoShift = $dayAtts->contains(fn ($a) => $a->period_id === null || $a->status === 'no_shift');
                        if (! $hasNoShift) {
                            $distinctPeriods = $dayAtts->pluck('period_id')->filter()->unique()->values();
                            if ($distinctPeriods->count() === 1) {
                                $resolvedPeriodId = (int) $distinctPeriods->first();
                            }
                        }
                    }

                    if ($resolvedPeriodId !== null) {
                        DB::table('hr_employee_overtime')
                            ->where('id', $eo->id)
                            ->update(['period_id' => $resolvedPeriodId]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('hr_employee_overtime', function (Blueprint $table) {
                $table->dropIndex('hr_emp_overtime_emp_date_type_period_idx');
            });
        } catch (\Throwable $e) {
            //
        }

        if (Schema::hasColumn('hr_employee_overtime', 'period_id')) {
            Schema::table('hr_employee_overtime', function (Blueprint $table) {
                $table->dropColumn('period_id');
            });
        }
    }
};
