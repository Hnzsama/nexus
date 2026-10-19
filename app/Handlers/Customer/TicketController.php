<?php

declare(strict_types=1);

namespace App\Handlers\Customer;

use App\Handlers\Controller;
use Core\Database\Connection;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;
use PDO;

/**
 * Class TicketController
 * 
 * Handler customer untuk melihat daftar e-tiket digital dan status check-in QR code.
 */
class TicketController extends Controller
{
    #[OA\Get(
        path: '/api/tickets',
        summary: 'Daftar E-Tiket Saya',
        description: 'Mengambil riwayat tiket bioskop milik pengguna yang sedang login.',
        tags: ['Transactions & Checkout'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar tiket pengguna berhasil diambil.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.')
        ]
    )]
    public function myTickets(Request $request): Response
    {
        $user = $request->user();
        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        $userId = (int) ($user->id ?? 0);
        $pdo = Connection::getPdo();

        $stmt = $pdo->prepare("
            SELECT t.id as ticket_id, t.ticket_code, t.qr_token, t.checkin_status, t.checked_in_at,
                   o.order_number, o.created_at as order_date,
                   m.title as movie_title, m.poster_url,
                   s.name as studio_name, s.studio_type,
                   c.name as cinema_name, c.city as cinema_city,
                   st.start_time, st.end_time,
                   st_seat.row_label, st_seat.seat_number, st_seat.seat_type
            FROM tickets t
            JOIN order_items oi ON oi.id = t.order_item_id
            JOIN orders o ON o.id = oi.order_id
            JOIN showtimes st ON st.id = o.showtime_id
            JOIN movies m ON m.id = st.movie_id
            JOIN studios s ON s.id = st.studio_id
            JOIN cinemas c ON c.id = s.cinema_id
            JOIN seats st_seat ON st_seat.id = oi.seat_id
            WHERE o.user_id = ? AND o.status = 'PAID'
            ORDER BY st.start_time DESC
        ");
        $stmt->execute([$userId]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::json([
            'status' => 'success',
            'data'   => $tickets,
        ]);
    }
}
