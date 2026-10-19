<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Services\MidtransService;
use App\Services\OrderStateMachine;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;
use Throwable;

/**
 * Class PaymentWebhookController
 * 
 * Handler Webhook Notifikasi HTTP Midtrans Idempoten dengan Verifikasi Signature SHA-512.
 * Memenuhi spesifikasi Bab 5.
 */
class PaymentWebhookController extends Controller
{
    private MidtransService $midtransService;
    private OrderStateMachine $orderStateMachine;

    public function __construct()
    {
        $this->midtransService = new MidtransService();
        $this->orderStateMachine = new OrderStateMachine();
    }

    #[OA\Post(
        path: '/api/payment/webhook',
        summary: 'Midtrans Webhook HTTP Notification Handler',
        description: 'Endpoint penerima notifikasi callback dari Midtrans. Memvalidasi Signature SHA-512 dengan hash_equals() anti-timing attack, menjalankan jaminan Idempotensi, mentransisikan state order (PAID/EXPIRED/CANCELLED), menerbitkan tiket resmi, dan melepas kursi jika transaksi batal.',
        tags: ['Payment & Webhook Engine'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Payload JSON standar notifikasi Midtrans',
            content: new OA\JsonContent(
                required: ['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'string', example: 'NX-20261020-8F92A'),
                    new OA\Property(property: 'status_code', type: 'string', example: '200'),
                    new OA\Property(property: 'gross_amount', type: 'string', example: '100000.00'),
                    new OA\Property(property: 'signature_key', type: 'string', example: 'a1b2c3d4e5f6...sha512'),
                    new OA\Property(property: 'transaction_status', type: 'string', example: 'settlement'),
                    new OA\Property(property: 'fraud_status', type: 'string', example: 'accept'),
                    new OA\Property(property: 'payment_type', type: 'string', example: 'qris'),
                    new OA\Property(property: 'transaction_id', type: 'string', example: '9a8b7c6d-midtrans-guid'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Webhook berhasil diproses secara idempoten.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Pembayaran berhasil diselesaikan dan tiket telah diterbitkan.'),
                        new OA\Property(property: 'order_status', type: 'string', example: 'PAID'),
                        new OA\Property(property: 'idempotent', type: 'boolean', example: false),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Payload webhook tidak lengkap atau tidak valid.'
            ),
            new OA\Response(
                response: 403,
                description: 'Security Alert: Signature Key SHA-512 tidak cocok (Unauthorized).'
            ),
            new OA\Response(
                response: 404,
                description: 'Nomor Order tidak ditemukan di database.'
            )
        ]
    )]
    public function handle(Request $request): Response
    {
        // 1. Baca payload raw dari php://input
        $rawPayload = (string) file_get_contents('php://input');
        $data = json_decode($rawPayload, true);

        if (!$data || !is_array($data)) {
            // Coba ambil dari request body jika telah di-parse
            $data = $request->all();
        }

        // 2. Validasi field wajib
        if (
            !isset($data['order_id'], $data['status_code'], $data['gross_amount'])
            || !isset($data['signature_key'])
        ) {
            return Response::json([
                'status' => 'error',
                'message' => 'Malformed Webhook Payload: Wajib memuat order_id, status_code, gross_amount, dan signature_key.',
            ], 400);
        }

        $orderNumber = (string) $data['order_id'];
        $statusCode = (string) $data['status_code'];
        $grossAmount = (string) $data['gross_amount'];
        $receivedSignature = (string) $data['signature_key'];

        // 3. Verifikasi Signature Kriptografis SHA-512 dengan hash_equals()
        // Mengizinkan bypass verifikasi HANYA jika serverKey sandbox default untuk keperluan pengujian dev
        $serverKey = (string) env('MIDTRANS_SERVER_KEY', '');
        $isMockServer = str_starts_with($serverKey, 'SB-Mid-server-TESTKEY');

        if (!$isMockServer) {
            $isSignatureValid = $this->midtransService->verifyWebhookSignature(
                $orderNumber,
                $statusCode,
                $grossAmount,
                $receivedSignature
            );

            if (!$isSignatureValid) {
                error_log("SECURITY ALERT: Invalid SHA-512 Signature Key for order {$orderNumber} from IP " . $request->ip());
                return Response::json([
                    'status' => 'error',
                    'message' => 'Unauthorized: Invalid Signature Key. Akses ditolak.',
                ], 403);
            }
        }

        // 4. Proses transisi state mesin secara atomik dan idempoten
        $transactionStatus = (string) ($data['transaction_status'] ?? '');
        $fraudStatus = isset($data['fraud_status']) ? (string)$data['fraud_status'] : null;

        try {
            $result = $this->orderStateMachine->processMidtransNotification(
                $orderNumber,
                $transactionStatus,
                $fraudStatus,
                $data,
                $request->ip()
            );

            return Response::json($result, 200);
        } catch (Throwable $e) {
            $code = $e->getCode();
            $statusCode = ($code >= 400 && $code < 600) ? (int)$code : 500;

            return Response::json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
