<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],
            'first_name'  => [
                'required',
                'string',
            ],
            'last_name'  => [
                'required',
                'string',
            ],
            'role'  => [
                'required',
                'string',
                'exists:roles,name'
            ],
            'dark_mode' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
