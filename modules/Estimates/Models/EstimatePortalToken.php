<?php

namespace Modules\Estimates\Models;

use App\Abstracts\Model;
use Illuminate\Support\Str;

class EstimatePortalToken extends Model
{
    protected $table = 'estimate_portal_tokens';

    protected $fillable = [
        'company_id',
        'document_id',
        'token',
        'viewed_at',
        'expires_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo('App\Models\Document\Document');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public static function generateForDocument(int $companyId, int $documentId): self
    {
        return static::updateOrCreate(
            ['document_id' => $documentId],
            [
                'company_id' => $companyId,
                'token' => Str::random(64),
                'expires_at' => null,
            ]
        );
    }

    public function markViewed(): void
    {
        if (! $this->viewed_at) {
            $this->update(['viewed_at' => now()]);
        }
    }
}
