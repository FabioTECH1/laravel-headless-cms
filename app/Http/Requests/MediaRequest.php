<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('put')) {
            return [
                'alt_text' => 'nullable|string|max:255',
                'caption' => 'nullable|string',
                'folder_id' => 'nullable|exists:media_folders,id',
            ];
        }

        return [
            'file' => 'required|file|max:2048', // Max 2MB
            'folder_id' => 'nullable|exists:media_folders,id',
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
        ];
    }
}
