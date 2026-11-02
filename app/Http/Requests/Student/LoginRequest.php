<?php

namespace App\Http\Requests\Student;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => Phone::normalize($this->input('phone'))]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ];
    }
}
