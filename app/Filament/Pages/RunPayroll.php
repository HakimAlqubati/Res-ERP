<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;
use Filament\Resources\Pages\Page;

class RunPayroll extends Page
{
    protected static string $resource = PayrollResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.run-payroll';
}
