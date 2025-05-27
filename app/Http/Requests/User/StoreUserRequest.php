<?php declare(strict_types = 1);

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Override;

class StoreUserRequest extends FormRequest
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
            'nickname'  => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', Password::defaults()],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'avatar'    => ['nullable', 'string', 'max:255'],
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
            'name.required'     => 'The name field is required',
            'email.required'    => 'The email field is required',
            'email.email'       => 'The email must be a valid email address',
            'email.unique'      => 'This email is already in use',
            'password.required' => 'The password field is required',
            'tenant_id.exists'  => 'The selected tenant does not exist',
        ];
    }
}
