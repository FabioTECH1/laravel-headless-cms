<?php

namespace App\Services;

use App\Models\ContentType;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContentValidator
{
    /**
     * Generate validation rules for a given Content Type.
     *
     * @param  int|null  $ignoreId  ID to ignore for unique checks (during update)
     */
    public function getRules(ContentType $contentType, $ignoreId = null): array
    {
        $rules = [];

        foreach ($contentType->fields as $field) {
            $fieldRules = [];

            // 1. Required / Nullable
            if (! empty($field->settings['required'])) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // 2. Type-based validation
            switch ($field->type) {
                case 'integer':
                    $fieldRules[] = 'integer';
                    break;
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;
                case 'datetime':
                    $fieldRules[] = 'date';
                    break;
                case 'relation':
                case 'media':
                    // Since we store as '_id', the validation key is different.
                    // Handled below by overriding key name.
                    $fieldRules[] = 'integer'; // IDs are integers
                    break;
                case 'text':
                case 'longtext':
                default:
                    $fieldRules[] = 'string';
                    break;
            }

            // 3. Unique validation
            if (! empty($field->settings['unique'])) {
                $tableName = Str::plural(Str::snake($contentType->name));

                // If it's a relation/media, the column is 'field_id'
                $column = in_array($field->type, ['relation', 'media'])
                    ? $field->name.'_id'
                    : $field->name;

                $rules[$column][] = Rule::unique($tableName, $column)->ignore($ignoreId);
            }

            // Assign rules to appropriate key
            $key = in_array($field->type, ['relation', 'media'])
                ? $field->name.'_id'
                : $field->name;

            // Merge rules if already set (rare, but good practice)
            $rules[$key] = array_merge($rules[$key] ?? [], $fieldRules);
        }

        // Always allow published_at
        $rules['published_at'] = ['nullable', 'date'];

        return $rules;
    }
}
