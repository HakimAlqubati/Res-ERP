<?php

namespace App\Modules\HR\Payroll\Reports;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Payroll;
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

        // Sort transactions by date
        $transactions = $payroll->transactions()->orderBy('date')->get();

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

        // Exclude Carry Forward from the TOTAL sum, but keep them in the $deductions list for display
        $totalDeductions = $deductions->filter(function ($t) {
            return $t->type !== SalaryTransactionType::TYPE_CARRY_FORWARD->value;
        })->sum('amount');

        $net = max($gross - $totalDeductions, 0);
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
     * Generate and download the Salary Slip PDF.
     * 
     * @param int|string $payrollId
     * @return \Illuminate\Http\Response
     */
    public function generate($payrollId)
    {
        $data = $this->getData($payrollId);
        $payroll = $data['payroll'];

        $pdf = LaravelMpdf::loadView('reports.hr.payroll.salary-slip-pdf', $data);

        $filename = sprintf(
            'SalarySlip-%s-%s-%s.pdf',
            $payroll->employee?->name ?? '000',
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
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
