<?php

namespace App\Http\Controllers\Api\HR\ImageRecognize;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\ImageRecognize\IdentifyEmployeeRequest;
use App\Services\HR\ImageRecognize\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EmployeeIdentificationController extends Controller
{
    public function __construct(
        protected FaceRecognitionService $service
    ) {}

    public function identify(IdentifyEmployeeRequest $request): JsonResponse
    {
        $file = $request->file('image');
        try {
            $match = $this->service->identify($file);


            return response()->json([
                'status' => 'success',
                'match'  => [
                    'found'         => $match->found,
                    'name'          => $match->name,
                    'employee_id'   => $match->employeeId,
                    'employee_data' => $match->employeeData,
                    'similarity'    => $match->similarity,
                    'confidence'    => $match->confidence,
                    'message'       => $match->message,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Face recognition failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Recognition failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
