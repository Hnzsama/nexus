<?php

declare(strict_types=1);

namespace App\Requests;

use Core\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kustomisasi URL redirect jika validasi gagal.
     */
    public function redirectTo(): ?string
    {
        return route('users.index');
    }

    /**
     * Aturan validasi yang berlaku untuk penambahan user baru.
     *
     * @return array<string, string|array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name'     => 'required|min:3|max:60',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ];
    }

    /**
     * Pesan error kustom untuk aturan validasi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'Nama lengkap wajib diisi.',
            'name.min'          => 'Nama lengkap minimal memiliki :min karakter.',
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format email tidak valid (contoh: user@nexus.dev).',
            'email.unique'      => 'Alamat email sudah digunakan oleh user lain.',
            'password.required' => 'Kata sandi wajib diisi untuk keamanan akun.',
            'password.min'      => 'Kata sandi minimal terdiri dari :min karakter.',
        ];
    }

    /**
     * Label nama atribut yang ramah pengguna.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'     => 'Nama Lengkap',
            'email'    => 'Alamat Email',
            'password' => 'Kata Sandi',
        ];
    }
}