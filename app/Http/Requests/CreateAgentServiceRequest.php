<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'message_number' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'featured_image' => 'nullable|string|max:255',
            'banner_image' => 'nullable|string|max:255',
            'category_id' => 'required|exists:services,id',
            'role_id' => 'required|exists:roles,id',
        ];
    }
}
