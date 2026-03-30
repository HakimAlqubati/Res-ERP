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
        $this->region = (string) config('services.textract.region', env('AWS_TEXTRACT_REGION', 'eu-central-1'));
        $this->bucket = config('filesystems.disks.s3.bucket', env('AWS_BUCKET'));

        $options = [
            'version' => 'latest',
            'region' => $this->region,
            'http' => [
                'timeout' => 30,
                'connect_timeout' => 10,
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ]
            ],
        ];

        $key = env('TEXTRACT_KEY', env('AWS_ACCESS_KEY_ID'));
        $secret = env('TEXTRACT_SECRET', env('AWS_SECRET_ACCESS_KEY'));
        $token = env('TEXTRACT_SESSION_TOKEN', env('AWS_SESSION_TOKEN'));

        if ($key && $secret) {
            $options['credentials'] = [
                'key' => (string) $key,
                'secret' => (string) $secret,
            ];
            if ($token) {
                $options['credentials']['token'] = (string) $token;
            }
        }

        $this->client = $client ?: new TextractClient($options);

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

        try {
            $params = [];

            // Both images and PDFs (single-page, <5MB) can be passed natively using Bytes for Textract synchronous APIs.
            // This completely eliminates the S3 cross-region issue and local bucket dependencies!
            $params['Document'] = [
                'Bytes' => file_get_contents($file->getRealPath()),
            ];

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
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
