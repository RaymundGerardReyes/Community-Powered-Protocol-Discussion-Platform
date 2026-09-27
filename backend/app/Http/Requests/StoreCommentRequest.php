<?php

namespace App\Http\Requests;

use App\Models\Thread;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
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
        $thread = $this->route('thread');
        $threadId = $thread instanceof Thread ? $thread->id : (int) $thread;

        return [
            'content' => ['required', 'string'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('comments', 'id')->where('thread_id', $threadId),
            ],
        ];
    }
}
