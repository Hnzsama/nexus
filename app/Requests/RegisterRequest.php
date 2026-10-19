<?php

declare(strict_types=1);

namespace App\Requests;

use Core\Http\FormRequest;

/**
 * Class RegisterRequest
 * 
 * Validasi ketat registrasi pengguna baru.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:150',
            'password' => 'required|min:8|max:100',
            'phone'    => 'nullable|min:8|max:25',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama lengkap wajib diisi.',
            'name.min'          => 'Nama lengkap minimal :min karakter.',
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format alamat email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal harus :min karakter demi keamanan.',
        ];
    }
}
