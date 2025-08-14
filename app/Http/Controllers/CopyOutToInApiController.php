<?php

namespace App\Http\Controllers;

use App\Jobs\CopyOutToInForOrdersJob;
use Illuminate\Http\Request;

class CopyOutToInApiController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['nullable','integer','min:1'],
        ]);

        $branchId = $data['branch_id'] ?? null;

        // نستدعي نفس الـ Job الموجودة لديك: (tenant=null, branch=$branchId)
        CopyOutToInForOrdersJob::dispatch(null, $branchId);

        return response()->json([
            'ok' => true,
            'queued' => true,
            'branch_id' => $branchId,
            'message' => 'Job dispatched to queue "inventory".',
        ]);
    }
}
