<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Facades\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    /**
     * Send a WhatsApp message via API.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Try to find the employee by phone number to leverage the model-aware helper for personalized names
        $employee = \App\Models\Employee::where('phone_number', $request->to)->first();
        $target = $employee ?? $request->to;

        $response = sendWhatsAppMessage($target, $request->message);
        $responseData = json_decode($response, true);

        return response()->json($responseData, ($responseData['status'] ?? 'error') === 'success' ? 200 : 500);
    }
}
