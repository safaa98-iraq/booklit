<?php

namespace App\Http\Requests\Student;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^07\d{9}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'يرجى إدخال رقم هاتف عراقي صحيح يبدأ بـ 07 ويتكون من 11 رقماً.',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً.',
        ];
    }
}
