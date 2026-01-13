<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchemaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'is_public' => ['boolean'],
            'has_ownership' => ['boolean'],
            'is_component' => ['boolean'],
            'is_single' => ['boolean'],
            'is_localized' => ['boolean'],
            'fields' => ['array'],
            'fields.*.name' => ['required', 'string', 'alpha_dash'],
            'fields.*.type' => ['required', 'in:text,longtext,integer,boolean,datetime,media,relation,json,enum,email,component,dynamic_zone'],
            'fields.*.settings.related_content_type_id' => ['required_if:fields.*.type,relation,component', 'nullable', 'exists:content_types,id'],
            'fields.*.settings.allowed_component_ids' => ['required_if:fields.*.type,dynamic_zone', 'nullable', 'array'],
            'fields.*.settings.options' => ['required_if:fields.*.type,enum', 'nullable', 'array'],
        ];

        if ($this->isMethod('post')) {
            $rules['name'] = ['required', 'string', 'max:255', 'alpha_num'];
            $rules['fields'] = ['required', 'array', 'min:1'];
        }

        return $rules;
    }
}
