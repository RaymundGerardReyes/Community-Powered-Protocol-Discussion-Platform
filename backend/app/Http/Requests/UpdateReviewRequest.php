<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'verdict' => ['sometimes', 'in:approved,changes_requested,rejected'],
            'summary' => ['sometimes', 'string', 'max:255'],
            'findings' => ['nullable', 'string'],
        ];
    }
}
