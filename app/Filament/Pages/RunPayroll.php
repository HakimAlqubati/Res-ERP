<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

class RunPayroll extends Page
{
    protected static string $resource = PayrollResource::class;
    // protected static ?string $navigationIcon = Heroicon::DocumentText;

    protected   string $view = 'filament.pages.run-payroll';
}
