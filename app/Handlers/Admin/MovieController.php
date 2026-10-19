<?php

declare(strict_types=1);

namespace App\Handlers\Admin;

use App\Handlers\Controller;
use App\Models\Movie;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Class MovieController (Admin)
 * 
 * Pengendali CRUD data master film untuk Administrator.
 */
class MovieController extends Controller
{
    #[OA\Get(
        path: '/api/admin/movies',
        summary: '[Admin] Daftar Seluruh Film',
        description: 'Mengambil seluruh katalog film (NOW_PLAYING, UPCOMING, ARCHIVED) untuk manajemen backoffice.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar film berhasil diambil.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 403, description: 'Forbidden.')
        ]
    )]
    public function index(Request $request): Response
    {
        $movies = Movie::orderBy('id', 'DESC')->get();
        return Response::json([
            'status' => 'success',
            'data'   => $movies,
        ]);
    }

    #[OA\Post(
        path: '/api/admin/movies',
        summary: '[Admin] Tambah Film Baru',
        description: 'Menambahkan judul film baru ke sistem bioskop.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'duration_minutes'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Oppenheimer'),
                    new OA\Property(property: 'duration_minutes', type: 'integer', example: 180),
                    new OA\Property(property: 'age_rating', type: 'string', example: '17+'),
                    new OA\Property(property: 'synopsis', type: 'string', example: 'Kisah fisikawan J. Robert Oppenheimer...'),
                    new OA\Property(property: 'poster_url', type: 'string', example: 'https://images.unsplash.com/...'),
                    new OA\Property(property: 'status', type: 'string', example: 'NOW_PLAYING'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Film berhasil ditambahkan.'),
            new OA\Response(response: 422, description: 'Validasi input gagal.')
        ]
    )]
    public function store(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));
        $duration = (int) $request->input('duration_minutes', 0);

        if (empty($title) || $duration <= 0) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Judul film dan durasi menit yang valid wajib diisi.',
            ], 422);
        }

        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

        $movie = new Movie();
        $movie->title = $title;
        $movie->slug = $slug . '-' . random_int(100, 999);
        $movie->duration_minutes = $duration;
        $movie->age_rating = (string) $request->input('age_rating', '13+');
        $movie->synopsis = (string) $request->input('synopsis', '');
        $movie->poster_url = (string) $request->input('poster_url', '');
        $movie->trailer_url = (string) $request->input('trailer_url', '');
        $movie->status = (string) $request->input('status', 'NOW_PLAYING');
        $movie->save();

        return Response::json([
            'status'  => 'success',
            'message' => 'Film baru berhasil ditambahkan.',
            'data'    => $movie,
        ], 201);
    }

    #[OA\Put(
        path: '/api/admin/movies/{id}',
        summary: '[Admin] Perbarui Data Film',
        description: 'Memperbarui data katalog film.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Film berhasil diperbarui.'),
            new OA\Response(response: 404, description: 'Film tidak ditemukan.')
        ]
    )]
    public function update(Request $request, string $id): Response
    {
        $movie = Movie::find((int) $id);
        if (!$movie) {
            return Response::json(['status' => 'error', 'message' => 'Film tidak ditemukan.'], 404);
        }

        if ($request->has('title')) $movie->title = (string) $request->input('title');
        if ($request->has('duration_minutes')) $movie->duration_minutes = (int) $request->input('duration_minutes');
        if ($request->has('age_rating')) $movie->age_rating = (string) $request->input('age_rating');
        if ($request->has('synopsis')) $movie->synopsis = (string) $request->input('synopsis');
        if ($request->has('poster_url')) $movie->poster_url = (string) $request->input('poster_url');
        if ($request->has('status')) $movie->status = (string) $request->input('status');
        $movie->save();

        return Response::json([
            'status'  => 'success',
            'message' => 'Film berhasil diperbarui.',
            'data'    => $movie,
        ]);
    }

    #[OA\Delete(
        path: '/api/admin/movies/{id}',
        summary: '[Admin] Hapus Film',
        description: 'Menghapus film dari katalog.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Film berhasil dihapus.'),
            new OA\Response(response: 404, description: 'Film tidak ditemukan.')
        ]
    )]
    public function destroy(Request $request, string $id): Response
    {
        $movie = Movie::find((int) $id);
        if (!$movie) {
            return Response::json(['status' => 'error', 'message' => 'Film tidak ditemukan.'], 404);
        }

        $movie->delete();

        return Response::json([
            'status'  => 'success',
            'message' => 'Film berhasil dihapus dari sistem.',
        ]);
    }
}
