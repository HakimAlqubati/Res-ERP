<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Throwable;

class StoreController extends Controller
{
    /**
     * Display a listing of all stores as key-value pairs (id => name).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $stores = Store::orderBy('id')->pluck('name', 'id');

            return response()->json([
                'success' => true,
                'count'   => $stores->count(),
                'data'    => $stores,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
