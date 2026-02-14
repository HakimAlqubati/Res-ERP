<?php

namespace App\Models;

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class FaceRecognition extends Model
{
    use Sushi;

    protected $schema = [
        'id' => 'string', // RekognitionId
        'name' => 'string',
        'avatar_url' => 'string',
        'base_url' => 'string',
    ];

    /**
     * Disable Sushi caching to ensure we always fetch fresh data from DynamoDB.
     * Note: This might impact performance if the table is large.
     */
    protected $sushiShouldCache = false;

    public function getRows()
    {
        $rows = [];

        try {
            $client = new DynamoDbClient([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => 'latest',
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            // Scan operation to get all items
            // Assuming table name is face_recognition as used in S3ImageService
            $result = $client->scan([
                'TableName' => 'face_recognition',
            ]);

            $items = $result['Items'] ?? [];

            foreach ($items as $item) {
                $s3Uri = $item['AvatarUrl']['S'] ?? null;
                $httpUrl = null;

                if ($s3Uri) {
                    // Extract the key: remove "s3://emps/"
                    $key = str_replace('s3://emps/', '', $s3Uri);
                    // Generate public URL
                    // Note: Ensure your S3 disk is configured correctly in filesystems.php
                    // If the files are private, you might need temporaryUrl()
                    try {
                        $httpUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url($key);
                    } catch (\Exception $e) {
                        $httpUrl = null;
                    }
                }

                $rows[] = [
                    'id' => $item['RekognitionId']['S'] ?? null,
                    'name' => $item['Name']['S'] ?? 'Unknown',
                    'avatar_url' => $httpUrl,
                    'base_url' => $item['baseUrl']['S'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Log error or return empty if connection fails
            // In a real app, you might want to handle this more gracefully
        }

        return $rows;
    }

    // Since Sushi promotes read-only by default for array sources unless configured otherwise,
    // and this is a view of DynamoDB, we probably want it read-only for now in Filament
    // unless we implement writes back to DynamoDB (which Sushi doesn't do automatically).
}
