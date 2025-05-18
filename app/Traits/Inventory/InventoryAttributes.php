<?php 
namespace App\Traits\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

trait InventoryAttributes
{
  
    public function getMovementTypeTitleAttribute()
    {
        return $this->movement_type === InventoryTransaction::MOVEMENT_IN ? 'In' : 'Out';
    }

    public function getFormattedTransactionableTypeAttribute()
    {
        if (!$this->transactionable_type) {
            return null;
        }

        return preg_replace('/(?<!\ )[A-Z]/', ' $0', class_basename($this->transactionable_type));
    }
}
