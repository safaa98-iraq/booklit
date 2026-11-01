<?php

namespace App\Http\Requests\Api;

use App\Enums\QuestionSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** "أظهر الحل" — browse MCQ questions with their answers without solving. */
class ReviewQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => ['required', Rule::enum(QuestionSource::class)],
        ];
    }

    public function attributes(): array
    {
        return ['source' => 'مصدر الأسئلة'];
    }

    public function source(): QuestionSource
    {
        return QuestionSource::from($this->validated('source'));
    }
}
