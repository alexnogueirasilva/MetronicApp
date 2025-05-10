<?php declare(strict_types = 1);

namespace App\Http\Requests\Auth;

use App\DTO\Auth\LoginDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<string>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'device'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): LoginDTO
    {
        /** @var array{email: string, password: string, device?: string} $data */
        $data = $this->validated();

        return LoginDTO::fromRequest($data);
    }
}
