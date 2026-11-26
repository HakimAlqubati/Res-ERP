<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\EmployeeSalaryLogObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy([EmployeeSalaryLogObserver::class])]
class EmployeeSalaryLog extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'hr_employee_salary_logs';

    public const SOURCE_INITIAL = 'initial';

    public const SOURCE_BACKFILL = 'backfill';

    public const SOURCE_AUTO_BASELINE = 'auto_baseline';

    public const SOURCE_SALARY_INCREASE_REQUEST = 'salary_increase_request';

    public const SOURCE_DIRECT_EDIT = 'direct_edit';

    public const SOURCE_IMPORT = 'import';

    public const SOURCE_REHIRE = 'rehire';

    public const SOURCES = [
        self::SOURCE_INITIAL,
        self::SOURCE_BACKFILL,
        self::SOURCE_AUTO_BASELINE,
        self::SOURCE_SALARY_INCREASE_REQUEST,
        self::SOURCE_DIRECT_EDIT,
        self::SOURCE_IMPORT,
        self::SOURCE_REHIRE,
    ];

    protected $fillable = [
        'employee_id',
        'old_salary',
        'new_salary',
        'change_amount',
        'effective_from',
        'effective_date',
        'source',
        'salary_increase_request_id',
        'notes',
        'created_by',
    ];

    protected $auditInclude = [
        'employee_id',
        'old_salary',
        'new_salary',
        'change_amount',
        'effective_from',
        'effective_date',
        'source',
        'salary_increase_request_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'old_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_date' => 'date',
    ];

    /**
     * @return array<string, string>
     */
    public static function sourceOptions(): array
    {
        return [
            self::SOURCE_INITIAL => 'Initial',
            self::SOURCE_BACKFILL => 'Backfill',
            self::SOURCE_AUTO_BASELINE => 'Auto Baseline',
            self::SOURCE_SALARY_INCREASE_REQUEST => 'Salary Increase Request',
            self::SOURCE_DIRECT_EDIT => 'Direct Edit',
            self::SOURCE_IMPORT => 'Excel Import',
            self::SOURCE_REHIRE => 'Rehire',
        ];
    }

    public static function getSourceLabel(?string $source): string
    {
        return self::sourceOptions()[$source] ?? (string) ($source ?? '');
    }

    public static function normalizeEffectiveFrom(Carbon|string $date): Carbon
    {
        return ($date instanceof Carbon ? $date->copy() : Carbon::parse($date))->startOfMonth();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withoutGlobalScopes();
    }

    public function salaryIncreaseRequest(): BelongsTo
    {
        return $this->belongsTo(SalaryIncreaseRequest::class, 'salary_increase_request_id')->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
