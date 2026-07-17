<?php

namespace App\Modules\HR\Payroll\Reports;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Payroll;
use App\Models\SalaryTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class SalarySlipReport
{
    /**
     * Generate and download the Salary Slip PDF.
     * 
     * @param int|string $payrollId
     * @return \Illuminate\Http\Response
     */
    /**
     * Get the salary slip data.
     *
     * @param int|string $payrollId
     * @return array
     */
    public function getData($payrollId)
    {
        /** @var \App\Models\Payroll $payroll */
        $payroll = Payroll::with([
            'employee',
            'employee.department',
            'employee.position',
            'transactions',
        ])->findOrFail($payrollId);

        $payrollIds = Payroll::query()
            ->where('payroll_run_id', $payroll->payroll_run_id)
            ->where('employee_id', $payroll->employee_id)
            ->pluck('id');

        // Sort and merge similar transactions across branch split payroll rows.
        $transactions = $this->mergeSimilarTransactions(
            SalaryTransaction::query()
                ->with('payroll.branch')
                ->whereIn('payroll_id', $payrollIds)
                ->orderBy('date')
                ->get()
        );
        
        // Split transactions
        $earnings = $transactions->filter(fn($t) => $t->operation === '+');
        $deductions = $transactions->filter(fn($t) => $t->operation === '-');

        // Employer contributions (for display only)
        $employerContrib = $transactions->filter(fn($t) => $t->type === SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION->value);

        // Build interleaved deduction rows: each employee deduction followed by its matching employer contribution
        $deductionRows = collect();
        $matchedEmployerIds = [];

        foreach ($deductions->values() as $d) {
            // Add the employee deduction row
            $dDesc = $d->description ?: ucfirst(str_replace('_', ' ', $d->sub_type ?? ($d->type ?? '')));
            $deductionRows->push((object)[
                'description' => $dDesc,
                'amount'      => $d->amount,
                'isEmployer'  => false,
                'bgColor'     => $d->type === SalaryTransactionType::TYPE_CARRY_FORWARD->value ? '#ffe6e6' : null,
                'type'        => $d->type,
                'sub_type'    => $d->sub_type,
            ]);

            // Try to find matching employer contribution
            $matchingEc = null;

            // Generate a base slug for matching, removing "(employer)" strings
            $dBaseName = trim(str_ireplace(['(employer)', 'employer'], '', $dDesc));
            $dSlug = Str::slug($dBaseName);

            foreach ($employerContrib as $ec) {
                if (in_array($ec->id, $matchedEmployerIds)) continue;

                $ecDesc = $ec->description ?: ucfirst(str_replace('_', ' ', $ec->sub_type ?? ($ec->type ?? '')));
                $ecBaseName = trim(str_ireplace(['(employer)', 'employer'], '', $ecDesc));
                $ecSlug = Str::slug($ecBaseName);

                // 1. Match by sub_type if both are present
                if (!empty($d->sub_type) && !empty($ec->sub_type) && $d->sub_type === $ec->sub_type) {
                    $matchingEc = $ec;
                    break;
                }

                // 2. Match by base description slugs
                if (!empty($dSlug) && !empty($ecSlug) && $dSlug === $ecSlug) {
                    $matchingEc = $ec;
                    break;
                }

                // 3. Match if one slug contains the other
                if (!empty($dSlug) && !empty($ecSlug) && (Str::contains($ecSlug, $dSlug) || Str::contains($dSlug, $ecSlug))) {
                    $matchingEc = $ec;
                    break;
                }
            }

            if ($matchingEc) {
                $matchedEmployerIds[] = $matchingEc->id;
                $ecDesc = $matchingEc->description ?: ucfirst(str_replace('_', ' ', $matchingEc->sub_type ?? ''));
                $deductionRows->push((object)[
                    'description' => $ecDesc,
                    'amount'      => $matchingEc->amount,
                    'isEmployer'  => true,
                    'bgColor'     => '#e6ffc8',
                    'type'        => $matchingEc->type,
                    'sub_type'    => $matchingEc->sub_type,
                ]);
            }
        }

        // Add any unmatched employer contributions at the end
        foreach ($employerContrib as $ec) {
            if (in_array($ec->id, $matchedEmployerIds)) continue;
            $ecDesc = $ec->description ?: ucfirst(str_replace('_', ' ', $ec->sub_type ?? ''));
            $deductionRows->push((object)[
                'description' => $ecDesc,
                'amount'      => $ec->amount,
                'isEmployer'  => true,
                'bgColor'     => '#e6ffc8',
                'type'        => $ec->type,
                'sub_type'    => $ec->sub_type,
            ]);
        }

        // Totals
        $gross = $earnings->sum('amount');

        // Exclude *new* Carry Forward (deficit recording) from the TOTAL sum,
        // but include Carry Forward *recovery* (which has a reference_type).
        $totalDeductions = $deductions->filter(function ($t) {
            if ($t->type === SalaryTransactionType::TYPE_CARRY_FORWARD->value) {
                // If it's a recovery deduction, include it in the sum
                return $t->type === SalaryTransactionType::TYPE_CARRY_FORWARD->value;
            }
            return true;
        })->sum('amount');

        // $net = max($gross - $totalDeductions, 0);
        $net = $gross - $totalDeductions;
        $totalEmployer = $employerContrib->sum('amount');

        // Helper for words (placeholder)
        $amountInWords = function (float $value) {
            if (function_exists('number_to_words')) {
                // return number_to_words($value);
                return '';
            }
            return '';
        };

        return [
            'payroll'         => $payroll,
            'transactions'    => $transactions,
            'earnings'        => $earnings->values(),
            'deductions'      => $deductions->values(),
            'deductionRows'   => $deductionRows,
            'employerContrib' => $employerContrib->values(),
            'gross'           => $gross,
            'totalDeductions' => $totalDeductions,
            'net'             => $net,
            'totalEmployer'   => $totalEmployer,
            'amountInWords'   => $amountInWords($net),
        ];
    }

    /**
     * @param Collection<int, SalaryTransaction> $transactions
     * @return Collection<int, object>
     */
    private function mergeSimilarTransactions(Collection $transactions): Collection
    {
        return $transactions
            ->groupBy(fn(SalaryTransaction $transaction) => implode('|', [
                $transaction->operation,
                $transaction->type,
                $transaction->sub_type,
                $transaction->unit,
                $transaction->multiplier,
                $this->mergeLabel($transaction),
                $transaction->type === SalaryTransactionType::TYPE_SALARY->value ? $transaction->payroll?->branch_id : '',
            ]))
            ->map(function (Collection $group) {
                /** @var SalaryTransaction $first */
                $first = $group->first();
                $qtySum = $group->sum('qty');
                
                if ($first->type === SalaryTransactionType::TYPE_SALARY->value) {
                    $branchName = $first->payroll?->branch?->name;
                    $label = "Earned Basic Salary (Prorated) " . (float)$qtySum . " days" . ($branchName ? " - {$branchName}" : "");
                } else {
                    $label = $this->mergeLabel($first);
                }

                return (object) [
                    'id'          => $first->id,
                    'operation'   => $first->operation,
                    'type'        => $first->type,
                    'sub_type'    => $first->sub_type,
                    'description' => $label,
                    'notes'       => $first->notes,
                    'amount'      => round((float) $group->sum('amount'), 2),
                    'date'        => $first->date,
                    'unit'        => $first->unit,
                    'qty'         => $qtySum,
                    'rate'        => $first->rate,
                    'multiplier'  => $first->multiplier,
                ];
            })
            ->values();
    }

    private function mergeLabel(SalaryTransaction $transaction): string
    {
        if ($transaction->sub_type === 'base_salary') {
            return 'Base salary';
        }

        $label = $transaction->description
            ?: ucfirst(str_replace('_', ' ', $transaction->sub_type ?? ($transaction->type ?? '')));

        return trim((string) preg_replace('/\s*\([^)]*\d+\s*days?[^)]*\)\s*/i', ' ', $label));
    }

    /**
     * Generate and download the Salary Slip PDF.
     * 
     * @param int|string $payrollId
     * @return \Illuminate\Http\Response
     */
    public function generate($payrollId)
    {
        $data = $this->getData($payrollId);
        $payroll = $data['payroll'];
        // if (isStuff() &&  $payroll->status !== Payroll::STATUS_APPROVED) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('lang.cannot_view_unapproved_salary_slip')
        //     ]);
        // }
        $pdf = LaravelMpdf::loadView('reports.hr.payroll.salary-slip-pdf', $data);

        $filename = sprintf(
            'SalarySlip-%s-%s-%s.pdf',
            str_replace(['/', '\\'], '-', $payroll->employee?->name ?? '000'),
            $payroll->year,
            $payroll->month
        );

        // Use streamDownload for Livewire compatibility
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Return the Salary Slip data as JSON.
     * 
     * @param int|string $payrollId
     * @return \Illuminate\Http\JsonResponse
     */
    public function json($payrollId)
    {
        $data = $this->getData($payrollId);
        if (isStuff() &&  $data['payroll']->status !== Payroll::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => __('lang.cannot_view_unapproved_salary_slip')
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
