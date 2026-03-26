<?php

namespace Modules\Crm\Models;

use App\Abstracts\Model;

class CrmPipelineStage extends Model
{
    protected $table = 'crm_pipeline_stages';

    protected $fillable = [
        'company_id',
        'name',
        'position',
        'color',
        'is_won',
        'is_lost',
    ];

    protected $casts = [
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
    ];

    public function deals()
    {
        return $this->hasMany(CrmDeal::class, 'stage_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public static function ensureDefaults(int $companyId): void
    {
        if (static::forCompany($companyId)->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Lead', 'color' => '#64748b'],
            ['name' => 'Qualified', 'color' => '#0ea5e9'],
            ['name' => 'Proposal', 'color' => '#8b5cf6'],
            ['name' => 'Negotiation', 'color' => '#f59e0b'],
            ['name' => 'Won', 'color' => '#10b981', 'is_won' => true],
            ['name' => 'Lost', 'color' => '#ef4444', 'is_lost' => true],
        ];

        foreach ($defaults as $index => $stage) {
            static::create([
                'company_id' => $companyId,
                'name' => $stage['name'],
                'position' => $index + 1,
                'color' => $stage['color'],
                'is_won' => $stage['is_won'] ?? false,
                'is_lost' => $stage['is_lost'] ?? false,
            ]);
        }
    }
}
