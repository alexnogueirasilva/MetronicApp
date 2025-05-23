<?php declare(strict_types = 1);

namespace App\Http\Requests\Acl;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RoleStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<string>|string>
     */
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255', 'unique:roles,name'],
            'icon'         => ['nullable', 'string', 'max:255'],
            'icon_class'   => ['nullable', 'string', 'max:255'],
            'fill_class'   => ['nullable', 'string', 'max:255'],
            'stroke_class' => ['nullable', 'string', 'max:255'],
            'size_class'   => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'is_default'   => ['boolean'],
        ];
    }
}
