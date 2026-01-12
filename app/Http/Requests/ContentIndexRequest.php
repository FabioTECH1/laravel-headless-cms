<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'in:draft,published',
            'page' => 'integer',
            'per_page' => 'integer|max:100',
            'filters' => 'array',
            'sort' => 'nullable', // Can be string or array
            'fields' => 'array',
            'populate' => 'nullable', // Can be string or array
        ];
    }
}
