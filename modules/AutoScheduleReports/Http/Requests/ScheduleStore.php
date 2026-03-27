<?php

namespace Modules\AutoScheduleReports\Http\Requests;

use App\Abstracts\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\AutoScheduleReports\Models\ReportSchedule;

class ScheduleStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(ReportSchedule::REPORT_TYPES)],
            'frequency' => ['required', Rule::in(ReportSchedule::FREQUENCIES)],
            'next_run' => ['required', 'date'],
            'recipients_json' => ['nullable', 'array'],
            'recipients_json.*' => ['nullable', 'email:rfc'],
            'format' => ['required', Rule::in(ReportSchedule::FORMATS)],
            'date_range_type' => ['required', Rule::in(ReportSchedule::DATE_RANGE_TYPES)],
            'custom_date_from' => ['nullable', 'date', 'required_if:date_range_type,custom'],
            'custom_date_to' => ['nullable', 'date', 'after_or_equal:custom_date_from', 'required_if:date_range_type,custom'],
            'webhook_url' => ['nullable', 'url'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $recipients = $this->input('recipients_json', $this->input('recipients'));

        if (is_string($recipients)) {
            $recipients = preg_split('/[\s,;]+/', $recipients) ?: [];
        }

        $recipients = collect((array) $recipients)
            ->map(fn ($recipient) => trim((string) $recipient))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'recipients_json' => $recipients,
            'enabled' => $this->boolean('enabled'),
        ]);
    }
}
