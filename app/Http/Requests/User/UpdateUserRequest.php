<?php declare(strict_types = 1);

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Override;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nickname' => ['sometimes', 'string', 'max:255'],
            'email'    => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'password'  => ['sometimes', 'string', Password::defaults()],
            'tenant_id' => ['sometimes', 'nullable', 'exists:tenants,id'],
            'avatar'    => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'name.string'      => 'The name must be a string',
            'email.email'      => 'The email must be a valid email address',
            'email.unique'     => 'This email is already in use',
            'tenant_id.exists' => 'The selected tenant does not exist',
        ];
    }
}
