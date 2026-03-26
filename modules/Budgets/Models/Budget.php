<?php

namespace Modules\Budgets\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_ANNUAL = 'annual';

    protected $table = 'budgets';

    protected $fillable = [
        'company_id',
        'name',
        'period_type',
        'scenario',
        'period_start',
        'period_end',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    protected $appends = [
        'status_label',
        'period_label',
        'scenario_label',
        'total_budgeted',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class, 'budget_id');
    }

    public function scopeStatus($query, ?string $status)
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function getStatusLabelAttribute(): string
    {
        return trans('budgets::general.statuses.' . $this->status);
    }

    public function getPeriodLabelAttribute(): string
    {
        return trans('budgets::general.period_types.' . $this->period_type);
    }

    public function getScenarioLabelAttribute(): string
    {
        if (empty($this->scenario)) {
            return trans('general.na');
        }

        return trans('budgets::general.scenarios.' . $this->scenario);
    }

    public function getTotalBudgetedAttribute(): float
    {
        if ($this->relationLoaded('lines')) {
            return (float) $this->lines->sum('amount');
        }

        return (float) $this->lines()->sum('amount');
    }
}
