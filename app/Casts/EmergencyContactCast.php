<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EmergencyContactCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $data = json_decode($value, true);

        return [
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'relation' => $data['relation'] ?? null,
        ];
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        // Ensure we only store the specified keys
        return json_encode([
            'name' => $value['name'] ?? null,
            'phone' => $value['phone'] ?? null,
            'relation' => $value['relation'] ?? null,
        ]);
    }
}
