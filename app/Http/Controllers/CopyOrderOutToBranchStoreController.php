<?php

namespace App\Http\Controllers;

use App\Services\CopyOrderOutToBranchStoreService;
use Illuminate\Http\Request;

class CopyOrderOutToBranchStoreController extends Controller
{
    public function handle(Request $request)
    {
        $service = new CopyOrderOutToBranchStoreService();
        $service->handle();

        return response()->json(['message' => 'Copying OUT transactions to IN for branch store initiated.']);
    }
}
