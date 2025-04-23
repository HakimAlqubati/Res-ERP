<?php 
namespace App\Filament\Clusters\AccountingCluster\Resources\AccountResource\Pages;

use App\Filament\Clusters\AccountingCluster\Resources\AccountResource;
use Filament\Resources\Pages\Page;
use App\Models\Account;


class AccountingDirectoryTree extends Page
{
    protected static string $resource = AccountResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static string $view = 'filament.pages.accounting-reports.accounting-directory-tree';

    protected static ?string $title = 'Accounting Directory Tree';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 100;

    public function getViewData(): array
    {
        // الحسابات الجذرية (الرئيسية)
        $accounts = Account::with('children')->whereNull('parent_id')->orderBy('code')->get();
    
        // استخراج حساب الموردين التحليلي: النوع = liability + ليس له أبناء + مرتبط بـ suppliers
        $suppliersAccount = Account::where('type', Account::TYPE_LIABILITY)
            ->whereHas('suppliers') // يضمن أنه مرتبط فعليًا بموردين
            ->doesntHave('children') // مو حساب رئيسي آخر داخلي
            ->first();
    
        $suppliers = \App\Models\Supplier::all();
    
        return [
            'accounts' => $accounts,
            'suppliersParentId' => $suppliersAccount?->id,
            'suppliers' => $suppliers,
        ];
    }
    
}