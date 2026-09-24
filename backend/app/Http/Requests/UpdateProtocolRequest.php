<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
