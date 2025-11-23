<?php

declare(strict_types=1);

namespace App\Services\AWS\Textract;

use Aws\Textract\TextractClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use App\Services\AWS\Textract\Helpers\AnalyzeExpenseHelper;

class AnalyzeExpenseService
{
    private TextractClient $client;
    private string $region;
    private ?string $bucket;
    private AnalyzeExpenseHelper $helper;

    public function __construct(
        ?TextractClient $client = null,
        ?AnalyzeExpenseHelper $helper = null
    ) {
        // Base config
        $this->region = (string) config('services.textract.region', env('AWS_DEFAULT_REGION', 'me-central-1'));
        $this->bucket = config('filesystems.disks.s3.bucket', env('AWS_BUCKET'));

        // AWS client
        $this->client = $client ?: new TextractClient([
            'version'     => 'latest',
            'region'      => $this->region,
            'credentials' => [
                'key'    => (string) env('AWS_ACCESS_KEY_ID'),
                'secret' => (string) env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        // Helper (DI-friendly)
        $this->helper = $helper ?: new AnalyzeExpenseHelper();
    }

    /**
     * Analyze an uploaded image/PDF with Textract AnalyzeExpense.
     */
    public function analyze(UploadedFile $file): array
    {
        $mime  = (string) $file->getMimeType();
        $isImg = str_starts_with($mime, 'image/');
        $isPdf = $mime === 'application/pdf';

        if (!($isImg || $isPdf)) {
            throw new \InvalidArgumentException('Only image files (PNG/JPG/TIFF) or PDF are supported.');
        }

        $s3TempKey = null;

        try {
            $params = [];
            if ($isImg) {
                $params['Document'] = ['Bytes' => file_get_contents($file->getRealPath())];
            } else {
                if (!$this->bucket) {
                    throw new \RuntimeException('AWS_BUCKET is not configured. PDF requires S3.');
                }
                $s3TempKey = 'textract/tmp/' . now()->format('Y/m/d/') . Str::uuid() . '-' . $file->getClientOriginalName();
                Storage::disk('s3')->put($s3TempKey, file_get_contents($file->getRealPath()), 'private');

                $params['Document'] = [
                    'S3Object' => [
                        'Bucket' => $this->bucket,
                        'Name'   => $s3TempKey,
                    ],
                ];
            }

            $result    = $this->client->analyzeExpense($params);
            $documents = $result['ExpenseDocuments'] ?? [];

            $parsed = [];
            foreach ($documents as $doc) {
                $parsed[] = [
                    'summary'    => $this->helper->extractSummary($doc),
                    'line_items' => $this->helper->extractItems($doc),
                ];
            }

            return [
                'mime'      => $mime,
                'documents' => $parsed,
            ];
        } finally {
            if ($s3TempKey) {
                try {
                    Storage::disk('s3')->delete($s3TempKey);
                } catch (Throwable $e) {
                    // ignore cleanup failures
                }
            }
        }
    }
}
