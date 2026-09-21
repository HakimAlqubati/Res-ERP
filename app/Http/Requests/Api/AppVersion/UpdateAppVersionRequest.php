<?php

namespace App\Http\Requests\Api\AppVersion;

use App\Models\AppVersion;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform'              => ['sometimes', 'required', 'string', 'in:' . implode(',', AppVersion::PLATFORMS)],
            'version_name'          => ['sometimes', 'required', 'string', 'max:50'],
            'version_code'          => ['sometimes', 'required', 'integer', 'min:1'],
            'min_supported_version' => ['nullable', 'string', 'max:50'],
            'min_version_code'      => ['nullable', 'integer', 'min:1'],
            'is_force_update'       => ['boolean'],
            'download_url'          => ['nullable', 'url', 'max:500'],
            'release_notes'         => ['nullable', 'string'],
            'is_active'             => ['boolean'],
        ];
    }
}
