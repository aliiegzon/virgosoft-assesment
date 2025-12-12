<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user');
        $email = $this->request->get('email') ?? null;

        return [
            'email' => [
                'email',
                Rule::unique('users')->where(function ($query) use ($email) {
                    $query->where('email', $email);
                })->ignore($userId),
            ],
            'first_name'  => [
                'string',
            ],
            'last_name'  => [
                'string',
            ],
            'role'  => [
                'string',
                'exists:roles,name'
            ],
            'is_active' => [
                'boolean'
            ],
            'dark_mode' => [
                'boolean',
            ],
        ];
    }
}
