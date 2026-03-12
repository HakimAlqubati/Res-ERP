<?php

declare(strict_types=1);

namespace App\Services\AWS\Textract;

use App\Services\AWS\Textract\Helpers\GenericReportSummaryParser;
use Aws\Textract\TextractClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ExtractReportSummaryService
{
    private TextractClient $client;
    private string $region;
    private ?string $bucket;
    private GenericReportSummaryParser $parser;

    public function __construct(
        ?TextractClient $client = null,
        ?GenericReportSummaryParser $parser = null
    ) {
        $this->region = (string) config('services.textract.region', env('AWS_DEFAULT_REGION', 'me-central-1'));
        $this->bucket = config('filesystems.disks.s3.bucket', env('AWS_BUCKET'));

        $this->client = $client ?: new TextractClient([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => (string) env('AWS_ACCESS_KEY_ID'),
                'secret' => (string) env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $this->parser = $parser ?: new GenericReportSummaryParser();
    }

    public function extract(UploadedFile $file): array
    {
        $mime = (string) $file->getMimeType();
        $isImage = str_starts_with($mime, 'image/');
        $isPdf = $mime === 'application/pdf';

        if (! $isImage && ! $isPdf) {
            throw new \InvalidArgumentException('Only image files or PDF are supported.');
        }

        $s3TempKey = null;

        try {
            $params = [];

            if ($isImage) {
                $params['Document'] = [
                    'Bytes' => file_get_contents($file->getRealPath()),
                ];
            } else {
                if (! $this->bucket) {
                    throw new \RuntimeException('AWS_BUCKET is not configured. PDF requires S3.');
                }

                $s3TempKey = 'textract/tmp/' . now()->format('Y/m/d/') . Str::uuid() . '-' . $file->getClientOriginalName();

                Storage::disk('s3')->put(
                    $s3TempKey,
                    file_get_contents($file->getRealPath()),
                    'private'
                );

                $params['Document'] = [
                    'S3Object' => [
                        'Bucket' => $this->bucket,
                        'Name' => $s3TempKey,
                    ],
                ];
            }

            $result = $this->client->detectDocumentText($params);

            $lines = [];
            $lineBlocks = [];

            foreach (($result['Blocks'] ?? []) as $block) {
                if (($block['BlockType'] ?? null) !== 'LINE' || empty($block['Text'])) {
                    continue;
                }

                $lineBlocks[] = [
                    'text' => trim((string) $block['Text']),
                    'top' => $block['Geometry']['BoundingBox']['Top'] ?? 999,
                    'left' => $block['Geometry']['BoundingBox']['Left'] ?? 999,
                ];
            }

            usort($lineBlocks, function ($a, $b) {
                if (abs($a['top'] - $b['top']) > 0.015) { // كان 0.01
                    return $a['top'] <=> $b['top'];
                }
                return $a['left'] <=> $b['left'];
            });

            $lines = array_map(fn($item) => $item['text'], $lineBlocks);

            $parsed = $this->parser->parse($lines);

            return [
                'mime' => $mime,
                'branch_name' => $parsed['branch_name'],
                'date' => $parsed['date'],
                'service_charge' => $parsed['service_charge'],
                'net_sale' => $parsed['net_sale'],
                'debug' => app()->isLocal() ? [
                    'lines' => $lines,
                ] : null,
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
