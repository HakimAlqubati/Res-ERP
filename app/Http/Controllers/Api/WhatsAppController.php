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

        $response = WhatsApp::sendMessage($request->to, $request->message);

        if ($response) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp message sent successfully',
                'data' => $response
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send WhatsApp message. Please check logs for details.'
        ], 500);
    }
}
