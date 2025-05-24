<?php declare(strict_types = 1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Override;

class ImpersonateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        // Somente usuários com permissão de impersonar podem fazer essa requisição
        return $user && $user->hasPermission('impersonate-users');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = Auth::user();

        return [
            // Não permite impersonar a si mesmo
            'user_id' => [
                'sometimes',
                'required',
                'exists:users,id',
                Rule::notIn([$user->id]),
            ],
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
            'user_id.not_in' => 'Você não pode impersonar a si mesmo.',
        ];
    }
}
