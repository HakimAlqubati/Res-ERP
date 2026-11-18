<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseDataDTO;
use App\Modules\HR\SalaryIncrease\DTOs\SalaryIncreaseFilterDTO;
use App\Modules\HR\SalaryIncrease\Http\Requests\RejectSalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\Http\Requests\StoreSalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\Http\Requests\UpdateSalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\Http\Resources\SalaryIncreaseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryIncreaseController extends Controller
{
    public function __construct(
        private readonly SalaryIncreaseServiceInterface $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $filter = SalaryIncreaseFilterDTO::fromArray($request->all());
        $items = $this->service->list($filter, $perPage);

        return response()->json([
            'success' => true,
            'data'    => SalaryIncreaseResource::collection($items->items()),
            'meta'    => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    public function statuses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'statuses'       => SalaryIncreaseRequest::statusOptions(),
                'increase_types' => SalaryIncreaseRequest::increaseTypeOptions(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->service->getById($id);

        return response()->json([
            'success' => true,
            'data'    => new SalaryIncreaseResource($record),
        ]);
    }

    public function store(StoreSalaryIncreaseRequest $request): JsonResponse
    {
        $dto = SalaryIncreaseDataDTO::fromArray($request->validated(), $request->user()?->id);
        $record = $this->service->create($dto);

        return response()->json([
            'success' => true,
            'message' => 'Salary increase request created successfully.',
            'data'    => new SalaryIncreaseResource($record),
        ], 201);
    }

    public function update(UpdateSalaryIncreaseRequest $request, int $id): JsonResponse
    {
        $existing = $this->service->getById($id);
        $dto = SalaryIncreaseDataDTO::fromArray(
            array_merge($request->validated(), ['employee_id' => $existing->employee_id]),
            $request->user()?->id
        );

        $record = $this->service->update($id, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Salary increase request updated successfully.',
            'data'    => new SalaryIncreaseResource($record),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $record = $this->service->approve($id, $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Salary increase request approved and employee salary updated.',
            'data'    => new SalaryIncreaseResource($record),
        ]);
    }

    public function reject(RejectSalaryIncreaseRequest $request, int $id): JsonResponse
    {
        $record = $this->service->reject(
            id: $id,
            rejectionReason: $request->validated('rejection_reason'),
            rejectorId: $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Salary increase request rejected successfully.',
            'data'    => new SalaryIncreaseResource($record),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Salary increase request deleted successfully.',
        ]);
    }
}
