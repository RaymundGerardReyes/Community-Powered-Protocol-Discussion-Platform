<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'verdict' => ['nullable', 'in:approved,changes_requested,rejected'],
            'summary' => ['nullable', 'string', 'max:255'],
            'feedback' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
        ];
    }
}
