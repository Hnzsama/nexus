<?php

declare(strict_types=1);

namespace App\Handlers\Admin;

use App\Handlers\Controller;
use App\Models\Cinema;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Class CinemaController (Admin)
 * 
 * Pengendali CRUD data master bioskop untuk Administrator.
 */
class CinemaController extends Controller
{
    #[OA\Get(
        path: '/api/admin/cinemas',
        summary: '[Admin] Daftar Seluruh Bioskop',
        description: 'Mengambil seluruh data bioskop (aktif maupun non-aktif) untuk keperluan manajemen backoffice.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar bioskop berhasil diambil.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 403, description: 'Forbidden (Hanya untuk Admin).')
        ]
    )]
    public function index(Request $request): Response
    {
        $cinemas = Cinema::all();
        return Response::json([
            'status' => 'success',
            'data'   => $cinemas,
        ]);
    }

    #[OA\Post(
        path: '/api/admin/cinemas',
        summary: '[Admin] Tambah Bioskop Baru',
        description: 'Mendaftarkan cabang bioskop baru ke sistem.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name', 'city', 'address'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'CGV-CP'),
                    new OA\Property(property: 'name', type: 'string', example: 'CGV Central Park'),
                    new OA\Property(property: 'city', type: 'string', example: 'Jakarta Barat'),
                    new OA\Property(property: 'address', type: 'string', example: 'Jl. Letjen S. Parman No.28'),
                    new OA\Property(property: 'is_active', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Bioskop berhasil ditambahkan.'),
            new OA\Response(response: 422, description: 'Validasi input gagal.')
        ]
    )]
    public function store(Request $request): Response
    {
        $code = trim((string) $request->input('code', ''));
        $name = trim((string) $request->input('name', ''));
        $city = trim((string) $request->input('city', ''));
        $address = trim((string) $request->input('address', ''));

        if (empty($code) || empty($name) || empty($city) || empty($address)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Kode, nama, kota, dan alamat bioskop wajib diisi.',
            ], 422);
        }

        $cinema = new Cinema();
        $cinema->code = strtoupper($code);
        $cinema->name = $name;
        $cinema->city = $city;
        $cinema->address = $address;
        $cinema->is_active = (int) $request->input('is_active', 1);
        $cinema->save();

        return Response::json([
            'status'  => 'success',
            'message' => 'Bioskop berhasil didaftarkan.',
            'data'    => $cinema,
        ], 201);
    }

    #[OA\Put(
        path: '/api/admin/cinemas/{id}',
        summary: '[Admin] Perbarui Data Bioskop',
        description: 'Memperbarui informasi cabang bioskop.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Bioskop berhasil diperbarui.'),
            new OA\Response(response: 404, description: 'Bioskop tidak ditemukan.')
        ]
    )]
    public function update(Request $request, string $id): Response
    {
        $cinema = Cinema::find((int) $id);
        if (!$cinema) {
            return Response::json(['status' => 'error', 'message' => 'Bioskop tidak ditemukan.'], 404);
        }

        if ($request->has('name')) $cinema->name = (string) $request->input('name');
        if ($request->has('city')) $cinema->city = (string) $request->input('city');
        if ($request->has('address')) $cinema->address = (string) $request->input('address');
        if ($request->has('is_active')) $cinema->is_active = (int) $request->input('is_active');
        $cinema->save();

        return Response::json([
            'status'  => 'success',
            'message' => 'Data bioskop berhasil diperbarui.',
            'data'    => $cinema,
        ]);
    }

    #[OA\Delete(
        path: '/api/admin/cinemas/{id}',
        summary: '[Admin] Hapus Bioskop',
        description: 'Menghapus cabang bioskop dari sistem.',
        tags: ['Admin & Backoffice'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Bioskop berhasil dihapus.'),
            new OA\Response(response: 404, description: 'Bioskop tidak ditemukan.')
        ]
    )]
    public function destroy(Request $request, string $id): Response
    {
        $cinema = Cinema::find((int) $id);
        if (!$cinema) {
            return Response::json(['status' => 'error', 'message' => 'Bioskop tidak ditemukan.'], 404);
        }

        $cinema->delete();

        return Response::json([
            'status'  => 'success',
            'message' => 'Bioskop berhasil dihapus dari sistem.',
        ]);
    }
}
