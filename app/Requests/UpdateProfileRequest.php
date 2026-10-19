<?php

declare(strict_types=1);

namespace App\Requests;

use Core\Http\FormRequest;

/**
 * Class UpdateProfileRequest
 * 
 * Validasi pembaruan profil pengguna.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|min:3|max:100',
            'phone' => 'nullable|min:8|max:25',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.min'      => 'Nama lengkap minimal :min karakter.',
        ];
    }
}
