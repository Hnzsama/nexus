<?php

declare(strict_types=1);

namespace App\Handlers\Admin;

use App\Handlers\Controller;
use Core\Database\Connection;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;
use PDO;

/**
 * Class OrderController (Admin)
 * 
 * Pengendali laporan transaksi dan monitoring pesanan tiket untuk Administrator.
 */
class OrderController extends Controller
{
    #[OA\Get(
        path: '/api/admin/orders',
        summary: '[Admin] Laporan Transaksi & Pesanan Tiket',
        description: 'Mengambil daftar transaksi pesanan tiket dengan filter status (PAID, UNPAID, CANCELLED) dan total pendapatan.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'PAID'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Laporan transaksi berhasil diambil.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 403, description: 'Forbidden.')
        ]
    )]
    public function index(Request $request): Response
    {
        $statusFilter = $request->query('status');
        $pdo = Connection::getPdo();

        $sql = "
            SELECT o.*, u.name as user_name, u.email as user_email,
                   m.title as movie_title, s.name as studio_name, c.name as cinema_name,
                   p.payment_type, p.transaction_status, p.settlement_time
            FROM orders o
            JOIN users u ON u.id = o.user_id
            JOIN showtimes st ON st.id = o.showtime_id
            JOIN movies m ON m.id = st.movie_id
            JOIN studios s ON s.id = st.studio_id
            JOIN cinemas c ON c.id = s.cinema_id
            LEFT JOIN payments p ON p.order_id = o.id
        ";

        $params = [];
        if (!empty($statusFilter)) {
            $sql .= " WHERE o.status = ?";
            $params[] = strtoupper((string) $statusFilter);
        }
        $sql .= " ORDER BY o.created_at DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hitung total pendapatan dari order berstatus PAID
        $revenue = (float) $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'PAID'")->fetchColumn();

        return Response::json([
            'status'        => 'success',
            'total_revenue' => $revenue,
            'count'         => count($orders),
            'data'          => $orders,
        ]);
    }
}
