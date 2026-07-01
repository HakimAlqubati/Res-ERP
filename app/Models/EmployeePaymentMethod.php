<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePaymentMethod extends Model
{
    use SoftDeletes;

    protected $table = 'hr_employee_payment_method';

    protected $fillable = [
        'name',
        'code',
        'active',
        'created_by',
    ];

    public const CODE_BANK = 'bank';
    public const CODE_EWALLET = 'ewallet';
    public const CODE_CASH = 'cash';

    public static function getCodes(): array
    {
        return [
            ['key' => self::CODE_BANK, 'value' => __('Bank')],
            ['key' => self::CODE_EWALLET, 'value' => __('E-Wallet')],
            ['key' => self::CODE_CASH, 'value' => __('Cash')],
        ];
    }

    public function getAccountNameLabel(): string
    {
        return $this->code === self::CODE_EWALLET ? __('E-Wallet Provider') : __('Bank Name');
    }

    public function getAccountNumberLabel(): string
    {
        return $this->code === self::CODE_EWALLET ? __('E-Wallet Number') : __('Account Number');
    }

    public function getNoteLabel(): string
    {
        return __('Remarks');
    }

    public function requiresDetails(): bool
    {
        return in_array($this->code, [self::CODE_EWALLET, self::CODE_BANK]);
    }
}

