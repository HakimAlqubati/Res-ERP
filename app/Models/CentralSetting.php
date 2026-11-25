<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class CentralSetting extends Model
{
    use UsesLandlordConnection;

    public const KEY_DEFAULT_INVOICE_WARNING_DAYS = 'default_invoice_warning_days';

    public const KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE = 'suspend_tenant_on_overdue_invoice';

    public const DEFAULT_INVOICE_WARNING_DAYS = 10;

    public const DEFAULT_SUSPEND_TENANT_ON_OVERDUE_INVOICE = false;

    /**
     * @var list<string>
     */
    private const BOOLEAN_KEYS = [
        self::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE,
    ];

    /**
     * @var list<string>
     */
    private const INTEGER_KEYS = [
        self::KEY_DEFAULT_INVOICE_WARNING_DAYS,
    ];

    /**
     * @var list<string>
     */
    private const ARRAY_KEYS = [];

    protected $table = 'central_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            self::KEY_DEFAULT_INVOICE_WARNING_DAYS => self::DEFAULT_INVOICE_WARNING_DAYS,
            self::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE => self::DEFAULT_SUSPEND_TENANT_ON_OVERDUE_INVOICE,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getAllSettings(): array
    {
        $stored = static::query()
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        return array_merge(static::getDefaults(), $stored);
    }

    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return ($setting && $setting->value !== null && $setting->value !== '')
            ? $setting->value
            : $default;
    }

    public static function setSetting(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => static::normalizeValueForStorage($key, $value)],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function upsertSettings(array $data): void
    {
        $rows = [];

        foreach ($data as $key => $value) {
            $rows[] = [
                'key' => (string) $key,
                'value' => static::normalizeValueForStorage((string) $key, $value),
            ];
        }

        if ($rows === []) {
            return;
        }

        static::upsert($rows, ['key'], ['value']);
    }

    public static function getDefaultInvoiceWarningDays(): int
    {
        $value = static::getSetting(
            self::KEY_DEFAULT_INVOICE_WARNING_DAYS,
            self::DEFAULT_INVOICE_WARNING_DAYS,
        );

        return max(0, (int) $value);
    }

    public static function shouldSuspendTenantOnOverdueInvoice(): bool
    {
        return (bool) static::getSetting(
            self::KEY_SUSPEND_TENANT_ON_OVERDUE_INVOICE,
            self::DEFAULT_SUSPEND_TENANT_ON_OVERDUE_INVOICE,
        );
    }

    public static function normalizeValueForStorage(string $key, mixed $value): ?string
    {
        if (in_array($key, self::ARRAY_KEYS, true) || is_array($value)) {
            return json_encode($value);
        }

        if (in_array($key, self::BOOLEAN_KEYS, true) || is_bool($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($key, self::INTEGER_KEYS, true)) {
            return (string) (int) $value;
        }

        return (string) $value;
    }

    public function getValueAttribute(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $key = (string) ($this->attributes['key'] ?? '');

        if (in_array($key, self::ARRAY_KEYS, true)) {
            return json_decode((string) $value, true) ?? [];
        }

        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($key, self::INTEGER_KEYS, true)) {
            return (int) $value;
        }

        return $value;
    }

    public function setValueAttribute(mixed $value): void
    {
        $key = (string) ($this->attributes['key'] ?? '');

        $this->attributes['value'] = static::normalizeValueForStorage($key, $value);
    }
}
