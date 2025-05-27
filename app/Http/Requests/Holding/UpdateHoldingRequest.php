<?php
declare(strict_types = 1);

namespace App\Http\Requests\Holding;

use App\Helpers\CnpjHelper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHoldingRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $holding = $this->route('holding');

        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'legal_name' => ['sometimes', 'string', 'max:255'],
            'tax_id'     => [
                'sometimes',
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
                Rule::unique('holdings', 'tax_id')
                    ->where(function ($query) {
                        if (is_object($query) && method_exists($query, 'where')) {
                            return $query->where('tenant_id', auth()->user()?->tenant_id);
                        }

                        return $query;
                    })
                    ->ignore($holding),
            ],
            'email'            => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:20'],
            'description'      => ['sometimes', 'nullable', 'string', 'max:1000'],
            'settings'         => ['sometimes', 'nullable', 'array'],
            'address'          => ['sometimes', 'nullable', 'array'],
            'address.street'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.number'   => ['sometimes', 'nullable', 'string', 'max:20'],
            'address.district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address.city'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'address.state'    => ['sometimes', 'nullable', 'string', 'max:2'],
            'address.zip'      => ['sometimes', 'nullable', 'string', 'max:10'],
            'address.country'  => ['sometimes', 'nullable', 'string', 'max:2'],
            'is_active'        => ['sometimes', 'boolean'],
        ];
    }
}
