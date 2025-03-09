<?php

namespace App\Services\Firebase;

use App\Models\User;
use App\Notifications\FcmNotification;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmClient
{
    private $googleClient;
    private $httpClient;
    public function __construct()
    {
        $this->googleClient = new GoogleClient();
        $this->googleClient->setAuthConfig(storage_path('app/firebase/google-services.json'));
        $this->googleClient->addScope('https://fcm.googleapis.com/auth/firebase.messaging');
        $this->httpClient = new HttpClient();
    }
    public function sendMessage($token, $notification)
    {
        // Fetch the OAuth 2.0 access token
        try {
            $tokenResponse = $this->googleClient->fetchAccessTokenWithAssertion();
            Log::info('Access Token Response:', $tokenResponse);
            $accessToken = $tokenResponse['access_token'] ?? null;
            if (!$accessToken) {
                Log::error('Failed to retrieve access token', $tokenResponse);
                throw new \Exception('Failed to retrieve access token');
            }
        } catch (\Exception $e) {
            Log::error('Error fetching access token: ' . $e->getMessage());
            return ['error' => 'Could not fetch access token'];
        }
    }

    public static function sendFCM($id)
    {
        $user = User::find($id);
        $notificationData = [
            'title' => 'Hello!',
            'body' => 'This is a test notification.',
            'data' => ['key' => 'value'], // Additional data if needed
        ];
        $user->notify(new FcmNotification($notificationData));
    }
}
