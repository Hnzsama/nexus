<?php

declare(strict_types=1);

namespace App\Services;

use Exception;

/**
 * Class MidtransService
 * 
 * Service server-side untuk integrasi resmi Midtrans Snap API & Verifikasi Kriptografi Signature.
 * Memenuhi spesifikasi Bab 1, 4, dan 5.
 */
class MidtransService
{
    private string $serverKey;
    private string $clientKey;
    private bool $isProduction;
    private string $snapBaseUrl;

    public function __construct()
    {
        $this->serverKey = (string) env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-TESTKEY123456');
        $this->clientKey = (string) env('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-TESTKEY123456');
        $this->isProduction = (bool) env('MIDTRANS_IS_PRODUCTION', false);

        $this->snapBaseUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /**
     * Dapatkan Client Key publik untuk Frontend Snap.js
     */
    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    /**
     * Request Snap Token dari Midtrans dengan Asymmetric Custom Expiry 540 Detik (9 Menit).
     *
     * @param string $orderNumber Nomor order unik (NEXUS-YYYYMMDD-XXXX)
     * @param float $totalAmount Total nilai transaksi
     * @param array<int, array{id: string|int, price: float, quantity: int, name: string}> $items Daftar kursi/tiket
     * @param array{name: string, email: string, phone: string} $customer Info pembeli
     * @return array{token: string, redirect_url: string}
     * @throws Exception Jika HTTP request ke Midtrans gagal
     */
    public function createSnapTransaction(
        string $orderNumber,
        float $totalAmount,
        array $items,
        array $customer
    ): array {
        // Konfigurasi Sinkronisasi Expiry Asimetris (Bab 4 & Bab 5)
        // Midtrans diberi TTL 9 menit (540 detik), sedangkan database lokal hold bertahan 10 menit (600 detik).
        // Buffer 60 detik ini mencegah insiden uang terpotong saat kursi sudah dilepas.
        $payload = [
            'transaction_details' => [
                'order_id' => $orderNumber,
                'gross_amount' => (int) round($totalAmount),
            ],
            'item_details' => array_map(function ($item) {
                return [
                    'id' => (string) $item['id'],
                    'price' => (int) round($item['price']),
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'name' => mb_substr((string) $item['name'], 0, 50),
                ];
            }, $items),
            'customer_details' => [
                'first_name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
            ],
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => 9, // Tepat 540 Detik
            ],
        ];

        // Jika running mode simulasi / development tanpa server key asli, hasilkan token sandbox mock
        if (str_starts_with($this->serverKey, 'SB-Mid-server-TESTKEY')) {
            $mockToken = 'MOCK-SNAP-' . strtoupper(bin2hex(random_bytes(12)));
            return [
                'token' => $mockToken,
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $mockToken,
            ];
        }

        $ch = curl_init($this->snapBaseUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new Exception("Koneksi ke Midtrans Snap API gagal: {$curlError}");
        }

        if ($httpCode !== 201 && $httpCode !== 200) {
            throw new Exception("Midtrans Snap API mengembalikan kode HTTP {$httpCode}: {$response}");
        }

        $data = json_decode((string) $response, true);
        if (!isset($data['token'])) {
            throw new Exception("Response Midtrans tidak memuat snap token: {$response}");
        }

        return [
            'token' => (string) $data['token'],
            'redirect_url' => (string) ($data['redirect_url'] ?? ''),
        ];
    }

    /**
     * Verifikasi Signature Key Kriptografis SHA-512 dari Webhook Notifikasi Midtrans.
     * Menggunakan hash_equals() guna mencegah Timing-Attack Vulnerability.
     *
     * Formula: SHA-512(order_id + status_code + gross_amount + ServerKey)
     */
    public function verifyWebhookSignature(
        string $orderNumber,
        string $statusCode,
        string|float $grossAmount,
        string $receivedSignature
    ): bool {
        // Standarisasi nilai desimal amount untuk hashing yang identik
        $formattedAmount = is_numeric($grossAmount) && str_contains((string)$grossAmount, '.')
            ? (string) $grossAmount
            : number_format((float)$grossAmount, 2, '.', '');

        // Coba varian desimal .00 maupun integer untuk kompatibilitas format Midtrans
        $variations = [
            $orderNumber . $statusCode . $grossAmount . $this->serverKey,
            $orderNumber . $statusCode . $formattedAmount . $this->serverKey,
            $orderNumber . $statusCode . (string)((int) round((float)$grossAmount)) . '.00' . $this->serverKey,
            $orderNumber . $statusCode . (string)((int) round((float)$grossAmount)) . $this->serverKey,
        ];

        foreach ($variations as $var) {
            $expectedSignature = hash('sha512', $var);
            if (hash_equals($expectedSignature, $receivedSignature)) {
                return true;
            }
        }

        return false;
    }
}
