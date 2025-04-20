<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\OrderDetails;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class OrdersImport implements ToCollection
{
    protected $currentOrder = null;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $rowType = trim(strtolower($row[0]));

                if ($rowType === 'header') {
                    $this->currentOrder = Order::create([
                        'id' => $row[1],
                        'branch_id' => $row[2],
                        'customer_id' => $row[4],
                        'status' => $row[6] ?? Order::ORDERED,
                        'notes' => $row[7],
                        'created_at' => $row[8],
                        'is_purchased' => 0,
                    ]);
                }

                if ($rowType === 'detail' && $this->currentOrder) {
                    OrderDetails::create([
                        'order_id' => $this->currentOrder->id,
                        'product_id' => $row[9],
                        'unit_id' => $row[11],
                        'quantity' => $row[13],
                        'price' => $row[14],
                        'available_quantity' => $row[15],
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
