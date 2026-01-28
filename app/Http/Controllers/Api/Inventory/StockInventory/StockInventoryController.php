<?php

namespace App\Http\Controllers\Api\Inventory\StockInventory;

use App\DTOs\Inventory\StockInventory\StockInventoryDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockInventory\StoreStockInventoryRequest;
use App\Http\Requests\Inventory\StockInventory\UpdateStockInventoryRequest;
use App\Http\Resources\Inventory\StockInventory\StockInventoryCollection;
use App\Http\Resources\Inventory\StockInventory\StockInventoryResource;
use App\Services\Inventory\StockInventory\StockInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class StockInventoryController extends Controller
{
    /**
     * @var StockInventoryService
     */
    protected StockInventoryService $service;

    /**
     * StockInventoryController constructor.
     *
     * @param StockInventoryService $service
     */
    public function __construct(StockInventoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of stock inventories.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'store_id',
                'finalized',
                'date_from',
                'date_to',
                'responsible_user_id',
                'include_details',
                'sort_by',
                'sort_direction'
            ]);

            $perPage = (int) $request->get('per_page', 15);

            $inventories = $this->service->getPaginated($filters, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Stock inventories retrieved successfully',
                'data' => new StockInventoryCollection($inventories),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock inventories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created stock inventory.
     *
     * @param StoreStockInventoryRequest $request
     * @return JsonResponse
     */
    public function store(StoreStockInventoryRequest $request): JsonResponse
    {
        try {
            $dto = StockInventoryDto::fromRequest($request->validated());

            $inventory = $this->service->create($dto);

            return response()->json([
                'success' => true,
                'message' => 'Stock inventory created successfully',
                'data' => new StockInventoryResource($inventory),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock inventory',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified stock inventory.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $inventory = $this->service->getById($id);

            if (!$inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock inventory not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock inventory retrieved successfully',
                'data' => new StockInventoryResource($inventory),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock inventory',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified stock inventory.
     *
     * @param UpdateStockInventoryRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateStockInventoryRequest $request, int $id): JsonResponse
    {
        try {
            $inventory = $this->service->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stock inventory updated successfully',
                'data' => new StockInventoryResource($inventory),
            ]);
        } catch (Exception $e) {
            $statusCode = $e->getMessage() === 'Stock inventory not found' ? 404 : ($e->getMessage() === 'Cannot update finalized inventory' ? 403 : 500);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Remove the specified stock inventory.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Stock inventory deleted successfully',
            ]);
        } catch (Exception $e) {
            $statusCode = $e->getMessage() === 'Stock inventory not found' ? 404 : ($e->getMessage() === 'Cannot delete finalized inventory' ? 403 : 500);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Finalize a stock inventory.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function finalize(int $id): JsonResponse
    {
        try {
            $inventory = $this->service->finalize($id);

            return response()->json([
                'success' => true,
                'message' => 'Stock inventory finalized successfully',
                'data' => new StockInventoryResource($inventory),
            ]);
        } catch (Exception $e) {
            $statusCode = $e->getMessage() === 'Stock inventory not found' ? 404 : (str_contains($e->getMessage(), 'already finalized') ? 400 : 500);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
