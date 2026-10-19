<?php

declare(strict_types=1);

namespace App\Requests;

use Core\Http\FormRequest;

/**
 * Class CheckoutRequest
 * 
 * Validasi ketat input formulir checkout transaksi tiket bioskop.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi checkout.
     *
     * @return array<string, string|array<int, string>>
     */
    public function rules(): array
    {
        return [
            'showtime_id'    => 'required|numeric',
            'customer_name'  => 'required|min:3|max:100',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|min:9|max:20',
        ];
    }

    /**
     * Pesan error kustom berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'showtime_id.required'    => 'ID Jadwal tayang (showtime_id) wajib disertakan.',
            'customer_name.required'  => 'Nama lengkap pemesan wajib diisi.',
            'customer_name.min'       => 'Nama pemesan minimal terdiri dari :min karakter.',
            'customer_email.required' => 'Alamat email wajib diisi untuk pengiriman e-ticket.',
            'customer_email.email'    => 'Format alamat email tidak valid.',
            'customer_phone.required' => 'Nomor WhatsApp / telepon wajib diisi.',
            'customer_phone.min'      => 'Nomor telepon minimal :min digit.',
        ];
    }

    /**
     * Label nama atribut.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'showtime_id'    => 'Jadwal Tayang',
            'customer_name'  => 'Nama Pemesan',
            'customer_email' => 'Email',
            'customer_phone' => 'Nomor Telepon',
        ];
    }
}
