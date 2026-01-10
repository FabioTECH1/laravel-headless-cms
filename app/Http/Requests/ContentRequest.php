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

                $rules[$column][] = Rule::unique($tableName, $column)->ignore($id);
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

        // Allow status field for setting published state
        $rules['status'] = ['nullable', 'string', 'in:draft,published'];

        return $rules;
    }
}
