<?php

namespace App\Console\Commands;

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Console\Command;

class ListDynamoDbFaces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:list-dynamo-faces {table=face_recognition : The DynamoDB table name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan and list items from the face_recognition DynamoDB table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tableName = $this->argument('table');

        $this->info("Scanning DynamoDB table: {$tableName}...");

        $client = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        try {
            // Scan operation to get all items
            // Note: Scan can be expensive on large tables. For debugging/lists it's fine.
            $result = $client->scan([
                'TableName' => $tableName,
            ]);

            $items = $result['Items'];

            if (empty($items)) {
                $this->warn("No items found in table '{$tableName}'.");
                return;
            }

            $rows = [];
            foreach ($items as $item) {
                // DynamoDB items are typed (e.g. ['S' => 'value']). 
                // We need to unmarshal or manually access them.
                // Simple manual access for expected fields:
                $rows[] = [
                    'Name' => $item['Name']['S'] ?? 'N/A',
                    'RekognitionId' => $item['RekognitionId']['S'] ?? 'N/A',
                    'AvatarUrl' => $item['AvatarUrl']['S'] ?? 'N/A',
                ];
            }

            $this->table(
                ['Name (Stored Name)', 'RekognitionId (FaceId)', 'AvatarUrl'],
                $rows
            );

            $this->info("Total items found: " . count($rows));
        } catch (\Exception $e) {
            $this->error("Error scanning DynamoDB: " . $e->getMessage());
        }
    }
}
