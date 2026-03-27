<?php

namespace Modules\AutoScheduleReports\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportSchedule extends Model
{
    public const REPORT_TYPES = [
        'pnl',
        'balance_sheet',
        'trial_balance',
        'cash_flow',
        'ar_aging',
        'ap_aging',
        'budget_variance',
        'custom',
    ];

    public const FREQUENCIES = [
        'daily',
        'weekly',
        'monthly',
        'quarterly',
        'annually',
    ];

    public const FORMATS = [
        'pdf',
        'csv',
        'excel',
    ];

    public const DATE_RANGE_TYPES = [
        'previous_month',
        'previous_quarter',
        'ytd',
        'custom',
    ];

    protected $table = 'report_schedules';

    protected $fillable = [
        'company_id',
        'report_type',
        'frequency',
        'next_run',
        'recipients_json',
        'format',
        'date_range_type',
        'custom_date_from',
        'custom_date_to',
        'webhook_url',
        'enabled',
    ];

    protected $casts = [
        'next_run' => 'datetime',
        'recipients_json' => 'array',
        'custom_date_from' => 'date',
        'custom_date_to' => 'date',
        'enabled' => 'boolean',
    ];

    protected $appends = [
        'report_type_label',
        'frequency_label',
        'format_label',
        'date_range_label',
        'recipients_text',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReportScheduleRun::class, 'schedule_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function getReportTypeLabelAttribute(): string
    {
        return trans('auto-schedule-reports::general.report_types.' . $this->report_type);
    }

    public function getFrequencyLabelAttribute(): string
    {
        return trans('auto-schedule-reports::general.frequencies.' . $this->frequency);
    }

    public function getFormatLabelAttribute(): string
    {
        return trans('auto-schedule-reports::general.formats.' . $this->format);
    }

    public function getDateRangeLabelAttribute(): string
    {
        return trans('auto-schedule-reports::general.date_ranges.' . $this->date_range_type);
    }

    public function getRecipientsTextAttribute(): string
    {
        return implode(', ', array_filter((array) $this->recipients_json));
    }
}
