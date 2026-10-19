<?php

declare(strict_types=1);

namespace App\Requests;

use Core\Http\FormRequest;

/**
 * Class VerifyEmailRequest
 * 
 * Validasi token verifikasi email pengguna.
 */
class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|min:4',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Token verifikasi email wajib disertakan.',
        ];
    }
}
