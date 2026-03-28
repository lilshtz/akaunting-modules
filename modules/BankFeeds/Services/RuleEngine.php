<?php

namespace Modules\BankFeeds\Services;

use Illuminate\Support\Collection;
use Modules\BankFeeds\Models\Rule;
use Modules\BankFeeds\Models\Transaction;

class RuleEngine
{
    public function applyRules($transactions, int $companyId): int
    {
        $rules = Rule::query()
            ->byCompany($companyId)
            ->where('enabled', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        if ($rules->isEmpty()) {
            return 0;
        }

        $categorized = 0;

        foreach (Collection::make($transactions) as $transaction) {
            if (! $transaction instanceof Transaction || $transaction->status !== 'pending') {
                continue;
            }

            foreach ($rules as $rule) {
                if (! $this->matchRule($rule, $transaction)) {
                    continue;
                }

                $transaction->forceFill([
                    'category_id' => $rule->category_id,
                    'status' => 'categorized',
                ])->save();

                $categorized++;

                break;
            }
        }

        return $categorized;
    }

    public function matchRule($rule, $transaction): bool
    {
        if ($rule->field === 'description') {
            $description = strtolower((string) $transaction->description);
            $value = strtolower((string) $rule->value);

            return match ($rule->operator) {
                'contains' => stripos((string) $transaction->description, (string) $rule->value) !== false,
                'equals' => $description === $value,
                'starts_with' => str_starts_with($description, $value),
                default => false,
            };
        }

        if ($rule->field === 'amount') {
            $amount = abs((float) $transaction->amount);
            $value = (float) $rule->value;
            $valueEnd = $rule->value_end !== null && $rule->value_end !== '' ? (float) $rule->value_end : null;

            return match ($rule->operator) {
                'gt' => $amount > $value,
                'lt' => $amount < $value,
                'between' => $valueEnd !== null && $amount >= min($value, $valueEnd) && $amount <= max($value, $valueEnd),
                default => false,
            };
        }

        if ($rule->field === 'type') {
            return $rule->operator === 'equals' && (string) $transaction->type === (string) $rule->value;
        }

        return false;
    }
}
