<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\Movie;
use App\Models\Showtime;
use App\Services\BookingService;
use Core\Database\Connection;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;
use PDO;
use Throwable;

/**
 * Class CinemaController
 * 
 * Handler untuk katalog film, jadwal bioskop, dan denah kursi real-time.
 */
class CinemaController extends Controller
{
    private BookingService $bookingService;

    public function __construct()
    {
        $this->bookingService = new BookingService();
    }

    #[OA\Get(
        path: '/api/movies',
        summary: 'Katalog Film Sedang Tayang',
        description: 'Mengambil daftar film yang sedang tayang (status NOW_PLAYING) lengkap dengan poster, rating usia, dan durasi.',
        tags: ['Cinema & Movies'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar film berhasil diambil.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Avatar: The Way of Water'),
                                    new OA\Property(property: 'slug', type: 'string', example: 'avatar-the-way-of-water'),
                                    new OA\Property(property: 'duration_minutes', type: 'integer', example: 192),
                                    new OA\Property(property: 'age_rating', type: 'string', example: '13+'),
                                    new OA\Property(property: 'poster_url', type: 'string', example: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=600'),
                                    new OA\Property(property: 'status', type: 'string', example: 'NOW_PLAYING'),
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function movies(Request $request): Response
    {
        $movies = Movie::where('status', '=', 'NOW_PLAYING')
            ->orderBy('id', 'DESC')
            ->get();

        return Response::json([
            'status' => 'success',
            'data' => $movies,
        ]);
    }

    #[OA\Get(
        path: '/api/movies/{slug}',
        summary: 'Detail Film & Jadwal Tayang',
        description: 'Mengambil informasi lengkap film beserta slot jadwal tayang (showtimes) di seluruh bioskop & studio.',
        tags: ['Cinema & Movies'],
        parameters: [
            new OA\Parameter(
                name: 'slug',
                in: 'path',
                required: true,
                description: 'Slug unik film',
                schema: new OA\Schema(type: 'string', example: 'avatar-the-way-of-water')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail film dan jadwal tayang.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'movie', type: 'object'),
                        new OA\Property(property: 'showtimes', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Film tidak ditemukan.')
        ]
    )]
    public function movieDetail(Request $request, string $slug): Response
    {
        $movie = Movie::where('slug', '=', $slug)->first();
        if (!$movie) {
            return Response::json([
                'status' => 'error',
                'message' => "Film dengan slug '{$slug}' tidak ditemukan.",
            ], 404);
        }

        $pdo = Connection::getPdo();
        $stStmt = $pdo->prepare("
            SELECT st.id, st.start_time, st.end_time, st.base_price, st.status,
                   s.name as studio_name, s.studio_type, s.capacity,
                   c.name as cinema_name, c.city, c.code as cinema_code
            FROM showtimes st
            JOIN studios s ON s.id = st.studio_id
            JOIN cinemas c ON c.id = s.cinema_id
            WHERE st.movie_id = ? AND st.status = 'ACTIVE'
            ORDER BY st.start_time ASC
        ");
        $stStmt->execute([$movie->id]);
        $showtimes = $stStmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::json([
            'status' => 'success',
            'movie' => $movie,
            'showtimes' => $showtimes,
        ]);
    }

    #[OA\Get(
        path: '/api/showtimes/{id}/seats',
        summary: 'Peta Kursi Real-Time (Visual 4 Status)',
        description: 'Mengambil denah grid kursi bioskop dengan status terkini (AVAILABLE, HELD, BOOKED, SELECTED) dan waktu sisa hold (Lazy Expiry).',
        tags: ['Seats & Real-Time Engine'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID Jadwal Tayang (Showtime ID)',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data denah kursi real-time berhasil diambil.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Jadwal tayang tidak ditemukan.')
        ]
    )]
    public function seats(Request $request, string $id): Response
    {
        $showtimeId = (int) $id;
        $user = $request->user();
        $userId = $user ? (int) ($user->id ?? 0) : 0;

        try {
            $layout = $this->bookingService->getShowtimeSeatLayout($showtimeId, $userId > 0 ? $userId : null);

            return Response::json([
                'status' => 'success',
                'data' => $layout,
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Endpoint untuk menjalankan migrasi 11 tabel & seed data awal bioskop.
     */
    public function seed(Request $request): Response
    {
        try {
            $migrator = new \Core\Database\Migrator();
            $migrated = $migrator->run();

            require_once base_path('database/seeders/CinemaSeeder.php');
            \Database\Seeders\CinemaSeeder::run();

            return Response::json([
                'status' => 'success',
                'message' => 'Migrasi 11 tabel & Cinema Seeder berhasil dijalankan!',
                'migrated_files' => $migrated,
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'status' => 'error',
                'message' => 'Seeder error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
