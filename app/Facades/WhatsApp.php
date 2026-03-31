<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array|null sendMessage(string $to, string $message, array $options = [])
 * 
 * @see \App\Services\WhatsAppService
 */
final class WhatsApp extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\WhatsAppService::class;
    }
}
