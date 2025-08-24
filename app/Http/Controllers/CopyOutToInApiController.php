<?php

namespace App\Http\Controllers;

use App\Jobs\CopyOutToInForOrdersJob;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Services\CopyOrderOutToBranchStoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CopyOutToInApiController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'min:1'], // اجعله مطلوب أو استخدم nullable بدل required
        ]);

        $branchId = $data['branch_id'];




        app(CopyOrderOutToBranchStoreService::class)->handle((int)$data['branch_id']);


        return response()->json([
            'ok' => true,
            'queued' => true,
            'branch_id' => $branchId,
            'message' => 'Job dispatched to queue "inventory".',
        ]);
    }
}
