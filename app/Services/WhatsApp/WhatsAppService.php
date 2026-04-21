<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements WhatsAppServiceInterface
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
     * Send a WhatsApp template message.
     *
     * @param string $to
     * @param string $templateName
     * @param array $parameters
     * @param string $languageCode
     * @return array|null
     */
    public function sendTemplate(string $to, string $templateName, array $parameters = [], string $languageCode = 'en'): ?array
    {
        if (empty($this->token) || empty($this->phoneNumberId)) {
            Log::error('WhatsAppService: Token or Phone Number ID is missing in configuration.');
            return null;
        }

        // Format parameters for the template body payload
        $formattedParameters = [];
        foreach ($parameters as $parameter) {
            if (is_array($parameter)) {
                $formattedParameters[] = $parameter;
            } else {
                $formattedParameters[] = [
                    'type' => 'text',
                    'text' => (string) $parameter,
                ];
            }
        }

        $endpoint = "{$this->baseUrl}{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => [
                    'code' => $languageCode
                ],
            ]
        ];

        // Add components only if there are parameters
        if (!empty($formattedParameters)) {
            $payload['template']['components'] = [
                [
                    'type'       => 'body',
                    'parameters' => $formattedParameters
                ]
            ];
        }

        $response = Http::withToken($this->token)->post($endpoint, $payload);

        if ($response->failed()) {
            Log::error('WhatsApp API sending failed.', [
                'status'   => $response->status(),
                'response' => $response->json(),
                'to'       => $to,
                'template' => $templateName,
            ]);
            return null;
        }

        return $response->json();
    }
}
