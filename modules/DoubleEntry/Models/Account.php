<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $table = 'double_entry_accounts';

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',
        'description',
        'opening_balance',
        'enabled',
    ];

    protected $casts = [
        'opening_balance' => 'double',
        'enabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected $sortable = ['code', 'name', 'type', 'enabled'];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function defaults()
    {
        return $this->hasMany(AccountDefault::class, 'account_id');
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->code . ' - ' . $this->name;
    }

    public function getLineActionsAttribute(): array
    {
        $actions = [];

        $actions[] = [
            'title' => trans('general.edit'),
            'icon' => 'edit',
            'url' => route('double-entry.accounts.edit', $this->id),
            'attributes' => [
                'id' => 'index-line-actions-edit-account-' . $this->id,
            ],
        ];

        $actions[] = [
            'type' => 'delete',
            'icon' => 'delete',
            'route' => 'double-entry.accounts.destroy',
            'model' => $this,
            'attributes' => [
                'id' => 'index-line-actions-delete-account-' . $this->id,
            ],
        ];

        return $actions;
    }
}
