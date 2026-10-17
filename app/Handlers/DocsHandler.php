<?php

declare(strict_types=1);

namespace App\Handlers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;

final class DocsHandler extends Controller
{
    /**
     * Tampilkan halaman utama dokumentasi SPA.
     */
    public function index(Request $request, ?string $slug = null): Response
    {
        $documents = $this->loadDocuments();

        // Default slug jika null atau tidak ditemukan
        $activeSlug = $slug ?? 'readme';
        if (!isset($documents[$activeSlug])) {
            $activeSlug = array_key_first($documents) ?? 'readme';
        }

        return $this->view('pages/docs/index', [
            'documents' => $documents,
            'activeSlug' => $activeSlug,
            'activeDoc' => $documents[$activeSlug] ?? null,
        ]);
    }

    /**
     * Endpoint API JSON untuk mengambil dokumen spesifik (opsional untuk SPA).
     */
    public function apiGetDoc(Request $request, string $slug): Response
    {
        $documents = $this->loadDocuments();

        if (!isset($documents[$slug])) {
            return Response::json(['message' => 'Dokumen tidak ditemukan'], 404);
        }

        return Response::json([
            'status' => 'success',
            'data' => $documents[$slug],
        ]);
    }

    /**
     * Muat dan parsing metadata seluruh berkas markdown di direktori docs/.
     *
     * @return array<string, array{slug: string, title: string, subtitle: string, icon: string, filename: string, content: string}>
     */
    private function loadDocuments(): array
    {
        $docsDir = base_path('docs');
        if (!is_dir($docsDir)) {
            return [];
        }

        $files = scandir($docsDir) ?: [];
        $documents = [];

        // Pemetaan metadata manual yang elegan untuk urutan dan ikon
        $metaMap = [
            'README.md' => [
                'order' => 0,
                'slug' => 'readme',
                'title' => 'Overview & Sitemap',
                'subtitle' => 'Ringkasan Sistem & Prinsip Utama',
                'icon' => 'book-open',
            ],
            '01-architecture-payment-flow.md' => [
                'order' => 1,
                'slug' => '01-architecture-payment-flow',
                'title' => 'Bab 1: Arsitektur & Payment Flow',
                'subtitle' => 'Alur Bisnis & Sequence Diagram Midtrans',
                'icon' => 'git-branch',
            ],
            '02-database-schema-erd.md' => [
                'order' => 2,
                'slug' => '02-database-schema-erd',
                'title' => 'Bab 2: Skema Database & ERD',
                'subtitle' => '11 Tabel InnoDB, Relasi & Indexing',
                'icon' => 'database',
            ],
            '03-state-machine.md' => [
                'order' => 3,
                'slug' => '03-state-machine',
                'title' => 'Bab 3: State Machine & Status',
                'subtitle' => 'Siklus Kursi & Pemetaan Midtrans',
                'icon' => 'activity',
            ],
            '04-seat-locking-timer.md' => [
                'order' => 4,
                'slug' => '04-seat-locking-timer',
                'title' => 'Bab 4: Seat Locking & Timer',
                'subtitle' => 'Pessimistic Lock & Sinkronisasi Expiry',
                'icon' => 'clock',
            ],
            '05-endpoints-webhook.md' => [
                'order' => 5,
                'slug' => '05-endpoints-webhook',
                'title' => 'Bab 5: Endpoints & Webhook',
                'subtitle' => 'Idempotent Handler & SHA-512 Signature',
                'icon' => 'webhook',
            ],
            '06-frontend-realtime.md' => [
                'order' => 6,
                'slug' => '06-frontend-realtime',
                'title' => 'Bab 6: Frontend & Snap.js',
                'subtitle' => 'Denah Kursi DaisyUI & Modul Countdown',
                'icon' => 'monitor',
            ],
            '07-critical-system-audit.md' => [
                'order' => 7,
                'slug' => '07-critical-system-audit',
                'title' => 'Bab 7: Critical System Audit',
                'subtitle' => 'Edge-Cases Finansial & Solusi Konkret',
                'icon' => 'shield-alert',
            ],
            '08-jobdesk-team.md' => [
                'order' => 8,
                'slug' => '08-jobdesk-team',
                'title' => 'Bab 8: Jobdesk & Roadmap Tim',
                'subtitle' => 'Pembagian 4 Anggota & Checklist Sub-task',
                'icon' => 'clipboard-list',
            ],
        ];

        foreach ($files as $file) {
            if (!str_ends_with($file, '.md')) {
                continue;
            }

            $filePath = $docsDir . '/' . $file;
            $rawContent = (string) file_get_contents($filePath);

            $meta = $metaMap[$file] ?? [
                'order' => 99,
                'slug' => strtolower(pathinfo($file, PATHINFO_FILENAME)),
                'title' => pathinfo($file, PATHINFO_FILENAME),
                'subtitle' => 'Dokumentasi Sistem',
                'icon' => 'file-text',
            ];

            $slug = $meta['slug'];
            $documents[$slug] = [
                'order' => $meta['order'],
                'slug' => $slug,
                'title' => $meta['title'],
                'subtitle' => $meta['subtitle'],
                'icon' => $meta['icon'],
                'filename' => $file,
                'content' => $rawContent,
            ];
        }

        // Urutkan dokumen berdasarkan order
        uasort($documents, fn($a, $b) => $a['order'] <=> $b['order']);

        return $documents;
    }
}
