<?php

namespace Modules\ExpenseClaims\Models;

use App\Abstracts\Model;
use App\Models\Document\Document;
use App\Models\Auth\User;
use Modules\Employees\Models\Employee;

class ExpenseClaim extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_PAID = 'paid';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REFUSED,
        self::STATUS_PAID,
    ];

    protected $table = 'expense_claims';

    protected $fillable = [
        'company_id',
        'employee_id',
        'approver_id',
        'reimbursement_document_id',
        'reimbursement_transaction_id',
        'status',
        'claim_number',
        'description',
        'total',
        'reimbursable_total',
        'due_date',
        'submitted_at',
        'approved_at',
        'refused_at',
        'refusal_reason',
        'paid_at',
    ];

    protected $casts = [
        'total' => 'double',
        'reimbursable_total' => 'double',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'refused_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function items()
    {
        return $this->hasMany(ExpenseClaimItem::class, 'claim_id');
    }

    public function reimbursementDocument()
    {
        return $this->belongsTo(Document::class, 'reimbursement_document_id');
    }

    public function reimbursementTransaction()
    {
        return $this->belongsTo('App\Models\Banking\Transaction', 'reimbursement_transaction_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_PENDING]);
    }

    public function getStatusLabelAttribute(): string
    {
        return trans('expense-claims::general.statuses.' . $this->status);
    }

    public function getEmployeeNameAttribute(): string
    {
        return $this->employee?->name ?? trans('general.na');
    }

    public function getIsOverdueAttribute(): bool
    {
        return (bool) $this->due_date && ! in_array($this->status, [self::STATUS_PAID, self::STATUS_REFUSED], true) && $this->due_date->isPast();
    }

    public function getLineActionsAttribute(): array
    {
        return [
            [
                'title' => trans('general.show'),
                'icon' => 'visibility',
                'url' => route('expense-claims.claims.show', $this->id),
            ],
            [
                'title' => trans('general.edit'),
                'icon' => 'edit',
                'url' => route('expense-claims.claims.edit', $this->id),
            ],
            [
                'type' => 'delete',
                'icon' => 'delete',
                'route' => 'expense-claims.claims.destroy',
                'model' => $this,
            ],
        ];
    }

    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $this->update([
            'total' => $items->sum('amount'),
            'reimbursable_total' => $items->where('paid_by_employee', true)->sum('amount'),
        ]);
    }
}
