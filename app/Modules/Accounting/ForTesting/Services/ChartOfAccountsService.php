<?php

namespace App\Modules\Accounting\ForTesting\Services;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

class ChartOfAccountsService
{
    /**
     * Get the Chart of Accounts from database and build the tree.
     *
     * @return Collection|array
     */
    public function getChartOfAccounts()
    {
        $accounts = Account::orderBy('account_code')->get();
        return $this->buildTree($accounts);
    }

    /**
     * Build hierarchical tree from flat collection.
     *
     * @param Collection $accounts
     * @param int|null $parentId
     * @return array
     */
    protected function buildTree($accounts, $parentId = null): array
    {
        $branch = [];

        foreach ($accounts as $account) {
            if ($account->parent_id == $parentId) {
                $children = $this->buildTree($accounts, $account->id);

                $node = $account->toArray();
                if ($children) {
                    $node['children'] = $children;
                }

                $branch[] = $node;
            }
        }

        return $branch;
    }
}
