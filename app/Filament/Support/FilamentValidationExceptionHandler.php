<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Reusable Filament v4 handler that surfaces service-layer `ValidationException`s
 * as both a danger toast notification and properly prefixed `data.*` field errors.
 */
final class FilamentValidationExceptionHandler
{
    /**
     * @throws ValidationException
     */
    public static function notifyAndRethrow(ValidationException $exception, string $prefix = 'data.'): never
    {
        $messages = collect($exception->errors())->flatten()->implode("\n");

        Notification::make()
            ->title(__('Validation Error'))
            ->body($messages)
            ->danger()
            ->send();

        $remapped = [];
        foreach ($exception->errors() as $key => $fieldErrors) {
            $targetKey = str_starts_with($key, $prefix) ? $key : "{$prefix}{$key}";
            $remapped[$targetKey] = $fieldErrors;
        }

        throw ValidationException::withMessages($remapped);
    }
}
