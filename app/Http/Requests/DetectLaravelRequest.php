<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetectLaravelRequest extends FormRequest
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
            'url' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Please enter a website URL to analyze.',
            'url.string' => 'The URL must be a valid text string.',
            'url.max' => 'The URL is too long. Please use a URL with 500 characters or less.',
        ];
    }
}
