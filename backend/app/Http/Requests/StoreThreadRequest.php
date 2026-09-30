<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('body') && ! $this->has('content')) {
            $this->merge(['content' => $this->input('body')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required_without:body', 'nullable', 'string'],
            'body' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }
}
