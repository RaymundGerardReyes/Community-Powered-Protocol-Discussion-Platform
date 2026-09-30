<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('content') && ! $this->has('description')) {
            $this->merge(['description' => $this->input('content')]);
        }
        if (! $this->has('category')) {
            $tags = $this->input('tags', []);
            $this->merge(['category' => ! empty($tags[0]) ? ucfirst($tags[0]) : 'General']);
        }
        if ($this->has('tags')) {
            $metadata = $this->input('metadata', []);
            $metadata['tags'] = (array) $this->input('tags');
            $this->merge(['metadata' => $metadata]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required_without:content', 'nullable', 'string'],
            'content' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'version' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
