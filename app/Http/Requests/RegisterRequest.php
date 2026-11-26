<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'regex:/^[\pL\s]+$/u'],
            'email' => ['required', 'email:rfc,dns', 'unique:users,email', 'regex:/^\S+$/u'],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()      // Requires at least one uppercase and one lowercase letter
                    ->numbers()        // Requires at least one number
                    ->symbols()        // Requires at least one special character
                ,
                'confirmed'
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name field may only contain letters and spaces.',
            'email.unique' => 'An account with this email address already exists.',
            'email.regex' => 'The email address cannot contain any white spaces.',
            'password.min' => 'The password must be at least 8 characters long.',
            'password.confirmed' => 'The password and confirm password field does not match.',
        ];
    }


    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                // This removes leading/trailing spaces and replaces multiple spaces with a single space
                'name' => preg_replace('/\s+/', ' ', trim($this->name)),


            ]);
        }
    }
}
