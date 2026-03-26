<?php

namespace Modules\CustomFields\Models;

use App\Abstracts\Model;

class FieldDefinition extends Model
{
    protected $table = 'custom_field_definitions';

    protected $fillable = [
        'company_id',
        'entity_type',
        'name',
        'field_type',
        'required',
        'default_value',
        'options_json',
        'position',
        'show_on_pdf',
        'width',
        'enabled',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options_json' => 'array',
        'position' => 'integer',
        'show_on_pdf' => 'boolean',
        'enabled' => 'boolean',
    ];

    protected $sortable = ['name', 'entity_type', 'field_type', 'position', 'enabled'];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function values()
    {
        return $this->hasMany(FieldValue::class, 'definition_id');
    }

    public function scopeEntityType($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeForPdf($query)
    {
        return $query->where('show_on_pdf', true);
    }

    public function getOptionsAttribute(): array
    {
        return $this->options_json ?? [];
    }

    public static function entityTypes(): array
    {
        return [
            'invoice' => 'Invoice',
            'bill' => 'Bill',
            'customer' => 'Customer',
            'vendor' => 'Vendor',
            'item' => 'Item',
            'account' => 'Account',
            'employee' => 'Employee',
            'transfer' => 'Transfer',
            'estimate' => 'Estimate',
            'project' => 'Project',
            'expense_claim' => 'Expense Claim',
        ];
    }

    public static function fieldTypes(): array
    {
        return [
            'text' => 'Text',
            'textarea' => 'Textarea',
            'number' => 'Number',
            'date' => 'Date',
            'datetime' => 'Date & Time',
            'time' => 'Time',
            'select' => 'Select',
            'checkbox' => 'Checkbox',
            'toggle' => 'Toggle',
            'url' => 'URL',
            'email' => 'Email',
        ];
    }

    public function getLineActionsAttribute(): array
    {
        $actions = [];

        $actions[] = [
            'title' => trans('general.edit'),
            'icon' => 'edit',
            'url' => route('custom-fields.fields.edit', $this->id),
            'attributes' => [
                'id' => 'index-line-actions-edit-field-' . $this->id,
            ],
        ];

        $actions[] = [
            'type' => 'delete',
            'icon' => 'delete',
            'route' => 'custom-fields.fields.destroy',
            'model' => $this,
            'attributes' => [
                'id' => 'index-line-actions-delete-field-' . $this->id,
            ],
        ];

        return $actions;
    }
}
