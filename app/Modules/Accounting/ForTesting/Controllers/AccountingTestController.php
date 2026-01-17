<?php

namespace App\Modules\Accounting\ForTesting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\ForTesting\Services\ChartOfAccountsService;
use Illuminate\View\View;

class AccountingTestController extends Controller
{
    protected ChartOfAccountsService $coaService;

    public function __construct(ChartOfAccountsService $coaService)
    {
        $this->coaService = $coaService;
    }

    /**
     * Display the Chart of Accounts tree.
     *
     * @return View
     */
    public function index(): View
    {
        $accounts = $this->coaService->getChartOfAccounts();
        return view('accounting.testing.coa_tree', compact('accounts'));
    }
}
