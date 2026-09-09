<?php

namespace App\Filament\Dev\Widgets;

use App\Models\Employee;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MaintenanceWidget extends Widget
{
    protected   string $view = 'filament.dev.widgets.maintenance-widget';

    // protected static int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function optimize()
    {
        Artisan::call('optimize:clear');
        Notification::make()->title('Optimization & Cache Cleared')->success()->send();
    }

    public function clearView()
    {
        Artisan::call('view:clear');
        Notification::make()->title('Views Cleared')->success()->send();
    }

    public function clearConfig()
    {
        Artisan::call('config:clear');
        Notification::make()->title('Config Cleared')->success()->send();
    }

    public function updateProductPrices()
    {
        try {
            $service = app(\App\Services\Products\UpdateBatchProductPricesService::class);
            $report = $service->execute();

            Notification::make()
                ->title('✅ Product Prices Updated')
                ->body("Processed {$report['products_processed']} products, updated {$report['unit_prices_updated']} unit prices and {$report['transactions_updated']} inventory transactions.")
                ->success()
                ->send();
        } catch (\Throwable $th) {
            Notification::make()
                ->title('❌ Price Update Failed')
                ->body($th->getMessage())
                ->danger()
                ->send();
        }
    }

  

 
    // ─────────────────────────────────────────────────────────────
    // Mask Employee Emails — Confirmation Modal
    // ─────────────────────────────────────────────────────────────

    /** Emails that must never be modified. */
    private const PROTECTED_EMAILS = [
        'hakimahmed123321@gmail.com',
        'yeolbyun2002@gmail.com',
        'awad.it@gmail.com',
        'loloaaaa185@gmail.com',
        'adelalqubati12@gmail.com',
    ];

    public bool   $showMaskModal  = false;
    public int    $puzzleA        = 0;
    public int    $puzzleB        = 0;
    public string $puzzleAnswer   = '';

    /** Open the confirmation modal and generate a fresh puzzle. */
    public function openMaskConfirmation(): void
    {
        $this->puzzleA      = rand(10, 50);
        $this->puzzleB      = rand(10, 50);
        $this->puzzleAnswer = '';
        $this->showMaskModal = true;
    }

    /** Close the modal without doing anything. */
    public function cancelMask(): void
    {
        $this->showMaskModal = false;
        $this->puzzleAnswer  = '';
    }

    /**
     * Validate the puzzle answer then append @test.com to every
     * employee email that is not in the protected list.
     */
    public function confirmMaskEmployeeEmails(): void
    {
        $expected = $this->puzzleA + $this->puzzleB;

        if ((int) $this->puzzleAnswer !== $expected) {
            Notification::make()
                ->title('Wrong Answer')
                ->body('Please solve the puzzle correctly to confirm.')
                ->danger()
                ->send();

            return;
        }

        $updated = 0;

        Employee::withoutGlobalScopes()
            ->whereNotIn('email', self::PROTECTED_EMAILS)
            ->chunkById(100, function ($employees) use (&$updated) {
                foreach ($employees as $employee) {
                    // Build email from name: remove all spaces then lowercase
                    $slug = Str::lower(str_replace(' ', '',  $employee->name . $employee->id));

                    $employee->email = $slug . '@test.com';
                    $employee->save();

                    $updated++;
                }
            });

        $this->showMaskModal = false;
        $this->puzzleAnswer  = '';

        Notification::make()
            ->title('Employee Emails Masked')
            ->body("Updated {$updated} employee email(s) successfully.")
            ->success()
            ->send();
    }

    // ─────────────────────────────────────────────────────────────
    // Randomize Employee Salaries — Confirmation Modal
    // ─────────────────────────────────────────────────────────────

    public bool   $showSalaryModal  = false;
    public int    $salaryPuzzleA    = 0;
    public int    $salaryPuzzleB    = 0;
    public string $salaryPuzzleAnswer = '';

    /** Open the salary confirmation modal with a fresh puzzle. */
    public function openSalaryConfirmation(): void
    {
        $this->salaryPuzzleA      = rand(10, 50);
        $this->salaryPuzzleB      = rand(10, 50);
        $this->salaryPuzzleAnswer = '';
        $this->showSalaryModal    = true;
    }

    /** Close the salary modal without doing anything. */
    public function cancelSalary(): void
    {
        $this->showSalaryModal    = false;
        $this->salaryPuzzleAnswer = '';
    }

    /**
     * Validate the puzzle then assign each employee a random salary
     * that is a clean multiple of 50 — between 1,000 and 2,950.
     * Formula: rand(20, 59) * 50  →  e.g. 1250, 1300, 1500, 2200, 2950
     */
    public function confirmRandomizeSalaries(): void
    {
        $expected = $this->salaryPuzzleA + $this->salaryPuzzleB;

        if ((int) $this->salaryPuzzleAnswer !== $expected) {
            Notification::make()
                ->title('Wrong Answer')
                ->body('Please solve the puzzle correctly to confirm.')
                ->danger()
                ->send();

            return;
        }

        $updated = 0;

        Employee::withoutGlobalScopes()
            ->chunkById(100, function ($employees) use (&$updated) {
                foreach ($employees as $employee) {
                    // Produces a clean multiple of 50: 1000, 1050, 1100 … 2950
                    $employee->salary = rand(20, 59) * 50;
                    $employee->save();

                    $updated++;
                }
            });

        $this->showSalaryModal    = false;
        $this->salaryPuzzleAnswer = '';

        Notification::make()
            ->title('Salaries Randomized')
            ->body("Updated {$updated} employee salary record(s) successfully.")
            ->success()
            ->send();
    }
}
