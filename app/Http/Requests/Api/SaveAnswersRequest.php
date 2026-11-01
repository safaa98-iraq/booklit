<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SaveAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('attempt'));
    }

    public function rules(): array
    {
        return [
            'answers' => [$this->answersRequired() ? 'required' : 'sometimes', 'array', 'max:500'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.option_id' => ['present', 'nullable', 'integer'],
        ];
    }

    protected function answersRequired(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'answers' => 'الإجابات',
            'answers.*.question_id' => 'السؤال',
            'answers.*.option_id' => 'الخيار',
        ];
    }

    /** @return list<array{question_id: int, option_id: ?int}> */
    public function answers(): array
    {
        return array_map(fn (array $a) => [
            'question_id' => (int) $a['question_id'],
            'option_id' => isset($a['option_id']) ? (int) $a['option_id'] : null,
        ], $this->validated('answers', []));
    }
}
