<?php declare(strict_types = 1);

namespace App\Http\Requests\Tenant;

use App\Enums\PlanType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Apenas administradores ou proprietários do tenant podem atualizar
        return true; // Substituir pela lógica de autorização adequada
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'max:255'],
            'domain' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($this->route('tenant')),
            ],
            'plan'                             => ['sometimes', 'string', Rule::enum(PlanType::class)],
            'is_active'                        => ['sometimes', 'boolean'],
            'trial_ends_at'                    => ['sometimes', 'nullable', 'date'],
            'settings'                         => ['sometimes', 'nullable', 'array'],
            'settings.custom_rate_limit'       => ['sometimes', 'nullable', 'integer', 'min:0'],
            'settings.max_concurrent_requests' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain.unique' => 'Este domínio já está sendo utilizado',
            'plan.enum'     => 'O plano selecionado não é válido',
        ];
    }
}
