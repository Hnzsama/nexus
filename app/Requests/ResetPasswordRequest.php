<?php

declare(strict_types=1);

namespace App\Requests;

use Core\Http\FormRequest;

/**
 * Class ResetPasswordRequest
 * 
 * Validasi eksekusi reset password baru dengan token verifikasi.
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email',
            'token'    => 'required|min:4',
            'password' => 'required|min:8|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Alamat email wajib diisi.',
            'token.required'    => 'Token reset password wajib disertakan.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min'      => 'Password baru minimal harus :min karakter.',
        ];
    }
}
