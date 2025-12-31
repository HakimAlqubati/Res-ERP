<?php

namespace App\Models;

use App\Scopes\BranchRequiredScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransaction extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchRequiredScope);
    }

    // Constants for transaction types
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';

    const TYPES = [
        self::TYPE_INCOME => 'Income',
        self::TYPE_EXPENSE => 'Expense',
    ];

    // Constants for transaction status
    const STATUS_PAID = 'paid';
    const STATUS_PENDING = 'pending';
    const STATUS_OVERDUE = 'overdue';

    const STATUSES = [
        self::STATUS_PAID => 'Paid',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_OVERDUE => 'Overdue',
    ];

    protected $fillable = [
        'branch_id',
        'category_id',
        'amount',
        'type',
        'transaction_date',
        'due_date',
        'status',
        'description',
        'payment_method_id',
        'reference_type',
        'reference_id',
        'created_by',
        'month',
        'year',
    ];



    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'due_date' => 'date',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Polymorphic relationship
    public function reference()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
