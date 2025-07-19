<?php

namespace App\Http\Controllers\AWS;

use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EmployeeLivenessAWSController extends Controller
{
    // 1. بدء جلسة Face Liveness
    public function startLivenessSession(Request $request)
    {
        $rekognitionClient = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        try {
            $result = $rekognitionClient->createFaceLivenessSession([]);
            $sessionId = $result['SessionId'] ?? null;
            $challengeUrl = $result['VideoChallengeUrl'] ?? null; // فقط إذا كان متوفر
            return response()->json([
                'status' => 'success',
                'sessionId' => $sessionId,
                'challengeUrl' => $challengeUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // 2. التحقق من نتيجة Liveness بعد انتهاء الجلسة
    public function checkLivenessResult(Request $request)
    {
        $sessionId = $request->input('sessionId');
        if (!$sessionId) {
            return response()->json(['status' => 'error', 'message' => 'Session ID required']);
        }

        $rekognitionClient = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        try {
            $result = $rekognitionClient->getFaceLivenessSessionResults([
                'SessionId' => $sessionId,
            ]);
            // status: SUCCEEDED/FAILED
            // confidence: نسبة الثقة 0-100
            if (($result['Status'] ?? '') === 'SUCCEEDED' && ($result['Confidence'] ?? 0) > 95) {
                return response()->json([
                    'status' => 'success',
                    'confidence' => $result['Confidence'],
                    'message' => 'Liveness check passed'
                ]);
            } else {
                return response()->json([
                    'status' => 'fail',
                    'confidence' => $result['Confidence'] ?? null,
                    'message' => 'Liveness check failed',
                    'details' => $result,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}