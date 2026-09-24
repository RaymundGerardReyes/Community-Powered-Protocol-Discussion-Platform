<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoteRequest extends FormRequest
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
            'votable_type' => ['required', 'string'],
            'votable_id' => ['required', 'integer', 'min:1'],
            'value' => ['required', 'integer', 'in:1,-1'],
        ];
    }
}
