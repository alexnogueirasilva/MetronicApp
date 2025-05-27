<?php
declare(strict_types = 1);

namespace App\Http\Requests\Holding;

use App\Helpers\CnpjHelper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JsonException;
use Override;

class CreateHoldingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorização será feita via Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'tax_id'     => [
                'required',
                'string',
                'max:18',
                function ($attribute, $value, $fail): void {
                    if (is_callable($fail)) {
                        $valueAsString = '';

                        if (is_string($value)) {
                            $valueAsString = $value;
                        } elseif (is_numeric($value)) {
                            $valueAsString = (string) $value;
                        } elseif (is_object($value) && method_exists($value, '__toString')) {
                            $valueAsString = $value->__toString();
                        }
                        $sanitized = CnpjHelper::sanitize(toString($valueAsString));

                        if (!CnpjHelper::isValid($sanitized)) {
                            $fail('O CNPJ informado é inválido.');
                        }
                    }
                },
                Rule::unique('holdings', 'tax_id')->where(function ($query) {
                    if (is_object($query) && method_exists($query, 'where')) {
                        return $query->where('tenant_id', auth()->user()?->tenant_id);
                    }

                    return $query;
                }),
            ],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'settings'         => ['nullable', 'array'],
            'address'          => ['nullable', 'array'],
            'address.street'   => ['nullable', 'string', 'max:255'],
            'address.number'   => ['nullable', 'string', 'max:20'],
            'address.district' => ['nullable', 'string', 'max:100'],
            'address.city'     => ['nullable', 'string', 'max:100'],
            'address.state'    => ['nullable', 'string', 'max:2'],
            'address.zip'      => ['nullable', 'string', 'max:10'],
            'address.country'  => ['nullable', 'string', 'max:2'],
            'is_active'        => ['boolean'],
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
            'name.required'       => 'O nome do holding é obrigatório',
            'legal_name.required' => 'A razão social é obrigatória',
            'tax_id.required'     => 'O CNPJ é obrigatório',
            'tax_id.unique'       => 'Este CNPJ já está cadastrado',
            'email.email'         => 'O e-mail deve ser um endereço válido',
            'phone.max'           => 'O telefone deve ter no máximo 20 caracteres',
            'description.max'     => 'A descrição deve ter no máximo 1000 caracteres',
        ];
    }

    /**
     * Prepare the data for validation.
     * @throws JsonException
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('tax_id')) {
            $taxId = $this->tax_id;
            $this->merge([
                'tax_id' => $this->convertToSanitizedString($taxId),
            ]);
        }
    }

    /**
     * Convert a mixed value to a sanitized string for CNPJ.
     * @throws JsonException
     */
    private function convertToSanitizedString(mixed $value): string
    {
        $valueAsString = '';

        if (is_string($value)) {
            $valueAsString = $value;
        } elseif (is_numeric($value)) {
            $valueAsString = (string) $value;
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            $valueAsString = $value->__toString();
        }

        return CnpjHelper::sanitize(toString($valueAsString));
    }
}
