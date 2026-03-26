<?php

namespace Modules\CustomFields\Models;

use App\Abstracts\Model;

class FieldValue extends Model
{
    protected $table = 'custom_field_values';

    protected $fillable = [
        'definition_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    public function definition()
    {
        return $this->belongsTo(FieldDefinition::class, 'definition_id');
    }

    public function entity()
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public static function getValuesForEntity(string $entityType, int $entityId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('definition')
            ->get()
            ->keyBy('definition_id');
    }

    public static function saveValuesForEntity(string $entityType, int $entityId, array $fieldValues): void
    {
        foreach ($fieldValues as $definitionId => $value) {
            static::updateOrCreate(
                [
                    'definition_id' => $definitionId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ],
                [
                    'value' => $value,
                ]
            );
        }
    }
}
