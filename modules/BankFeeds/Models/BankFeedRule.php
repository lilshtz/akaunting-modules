<?php

namespace Modules\BankFeeds\Models;

use App\Abstracts\Model;

class BankFeedRule extends Model
{
    protected $table = 'bank_feed_rules';

    protected $fillable = [
        'company_id',
        'field',
        'operator',
        'value',
        'category_id',
        'vendor_id',
        'enabled',
        'priority',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
    ];

    protected $sortable = ['field', 'operator', 'value', 'priority', 'enabled', 'created_at'];

    public const FIELDS = ['description', 'vendor', 'amount'];

    public const OPERATORS = ['contains', 'equals', 'starts_with', 'gt', 'lt', 'between'];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Setting\Category');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function matches(BankFeedTransaction $transaction): bool
    {
        $fieldValue = $this->getFieldValue($transaction);

        return match ($this->operator) {
            'contains' => stripos($fieldValue, $this->value) !== false,
            'equals' => strcasecmp($fieldValue, $this->value) === 0,
            'starts_with' => stripos($fieldValue, $this->value) === 0,
            'gt' => is_numeric($fieldValue) && (float) $fieldValue > (float) $this->value,
            'lt' => is_numeric($fieldValue) && (float) $fieldValue < (float) $this->value,
            'between' => $this->matchesBetween($fieldValue),
            default => false,
        };
    }

    protected function getFieldValue(BankFeedTransaction $transaction): string
    {
        return match ($this->field) {
            'description' => $transaction->description ?? '',
            'vendor' => $transaction->description ?? '',
            'amount' => (string) abs($transaction->amount),
            default => '',
        };
    }

    protected function matchesBetween(string $fieldValue): bool
    {
        if (!is_numeric($fieldValue)) {
            return false;
        }

        $parts = array_map('trim', explode(',', $this->value));
        if (count($parts) !== 2) {
            return false;
        }

        $amount = (float) $fieldValue;
        return $amount >= (float) $parts[0] && $amount <= (float) $parts[1];
    }

    public function getLineActionsAttribute(): array
    {
        return [
            [
                'title' => trans('general.edit'),
                'icon' => 'edit',
                'url' => route('bank-feeds.rules.edit', $this->id),
            ],
            [
                'type' => 'delete',
                'icon' => 'delete',
                'route' => 'bank-feeds.rules.destroy',
                'model' => $this,
            ],
        ];
    }
}
