<?php

namespace Modules\CustomFields\Listeners;

use Modules\CustomFields\Models\FieldDefinition;
use Modules\CustomFields\Models\FieldValue;

class SaveCustomFieldValues
{
    public function handle($event): void
    {
        $request = request();

        $customFields = $request->get('custom_fields', []);

        if (empty($customFields)) {
            return;
        }

        $entity = $this->getEntityFromEvent($event);

        if (! $entity) {
            return;
        }

        $entityType = $this->resolveEntityType($event, $entity);

        if ($entityType === 'unknown') {
            return;
        }

        FieldValue::saveValuesForEntity($entityType, $entity->id, $customFields);
    }

    protected function getEntityFromEvent($event)
    {
        if (property_exists($event, 'document')) {
            return $event->document;
        }

        if (property_exists($event, 'transaction')) {
            return $event->transaction;
        }

        if (property_exists($event, 'transfer')) {
            return $event->transfer;
        }

        if (property_exists($event, 'contact')) {
            return $event->contact;
        }

        if (property_exists($event, 'item')) {
            return $event->item;
        }

        if (property_exists($event, 'model')) {
            return $event->model;
        }

        return null;
    }

    protected function resolveEntityType($event, $entity): string
    {
        // For documents, distinguish by type (invoice, bill, estimate)
        if (property_exists($event, 'document')) {
            $type = $entity->type ?? 'invoice';

            if (str_contains($type, 'bill')) {
                return 'bill';
            }

            if (str_contains($type, 'estimate')) {
                return 'estimate';
            }

            return 'invoice';
        }

        if (property_exists($event, 'transfer')) {
            return 'transfer';
        }

        if (property_exists($event, 'transaction')) {
            return 'transaction';
        }

        if (property_exists($event, 'contact')) {
            $type = $entity->type ?? 'customer';

            return str_contains($type, 'vendor') ? 'vendor' : 'customer';
        }

        if (property_exists($event, 'item')) {
            return 'item';
        }

        return 'unknown';
    }

    /**
     * Static helper: validate required custom fields for a given entity type.
     */
    public static function validateRequiredFields(string $entityType, array $customFields): array
    {
        $errors = [];

        $required = FieldDefinition::where('company_id', company_id())
            ->entityType($entityType)
            ->enabled()
            ->where('required', true)
            ->get();

        foreach ($required as $definition) {
            $value = $customFields[$definition->id] ?? null;

            if ($value === null || $value === '') {
                $errors["custom_fields.{$definition->id}"] = trans('validation.required', [
                    'attribute' => $definition->name,
                ]);
            }
        }

        return $errors;
    }
}
