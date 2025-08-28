<?php

namespace App\Providers\AWS;

use Illuminate\Support\ServiceProvider; 
use Aws\DynamoDb\DynamoDbClient;
use Aws\Rekognition\RekognitionClient;

class AwsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RekognitionClient::class, function () {
            return new RekognitionClient([
                'region'      => config('rekognition.region'),
                'version'     => 'latest',
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);
        });

        $this->app->singleton(DynamoDbClient::class, function () {
            return new DynamoDbClient([
                'region'      => config('rekognition.region'),
                'version'     => 'latest',
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);
        });
    }
}
