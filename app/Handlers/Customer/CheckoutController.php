<?php

declare(strict_types=1);

namespace App\Handlers\Customer;

use App\Handlers\Controller;
use App\Requests\CheckoutRequest;
use App\Services\BookingService;
use Core\Database\Connection;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;
use PDO;
use Throwable;

/**
 * Class CheckoutController
 * 
 * Handler customer untuk orkestrasi checkout transaksi, penguncian kursi atomik, dan perolehan Snap Token Midtrans.
 */
class CheckoutController extends Controller
{
    private BookingService $bookingService;

    public function __construct()
    {
        $this->bookingService = new BookingService();
    }

    #[OA\Post(
        path: '/api/checkout/create-transaction',
        summary: 'Atomic Checkout & Request Midtrans Snap Token',
        description: 'Mengunci kursi secara atomik dengan Canonical Sorting (seat_ids ASC), Row Locking (SELECT ... FOR UPDATE), validasi double-check conflict, dan request Snap Token Midtrans dengan masa berlaku 540 detik.',
        tags: ['Transactions & Checkout'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Isi formulir checkout pemesanan tiket:',
            content: [
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(
                        required: ['showtime_id', 'seat_ids', 'customer_name', 'customer_email', 'customer_phone'],
                        properties: [
                            new OA\Property(property: 'showtime_id', type: 'integer', example: '', description: 'ID Jadwal Tayang'),
                            new OA\Property(
                                property: 'seat_ids',
                                type: 'array',
                                items: new OA\Items(type: 'integer'),
                                description: 'Daftar ID Kursi'
                            ),
                            new OA\Property(property: 'customer_name', type: 'string', example: '', description: 'Nama Lengkap'),
                            new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: '', description: 'Alamat Email'),
                            new OA\Property(property: 'customer_phone', type: 'string', example: '', description: 'Nomor WhatsApp / HP'),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['showtime_id', 'seat_ids', 'customer_name', 'customer_email', 'customer_phone'],
                    properties: [
                        new OA\Property(property: 'showtime_id', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'seat_ids',
                            type: 'array',
                            items: new OA\Items(type: 'integer', example: 12),
                            example: [11, 12]
                        ),
                        new OA\Property(property: 'customer_name', type: 'string', example: 'Budi Santoso'),
                        new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'budi@example.com'),
                        new OA\Property(property: 'customer_phone', type: 'string', example: '081234567890'),
                    ]
                )
            ]
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Transaksi berhasil dibuat dan Snap Token diperoleh.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'order_number', type: 'string', example: 'NX-20261020-8F92A'),
                        new OA\Property(property: 'snap_token', type: 'string', example: 'd3b07384-d113-4a11-8a9d-mock'),
                        new OA\Property(property: 'redirect_url', type: 'string', example: 'https://app.sandbox.midtrans.com/snap/v2/vtweb/...'),
                        new OA\Property(property: 'expires_at', type: 'string', example: '2026-10-20 00:50:00'),
                        new OA\Property(property: 'expires_in_seconds', type: 'integer', example: 600),
                        new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 100000.00),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Pengguna belum terotentikasi (Unauthorized). Silakan login terlebih dahulu.'),
            new OA\Response(
                response: 409,
                description: 'Konflik: Kursi telah dipesan atau sedang di-hold pelanggan lain.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Kursi pilihan Anda sedang dipilih pengguna lain.')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Parameter formulir checkout tidak valid.')
        ]
    )]
    public function createTransaction(CheckoutRequest $request): Response
    {
        $validated = $request->validated();

        $showtimeId = (int) $validated['showtime_id'];
        $seatIds = (array) $request->input('seat_ids', []);

        if (empty($seatIds)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Silakan pilih minimal 1 kursi sebelum melakukan checkout.',
            ], 422);
        }

        $customer = [
            'name'  => (string) $validated['customer_name'],
            'email' => (string) $validated['customer_email'],
            'phone' => (string) $validated['customer_phone'],
        ];

        // Wajibkan login untuk checkout tiket
        $user = $request->user();
        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Unauthorized: Silakan login terlebih dahulu untuk melakukan pemesanan tiket.',
            ], 401);
        }
        $userId = (int) ($user->id ?? 1);

        try {
            $result = $this->bookingService->lockSeatsAndCreateOrder(
                $showtimeId,
                $seatIds,
                $userId,
                $customer
            );

            return Response::json([
                'status'  => 'success',
                'message' => 'Kursi berhasil dikunci selama 10 menit. Silakan selesaikan pembayaran.',
                'data'    => $result,
            ], 201);
        } catch (Throwable $e) {
            $code = $e->getCode();
            $statusCode = ($code >= 400 && $code < 600) ? (int)$code : 500;

            return Response::json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    #[OA\Get(
        path: '/api/orders/{orderNumber}',
        summary: 'Detail Transaksi & Tiket Resmi',
        description: 'Mengecek status pembayaran order dan mengambil daftar tiket resmi yang telah diterbitkan (QR Token & Barcode Code).',
        tags: ['Transactions & Checkout'],
        parameters: [
            new OA\Parameter(
                name: 'orderNumber',
                in: 'path',
                required: true,
                description: 'Nomor Order Transaksi (NX-*)',
                schema: new OA\Schema(type: 'string', example: 'NX-20261020-8F92A')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail order dan tiket.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'order', type: 'object'),
                        new OA\Property(property: 'payment', type: 'object'),
                        new OA\Property(property: 'tickets', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Order tidak ditemukan.')
        ]
    )]
    public function orderDetail(Request $request, string $orderNumber): Response
    {
        $pdo = Connection::getPdo();

        $orderStmt = $pdo->prepare("
            SELECT o.*, st.start_time, st.end_time, m.title as movie_title, m.poster_url,
                   s.name as studio_name, c.name as cinema_name, c.city
            FROM orders o
            JOIN showtimes st ON st.id = o.showtime_id
            JOIN movies m ON m.id = st.movie_id
            JOIN studios s ON s.id = st.studio_id
            JOIN cinemas c ON c.id = s.cinema_id
            WHERE o.order_number = ?
        ");
        $orderStmt->execute([$orderNumber]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return Response::json([
                'status'  => 'error',
                'message' => "Order dengan nomor '{$orderNumber}' tidak ditemukan.",
            ], 404);
        }

        // Ambil info pembayaran
        $payStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
        $payStmt->execute([$order['id']]);
        $payment = $payStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Ambil tiket yang telah diterbitkan
        $ticketsStmt = $pdo->prepare("
            SELECT t.*, s.row_label, s.seat_number, s.seat_type
            FROM tickets t
            JOIN order_items oi ON oi.id = t.order_item_id
            JOIN seats s ON s.id = oi.seat_id
            WHERE oi.order_id = ?
        ");
        $ticketsStmt->execute([$order['id']]);
        $tickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::json([
            'status'  => 'success',
            'order'   => $order,
            'payment' => $payment,
            'tickets' => $tickets,
        ]);
    }
}
