<?php
namespace App\Jobs;

use App\Models\EmployeeFaceData;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateFaceEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $faceDataId;

    public function __construct($faceDataId)
    {
        $this->faceDataId = $faceDataId;
    }

    public function handle(): void
    {
        $record = EmployeeFaceData::find($this->faceDataId);

        if (! $record || ! $record->image_url) {
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

            if ($response->ok()) {
                $json = $response->json();
                if (isset($json['results'][0]['embedding'])) {
                    $record->update([
                        'embedding' => $json['results'][0]['embedding'],
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('Embedding Job Error', [$e->getMessage()]);
        }
    }
}