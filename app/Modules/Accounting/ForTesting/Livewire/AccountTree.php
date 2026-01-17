<?php

namespace App\Modules\Accounting\ForTesting\Livewire;

use App\Models\Account;
use Livewire\Component;

class AccountTree extends Component
{
    public $expandedNodes = [];
    public $search = '';
    public $allExpanded = false;

    public function toggleNode($nodeId)
    {
        if (in_array($nodeId, $this->expandedNodes)) {
            $this->expandedNodes = array_diff($this->expandedNodes, [$nodeId]);
        } else {
            $this->expandedNodes[] = $nodeId;
        }
    }

    public function expandAll()
    {
        $this->expandedNodes = Account::whereNotNull('parent_id')
            ->pluck('parent_id')
            ->merge(Account::whereNull('parent_id')->pluck('id'))
            ->unique()
            ->toArray();
        $this->allExpanded = true;
    }

    public function collapseAll()
    {
        $this->expandedNodes = [];
        $this->allExpanded = false;
    }

    public function render()
    {
        $accounts = Account::orderBy('account_code')->get();
        $tree = $this->buildTree($accounts);
        $totalAccounts = $accounts->count();

        return view('accounting-testing::livewire-account-tree', [
            'tree' => $tree,
            'totalAccounts' => $totalAccounts,
        ]);
    }

    protected function buildTree($accounts, $parentId = null): array
    {
        $branch = [];

        foreach ($accounts as $account) {
            if ($account->parent_id == $parentId) {
                $children = $this->buildTree($accounts, $account->id);

                // Skip if searching and no match
                if ($this->search) {
                    $matchesSelf = str_contains(strtolower($account->account_name), strtolower($this->search))
                        || str_contains($account->account_code, $this->search);
                    $hasMatchingChildren = count($children) > 0;

                    if (!$matchesSelf && !$hasMatchingChildren) {
                        continue;
                    }
                }

                $node = [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $this->getAccountType($account->account_code),
                    'has_children' => count($children) > 0,
                    'children' => $children,
                    'children_count' => $this->countAllChildren($account->id, $accounts),
                ];

                $branch[] = $node;
            }
        }

        return $branch;
    }

    protected function getAccountType($code): string
    {
        $firstDigit = substr($code, 0, 1);
        return match ($firstDigit) {
            '1' => 'assets',
            '2' => 'liabilities',
            '3' => 'equity',
            '4' => 'revenue',
            '5' => 'expenses',
            default => 'other',
        };
    }

    protected function countAllChildren($parentId, $accounts): int
    {
        $count = 0;
        foreach ($accounts as $account) {
            if ($account->parent_id == $parentId) {
                $count++;
                $count += $this->countAllChildren($account->id, $accounts);
            }
        }
        return $count;
    }
}
