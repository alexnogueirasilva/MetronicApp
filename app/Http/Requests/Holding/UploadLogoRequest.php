<?php declare(strict_types = 1);

namespace App\Http\Requests\Holding;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UploadLogoRequest extends FormRequest
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
            'logo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ];
    }

    /**
     *
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'logo.required'   => 'O arquivo de logo é obrigatório',
            'logo.image'      => 'O arquivo deve ser uma imagem',
            'logo.mimes'      => 'O logo deve ser um arquivo JPEG, JPG, PNG, GIF ou WebP',
            'logo.max'        => 'O logo deve ter no máximo 2MB',
            'logo.dimensions' => 'O logo deve ter entre 100x100 e 2000x2000 pixels',
        ];
    }
}
