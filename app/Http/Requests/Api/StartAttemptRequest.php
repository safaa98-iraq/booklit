<?php

namespace App\Http\Requests\Api;

use App\Enums\AttemptMode;
use App\Enums\Difficulty;
use App\Models\Booklet;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // access is checked in the controller (policy + subscription)
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(AttemptMode::class)],
            'booklet_id' => ['required_without:subject_id', 'nullable', 'integer', Rule::exists('booklets', 'id')],
            'subject_id' => ['required_without:booklet_id', 'nullable', 'integer', Rule::exists('subjects', 'id')],
            'difficulty' => [
                Rule::requiredIf(fn () => $this->input('mode') === AttemptMode::Quiz->value),
                'nullable',
                Rule::enum(Difficulty::class),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'mode' => 'نوع الأسئلة',
            'booklet_id' => 'الملزمة',
            'subject_id' => 'المادة',
            'difficulty' => 'مستوى الصعوبة',
        ];
    }

    public function mode(): AttemptMode
    {
        return AttemptMode::from($this->validated('mode'));
    }

    public function difficulty(): ?Difficulty
    {
        return Difficulty::tryFrom((string) $this->validated('difficulty'));
    }

    public function booklet(): ?Booklet
    {
        $id = $this->validated('booklet_id');

        return $id ? Booklet::with('subject.stage')->findOrFail($id) : null;
    }

    public function subject(): ?Subject
    {
        $id = $this->validated('subject_id');

        return $id ? Subject::with('stage')->findOrFail($id) : null;
    }
}
