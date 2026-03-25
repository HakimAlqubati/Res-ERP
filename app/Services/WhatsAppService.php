<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * @var string
     */
    protected string $token;

    /**
     * @var string
     */
    protected string $phoneNumberId;

    /**
     * @var string
     */
    protected string $baseUrl = 'https://graph.facebook.com/v22.0/';

    public function __construct()
    {
        $this->token = config('services.whatsapp.token', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
    }

    /**
     * Send a text message via WhatsApp Meta API.
     *
     * @param string $to The recipient phone number (with country code, but no '+', e.g., '967773030069')
     * @param string $message The body of the text message
     * @return array|null Returns the API response array on success, or null on failure.
     */
    public function sendMessage(string $to, string $message): ?array
    {
        if (empty($this->token) || empty($this->phoneNumberId)) {
            Log::error('WhatsAppService: Token or Phone Number ID is missing in configuration.');
            return null;
        }

        $endpoint = "{$this->baseUrl}{$this->phoneNumberId}/messages";

        $response = Http::withToken($this->token)
            ->post($endpoint, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp API sending failed.', [
                'status' => $response->status(),
                'response' => $response->json(),
                'to' => $to,
            ]);
            return null;
        }

        return $response->json();
    }
}
