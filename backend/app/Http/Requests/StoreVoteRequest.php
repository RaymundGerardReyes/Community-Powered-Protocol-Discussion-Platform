<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('type') && ! $this->has('votable_type')) {
            $merge['votable_type'] = $this->input('type');
        }
        if ($this->has('id') && ! $this->has('votable_id')) {
            $merge['votable_id'] = $this->input('id');
        }
        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
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
