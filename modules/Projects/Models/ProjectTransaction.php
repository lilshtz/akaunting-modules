<?php

namespace Modules\Projects\Models;

use App\Abstracts\Model;

class ProjectTransaction extends Model
{
    protected $table = 'project_transactions';

    protected $fillable = [
        'project_id',
        'document_type',
        'document_id',
    ];

    protected $appends = [
        'document_type_label',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function document()
    {
        return $this->belongsTo('App\Models\Document\Document', 'document_id');
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return trans('projects::general.document_types.' . $this->document_type);
    }
}
