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
     * Send a WhatsApp message via WhatsApp Meta API.
     *
     * @param string $to The recipient phone number
     * @param string $message The primary message content or template subject
     * @param array $options Additional options: 'template', 'parameters', 'language'
     * @return array|null
     */
    public function sendMessage(string $to, string $message, array $options = []): ?array
    {
        if (empty($this->token) || empty($this->phoneNumberId)) {
            Log::error('WhatsAppService: Token or Phone Number ID is missing in configuration.');
            return null;
        }

        $templateName = $options['template'] ?? 'workbench_notifier_3';
        $languageCode = $options['language'] ?? 'en';
        $parameters = $options['parameters'] ?? [];

        // Default mapping for templates if no parameters provided
        if (empty($parameters)) {
            if ($templateName === 'workbench_notifier_3') {
                $parameters = [
                    ['type' => 'text', 'text' => 'Hakim Al-Qubati'],
                    ['type' => 'text', 'text' => $message],
                    ['type' => 'text', 'text' => 'you can now view and download the full PDF from your dashboard']
                ];
            } elseif ($templateName === 'workbench_advance_notifier') {
                $parameters = [
                    ['type' => 'text', 'text' => 'Manager'],       // Default Manager Name
                    ['type' => 'text', 'text' => 'Employee Name'], // Default Employee Name
                    ['type' => 'text', 'text' => $message]         // Use $message as the Amount/Details
                ];
            }
        }

        $endpoint = "{$this->baseUrl}{$this->phoneNumberId}/messages";

        $response = Http::withToken($this->token)
            ->post($endpoint, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => $parameters
                        ]
                    ]
                ]
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
