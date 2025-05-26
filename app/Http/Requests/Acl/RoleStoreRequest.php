<?php declare(strict_types = 1);

namespace App\Http\Requests\Acl;

use App\DTO\ACL\CreateRoleData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use JsonException;

class RoleStoreRequest extends FormRequest
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
            'name'        => ['required', 'string', 'max:255', 'unique:roles,name'],
            'icon'        => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array'],
            'description' => ['nullable', 'string'],
            'is_default'  => ['boolean'],
        ];
    }

    /**
     * @throws JsonException
     */
    public function toDto(): CreateRoleData
    {
        $data = $this->validated();

        return new CreateRoleData(
            name: toString($data['name']),
            description: toString($data['description'] ?? null),
            icon: toString($data['icon'] ?? null),
            permissions: isset($data['permissions']) && is_array($data['permissions'])
                ? array_values(array_map(
                    static function (mixed $permission): int|string {
                        if (is_array($permission) && isset($permission['id'])) {
                            return toString($permission['id']);
                        }

                        if (is_int($permission)) {
                            return $permission;
                        }

                        if (is_string($permission)) {
                            return $permission;
                        }

                        if (is_scalar($permission)) {
                            return (string)$permission;
                        }

                        return '';
                    },
                    $data['permissions']
                ))
                : null
        );
    }
}
