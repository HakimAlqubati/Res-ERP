<?php

namespace App\Services\WhatsApp\Contracts;

interface WhatsAppServiceInterface
{
    /**
     * Send a template message to a recipient.
     *
     * @param string $to Recipient phone number format (e.g. 967773030069)
     * @param string $templateName The template name (e.g. workbench_advance_notifier)
     * @param array $parameters Array of parameter values (strings) to be placed in the template body.
     * @param string $languageCode The language code for the template (default: 'en').
     * @return array|null Response data or null on failure.
     */
    public function sendTemplate(string $to, string $templateName, array $parameters = [], string $languageCode = 'en'): ?array;
}
