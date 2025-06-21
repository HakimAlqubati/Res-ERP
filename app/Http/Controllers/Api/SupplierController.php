<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;

class SupplierController extends Controller
{
    /**
     * Get list of all suppliers as JSON
     */
    public function index()
    {
        $suppliers = Supplier::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }
}
