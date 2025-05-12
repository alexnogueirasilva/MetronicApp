<?php declare(strict_types = 1);

namespace App\Http\Requests\Tenant;

use App\Enums\PlanType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Apenas administradores podem criar tenants
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
            'name'                             => ['required', 'string', 'max:255'],
            'domain'                           => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'plan'                             => ['required', 'string', Rule::enum(PlanType::class)],
            'is_active'                        => ['boolean'],
            'trial_ends_at'                    => ['nullable', 'date'],
            'settings'                         => ['nullable', 'array'],
            'settings.custom_rate_limit'       => ['nullable', 'integer', 'min:0'],
            'settings.max_concurrent_requests' => ['nullable', 'integer', 'min:0'],
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
            'name.required' => 'O nome do tenant é obrigatório',
            'domain.unique' => 'Este domínio já está sendo utilizado',
            'plan.required' => 'O plano é obrigatório',
            'plan.enum'     => 'O plano selecionado não é válido',
        ];
    }
}
