<?php

namespace Modules\Estimates\Models;

use App\Abstracts\Model;

class EstimateSetting extends Model
{
    protected $table = 'estimate_settings';

    protected $fillable = [
        'company_id',
        'prefix',
        'next_number',
        'default_terms',
        'template',
        'approval_required',
    ];

    protected $casts = [
        'approval_required' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public static function getForCompany(int $companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'prefix' => 'EST-',
                'next_number' => 1,
                'template' => 'default',
                'approval_required' => true,
            ]
        );
    }

    public function generateNumber(): string
    {
        $number = $this->prefix . str_pad($this->next_number, 5, '0', STR_PAD_LEFT);
        $this->increment('next_number');

        return $number;
    }
}
