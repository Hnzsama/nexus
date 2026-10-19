<?php

declare(strict_types=1);

namespace App\Handlers\Admin;

use App\Handlers\Controller;
use App\Models\Showtime;
use Core\Database\Connection;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;
use PDO;

/**
 * Class ShowtimeController (Admin)
 * 
 * Pengendali slot jadwal penayangan film untuk Administrator.
 */
class ShowtimeController extends Controller
{
    #[OA\Get(
        path: '/api/admin/showtimes',
        summary: '[Admin] Daftar Seluruh Jadwal Tayang',
        description: 'Mengambil seluruh slot jadwal tayang di seluruh studio dan bioskop.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar jadwal tayang berhasil diambil.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 403, description: 'Forbidden.')
        ]
    )]
    public function index(Request $request): Response
    {
        $pdo = Connection::getPdo();
        $stmt = $pdo->query("
            SELECT st.*, m.title as movie_title, s.name as studio_name, c.name as cinema_name
            FROM showtimes st
            JOIN movies m ON m.id = st.movie_id
            JOIN studios s ON s.id = st.studio_id
            JOIN cinemas c ON c.id = s.cinema_id
            ORDER BY st.start_time DESC
        ");
        $showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::json([
            'status' => 'success',
            'data'   => $showtimes,
        ]);
    }

    #[OA\Post(
        path: '/api/admin/showtimes',
        summary: '[Admin] Buat Jadwal Tayang Baru',
        description: 'Menambahkan slot jadwal tayang film di studio tertentu dengan harga dasar tiket.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['movie_id', 'studio_id', 'start_time', 'end_time', 'base_price'],
                properties: [
                    new OA\Property(property: 'movie_id', type: 'integer', example: 1),
                    new OA\Property(property: 'studio_id', type: 'integer', example: 1),
                    new OA\Property(property: 'start_time', type: 'string', example: '2026-10-25 14:00:00'),
                    new OA\Property(property: 'end_time', type: 'string', example: '2026-10-25 16:30:00'),
                    new OA\Property(property: 'base_price', type: 'number', example: 50000.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Jadwal tayang berhasil dibuat.'),
            new OA\Response(response: 422, description: 'Validasi input gagal.')
        ]
    )]
    public function store(Request $request): Response
    {
        $movieId = (int) $request->input('movie_id', 0);
        $studioId = (int) $request->input('studio_id', 0);
        $startTime = (string) $request->input('start_time', '');
        $endTime = (string) $request->input('end_time', '');
        $basePrice = (float) $request->input('base_price', 0);

        if ($movieId <= 0 || $studioId <= 0 || empty($startTime) || empty($endTime) || $basePrice <= 0) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Parameter movie_id, studio_id, start_time, end_time, dan base_price wajib valid.',
            ], 422);
        }

        $showtime = new Showtime();
        $showtime->movie_id = $movieId;
        $showtime->studio_id = $studioId;
        $showtime->start_time = $startTime;
        $showtime->end_time = $endTime;
        $showtime->base_price = $basePrice;
        $showtime->status = 'ACTIVE';
        $showtime->save();

        return Response::json([
            'status'  => 'success',
            'message' => 'Jadwal tayang baru berhasil ditambahkan.',
            'data'    => $showtime,
        ], 201);
    }

    #[OA\Delete(
        path: '/api/admin/showtimes/{id}',
        summary: '[Admin] Hapus Jadwal Tayang',
        description: 'Membatalkan/menghapus jadwal tayang.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Jadwal tayang berhasil dihapus.'),
            new OA\Response(response: 404, description: 'Jadwal tidak ditemukan.')
        ]
    )]
    public function destroy(Request $request, string $id): Response
    {
        $showtime = Showtime::find((int) $id);
        if (!$showtime) {
            return Response::json(['status' => 'error', 'message' => 'Jadwal tidak ditemukan.'], 404);
        }

        $showtime->delete();

        return Response::json([
            'status'  => 'success',
            'message' => 'Jadwal tayang berhasil dihapus.',
        ]);
    }
}
