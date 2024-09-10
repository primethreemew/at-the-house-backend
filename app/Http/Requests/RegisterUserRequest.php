<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function wantsJson()
    {
        return true;  // Always return JSON response
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email',
            // 'phone' => 'required|regex:/^\+(?:[0-9]●?){6,14}[0-9]$/|unique:users',
            'password' => 'required|min:8',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'phone.regex' => 'The phone number must be a valid international phone number with a leading "+" sign.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters long.',
        ];
    }
}
