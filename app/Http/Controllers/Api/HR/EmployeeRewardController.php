<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HR\Rewards\RewardActionRequest;
use App\Http\Requests\Api\HR\Rewards\StoreEmployeeRewardRequest;
use App\Http\Requests\Api\HR\Rewards\UpdateEmployeeRewardRequest;
use App\Http\Resources\HR\EmployeeRewardResource;
use App\Services\HR\Rewards\Contracts\EmployeeRewardServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeRewardController extends Controller
{
    /**
     * @param EmployeeRewardServiceInterface $rewardService
     */
    public function __construct(
        protected EmployeeRewardServiceInterface $rewardService
    ) {}

    /**
     * Display a paginated listing of records.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $rewards = $this->rewardService->getRewardsList(
            $request->all(),
            $request->integer('per_page', 15)
        );

        return EmployeeRewardResource::collection($rewards);
    }

    /**
     * Store a newly created record.
     *
     * @param StoreEmployeeRewardRequest $request
     * @return JsonResponse
     */
    public function store(StoreEmployeeRewardRequest $request): JsonResponse
    {
        $reward = $this->rewardService->createReward($request->validated());

        return (new EmployeeRewardResource($reward))
            ->additional([
                'success' => true,
                'message' => __('lang.reward_created_successfully'),
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified record.
     *
     * @param int $id
     * @return EmployeeRewardResource|JsonResponse
     */
    public function show(int $id): EmployeeRewardResource|JsonResponse
    {
        $reward = $this->rewardService->getRewardById($id);

        if (!$reward) {
            return $this->errorResponse(__('lang.not_found'), 404);
        }

        return new EmployeeRewardResource($reward);
    }

    /**
     * Update the specified record.
     *
     * @param UpdateEmployeeRewardRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateEmployeeRewardRequest $request, int $id): JsonResponse
    {
        try {
            $reward = $this->rewardService->updateReward($id, $request->validated());

            return (new EmployeeRewardResource($reward))
                ->additional([
                    'success' => true,
                    'message' => __('lang.updated_successfully'),
                ])
                ->response();
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Approve the specified record.
     *
     * @param RewardActionRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function approve(RewardActionRequest $request, int $id): JsonResponse
    {
        try {
            $reward = $this->rewardService->approveReward($id, auth()->id());

            return (new EmployeeRewardResource($reward))
                ->additional([
                    'success' => true,
                    'message' => __('lang.approved_successfully'),
                ])
                ->response();
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Reject the specified record.
     *
     * @param RewardActionRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function reject(RewardActionRequest $request, int $id): JsonResponse
    {
        try {
            $reward = $this->rewardService->rejectReward($id, auth()->id(), $request->input('reason'));

            return (new EmployeeRewardResource($reward))
                ->additional([
                    'success' => true,
                    'message' => __('lang.rejected_successfully'),
                ])
                ->response();
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified record from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->rewardService->deleteReward($id);

            return response()->json([
                'success' => true,
                'message' => __('lang.deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Generic error response helper.
     *
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }
}
