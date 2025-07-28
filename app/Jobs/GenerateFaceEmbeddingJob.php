<?php
namespace App\Jobs;

use App\Models\EmployeeFaceData;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateFaceEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $faceDataId;

    // ⏱️ إطالة وقت الجوب إلى 5 دقائق
    public $timeout = 300;

    public function __construct($faceDataId)
    {
        $this->faceDataId = $faceDataId;
    }

    public function handle(): void
    {
        $record = EmployeeFaceData::find($this->faceDataId);

        if (! $record || ! $record->image_url) {
            $record?->update([
                'response_message' => 'Record missing or image URL not found.',
            ]);
            Log::warning("FaceData record missing or image URL is null. ID: {$this->faceDataId}");
            return;
        }

        try {
            $response = Http::timeout(180)
                ->withOptions(['verify' => false])
                ->post('https://54.251.132.76:5000/api/represent', [
                    'img'               => $record->image_url,
                    'model_name'        => 'Facenet',
                    'detector_backend'  => 'opencv',
                    'enforce_detection' => false,
                ]);

            $responseJson = $response->json();

            if (isset($responseJson['error'])) {
                $record->update([
                    'response_message' => 'DeepFace error: ' . $responseJson['error'],
                ]);
                Log::warning("DeepFace API error for record ID: {$this->faceDataId}", [
                    'error' => $responseJson['error'],
                ]);
                return;
            }

            if (! $response->ok()) {
                $record->update([
                    'response_message' => 'API call failed with status: ' . $response->status(),
                ]);
                Log::warning("Face API call failed. ID: {$this->faceDataId}, Status: {$response->status()}");
                return;
            }

            $json       = $response->json();
            $embedding  = $json['results'][0]['embedding'] ?? null;
            $confidence = $json['results'][0]['face_confidence'] ?? null;

            // 🟥 الحالة 1: البصمة غير موجودة
            if (! $embedding) {
                $record->update([
                    'response_message' => 'Embedding missing from response.',
                ]);
                Log::warning("Embedding missing for record ID: {$this->faceDataId}");
                return;
            }

            // 🟥 الحالة 2: الـ confidence غير موجود
            if (is_null($confidence)) {
                $record->update([
                    'response_message' => 'Face confidence missing in response.',
                ]);
                Log::warning("Confidence missing for record ID: {$this->faceDataId}");
                return;
            }

            // 🟥 الحالة 3: الثقة منخفضة
            if ($confidence < 0.90) {
                $record->update([
                    'response_message' => 'Face confidence too low (Confidence: ' . $confidence . ')',
                ]);
                Log::warning("Low face confidence for record ID: {$this->faceDataId}, Value: {$confidence}");
                return;
            }

            // ✅ الحالة 4: نجاح كامل → حفظ البيانات داخل معاملة
            DB::beginTransaction();

            $record->update([
                'embedding'        => $embedding,
                'face_added'       => true,
                'response_message' => 'Embedding extracted successfully (Confidence: ' . $confidence . ')',
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            $record?->update([
                'response_message' => 'Job error: ' . $e->getMessage(),
            ]);

            Log::error("Embedding Job Error (ID: {$this->faceDataId})", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

}