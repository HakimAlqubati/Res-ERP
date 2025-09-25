<?php
// File: app/Services/Inventory/Domain/InventoryAggregator.php
namespace App\Services\Inventory\Domain;

use App\Models\InventoryTransaction;

final class InventoryAggregator
{
    /** @param array<int, array|object> $movements */
    public function summarize(array $movements): array
    {
        $totals = []; // key: product_store

        foreach ($movements as $m) {
            $p  = $this->get($m, 'product_id');
            $s  = $this->get($m, 'store_id');
            if ($p === null || $s === null) continue;

            $mv = $this->normMove($this->get($m, 'movement'));
            $q  = (float) ($this->get($m, 'qty') ?? 0);
            $ps = (float) ($this->get($m, 'package_size') ?? 1);
            if ($ps <= 0) $ps = 1;
            $qs = (float) ($this->get($m, 'qty_smallest_unit') ?? ($q * $ps));

            $key = $p . '_' . $s;
            if (!isset($totals[$key])) {
                $totals[$key] = ['product_id' => (int)$p, 'store_id' => (int)$s, 'in_smallest' => 0.0, 'out_smallest' => 0.0];
            }

            if ($mv === InventoryTransaction::MOVEMENT_IN) {
                $totals[$key]['in_smallest']  += $qs;
            } else {
                $totals[$key]['out_smallest'] += $qs;
            }
        }

        // تهذيب وإضافة الرصيد
        foreach ($totals as &$r) {
            $r['in_smallest']        = round($r['in_smallest'], 6);
            $r['out_smallest']       = round($r['out_smallest'], 6);
            $r['remaining_smallest'] = round($r['in_smallest'] - $r['out_smallest'], 6);
        }
        unset($r);
        return array_values($totals);
        return ['totals' => array_values($totals)];
    }

    private function get($row, string $k)
    {
        return is_array($row) ? ($row[$k] ?? null) : (is_object($row) ? ($row->{$k} ?? null) : null);
    }

    private function normMove($m): string
    {
        if ($m === InventoryTransaction::MOVEMENT_IN || $m === InventoryTransaction::MOVEMENT_OUT) return $m;
        return strtoupper((string)$m) === 'IN' ? InventoryTransaction::MOVEMENT_IN : InventoryTransaction::MOVEMENT_OUT;
    }
}
