<?php

declare(strict_types=1);

namespace App\Requests;

use Core\Http\FormRequest;

/**
 * Class ChangePasswordRequest
 * 
 * Validasi pergantian password akun dari halaman profil.
 */
class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required',
            'new_password'     => 'required|min:8|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required'     => 'Password baru wajib diisi.',
            'new_password.min'          => 'Password baru minimal harus :min karakter.',
        ];
    }
}
