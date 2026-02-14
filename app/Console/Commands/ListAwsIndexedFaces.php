<?php

namespace App\Console\Commands;

use Aws\Rekognition\RekognitionClient;
use Illuminate\Console\Command;

class ListAwsIndexedFaces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:list-faces {collection=emps : The ID of the collection to list faces from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all faces indexed in a specific AWS Rekognition collection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collectionId = $this->argument('collection');

        $this->info("Connecting to AWS Rekognition to list faces for collection: {$collectionId}...");

        $client = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        try {
            $allFaces = [];
            $nextToken = null;

            do {
                $params = [
                    'CollectionId' => $collectionId,
                    'MaxResults' => 50, // Grab 50 at a time
                ];

                if ($nextToken) {
                    $params['NextToken'] = $nextToken;
                }

                $result = $client->listFaces($params);
                $faces = $result['Faces'];

                foreach ($faces as $face) {
                    $allFaces[] = [
                        'FaceId' => $face['FaceId'],
                        'ExternalImageId' => $face['ExternalImageId'] ?? 'N/A',
                        'Confidence' => $face['Confidence'],
                    ];
                }

                $nextToken = $result['NextToken'] ?? null;
            } while ($nextToken);

            if (empty($allFaces)) {
                $this->warn("No faces found in collection '{$collectionId}'.");
                return;
            }

            $this->table(
                ['FaceId', 'ExternalImageId', 'Confidence'],
                $allFaces
            );

            $this->info("Total faces found: " . count($allFaces));
        } catch (\Exception $e) {
            $this->error("Error listing faces: " . $e->getMessage());
        }
    }
}
