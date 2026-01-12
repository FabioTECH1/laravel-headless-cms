<?php

namespace App\Http\Requests;

use App\Models\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller or via policies for now.
        // We return true to allow the request to proceed to the validation stage.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $slug = $this->route('slug');
        $id = $this->route('id'); // Will be null for 'store' requests

        // Find the ContentType based on the slug
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

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
                    if ($field->settings['multiple'] ?? false) {
                        $fieldRules[] = 'array';
                    } else {
                        $fieldRules[] = 'string'; // ULIDs are strings
                    }
                    break;
                case 'media':
                    $fieldRules[] = 'string'; // Media IDs are ULIDs (strings)
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'string';
                    break;
                case 'json':
                    $fieldRules[] = 'array'; // Expecting JSON payload to be decoded to array by Laravel
                    break;
                case 'enum':
                    $fieldRules[] = 'string';
                    if (! empty($field->settings['options'])) {
                        $fieldRules[] = Rule::in($field->settings['options']);
                    }
                    break;
                case 'component':
                    $fieldRules[] = 'array';
                    $componentId = $field->settings['related_content_type_id'] ?? null;
                    if ($componentId) {
                        $fieldRules[] = function ($attribute, $value, $fail) use ($componentId) {
                            if (! is_array($value)) {
                                return; // 'array' rule handles this
                            }
                            $this->validateComponentData($attribute, $value, $componentId, $fail);
                        };
                    }
                    break;
                case 'dynamic_zone':
                    $fieldRules[] = 'array';
                    $allowedIds = $field->settings['allowed_component_ids'] ?? [];
                    $fieldRules[] = function ($attribute, $value, $fail) use ($allowedIds) {
                        if (! is_array($value)) {
                            return;
                        }
                        foreach ($value as $index => $item) {
                            if (! is_array($item)) {
                                $fail("The {$attribute}.{$index} must be an item object.");

                                continue;
                            }
                            if (empty($item['__component'])) {
                                $fail("The {$attribute}.{$index} item matches no component.");

                                continue;
                            }
                            // Find component by slug (assuming __component stores slug)
                            $componentSlug = $item['__component'];
                            $component = ContentType::where('slug', $componentSlug)->first();

                            if (! $component || ! in_array($component->id, $allowedIds)) {
                                $fail("The {$attribute}.{$index} component type is invalid or not allowed.");

                                continue;
                            }

                            $this->validateComponentData("{$attribute}.{$index}", $item, $component->id, $fail);
                        }
                    };
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

                $rules[$column][] = Rule::unique($tableName, $column)->ignore($id);
            }

            // Assign rules to appropriate key
            if ($field->type === 'relation' && ($field->settings['multiple'] ?? false)) {
                $key = $field->name;
            } elseif (in_array($field->type, ['relation', 'media'])) {
                $key = $field->name.'_id';
            } else {
                $key = $field->name;
            }

            // Merge rules if already set (rare, but good practice)
            $rules[$key] = array_merge($rules[$key] ?? [], $fieldRules);
        }

        // Always allow published_at
        $rules['published_at'] = ['nullable', 'date'];

        // Allow status field for setting published state
        $rules['status'] = ['nullable', 'string', 'in:draft,published'];

        return $rules;

    }

    protected function validateComponentData(string $attributePrefix, array $data, string $componentId, $fail): void
    {
        $component = ContentType::with('fields')->find($componentId);
        if (! $component) {
            return;
        }

        $validator = \Illuminate\Support\Facades\Validator::make($data, $this->getComponentRules($component));

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $messages) {
                foreach ($messages as $message) {
                    $fail("{$attributePrefix}.{$key}: {$message}");
                }
            }
        }
    }

    protected function getComponentRules(ContentType $component): array
    {
        $rules = [];
        foreach ($component->fields as $field) {
            $fieldRules = [];
            if (! empty($field->settings['required'])) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($field->type) {
                case 'integer': $fieldRules[] = 'integer';
                    break;
                case 'boolean': $fieldRules[] = 'boolean';
                    break;
                case 'email': $fieldRules[] = 'email';
                    break;
                case 'component':
                    $fieldRules[] = 'array';
                    $subId = $field->settings['related_content_type_id'] ?? null;
                    if ($subId) {
                        $fieldRules[] = function ($attr, $val, $fail) use ($subId) {
                            if (is_array($val)) {
                                $this->validateComponentData($attr, $val, $subId, $fail);
                            }
                        };
                    }
                    break;
                default: $fieldRules[] = 'string';
            }
            $rules[$field->name] = $fieldRules;
        }

        return $rules;
    }
}
