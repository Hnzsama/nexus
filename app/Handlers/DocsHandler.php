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
     * Endpoint API JSON untuk mendapatkan status checklist dan progress jobdesk per anggota tim.
     */
    public function apiGetJobdeskProgress(Request $request): Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $data = $this->calculateJobdeskProgress();

        return Response::json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Endpoint API JSON untuk toggle checklist task jobdesk secara interaktif.
     */
    public function apiToggleJobdeskTask(Request $request): Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $taskId = (string) ($request->input('taskId') ?? '');
        $completed = (bool) $request->input('completed', false);

        if ($taskId === '') {
            return Response::json([
                'status' => 'error',
                'message' => 'Task ID tidak valid',
            ], 422);
        }

        $filePath = storage_path('app/jobdesk_progress.json');
        $stored = $this->readProgressFile($filePath);

        $stored['tasks'][$taskId] = $completed;
        $stored['updated_at'] = date('c');

        $this->writeProgressFile($filePath, $stored);

        $data = $this->calculateJobdeskProgress();

        return Response::json([
            'status' => 'success',
            'message' => 'Progress checklist berhasil disimpan',
            'data' => $data,
        ]);
    }

    /**
     * Endpoint API JSON untuk mereset seluruh progress checklist ke nol.
     */
    public function apiResetJobdeskProgress(Request $request): Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filePath = storage_path('app/jobdesk_progress.json');
        $stored = [
            'updated_at' => date('c'),
            'tasks' => [],
        ];

        $this->writeProgressFile($filePath, $stored);

        $data = $this->calculateJobdeskProgress();

        return Response::json([
            'status' => 'success',
            'message' => 'Seluruh progress checklist berhasil di-reset',
            'data' => $data,
        ]);
    }

    /**
     * Parse file docs/08-jobdesk-team.md dan hitung statistik progress per anggota tim.
     *
     * @return array<string, mixed>
     */
    private function calculateJobdeskProgress(): array
    {
        $filePath = storage_path('app/jobdesk_progress.json');
        $stored = $this->readProgressFile($filePath);
        $savedTasks = (array) ($stored['tasks'] ?? []);

        $docPath = base_path('docs/08-jobdesk-team.md');
        $content = file_exists($docPath) ? (string) file_get_contents($docPath) : '';
        $lines = explode("\n", $content);

        $currentMember = 'general';
        $tasks = [];
        $memberMeta = [
            'aku' => [
                'name' => 'Aku',
                'role' => 'Lead Backend Architect & Payment Engineer',
                'badge' => 'badge-primary',
                'progressClass' => 'progress-primary',
                'total' => 0,
                'completed' => 0,
                'percent' => 0,
            ],
            'ais' => [
                'name' => 'Ais',
                'role' => 'Lead UI/UX & Frontend Design Specialist',
                'badge' => 'badge-success',
                'progressClass' => 'progress-success',
                'total' => 0,
                'completed' => 0,
                'percent' => 0,
            ],
            'adrian' => [
                'name' => 'Adrian',
                'role' => 'Frontend Client Logic & Runtime Engineer',
                'badge' => 'badge-info',
                'progressClass' => 'progress-info',
                'total' => 0,
                'completed' => 0,
                'percent' => 0,
            ],
            'pileyo' => [
                'name' => 'Pileyo',
                'role' => 'Quality Assurance, Security & DevOps',
                'badge' => 'badge-secondary',
                'progressClass' => 'progress-secondary',
                'total' => 0,
                'completed' => 0,
                'percent' => 0,
            ],
        ];

        foreach ($lines as $line) {
            if (preg_match('/##\s+\d+\.\s+.*?AKU/i', $line)) {
                $currentMember = 'aku';
            } elseif (preg_match('/##\s+\d+\.\s+.*?AIS/i', $line)) {
                $currentMember = 'ais';
            } elseif (preg_match('/##\s+\d+\.\s+.*?ADRIAN/i', $line)) {
                $currentMember = 'adrian';
            } elseif (preg_match('/##\s+\d+\.\s+.*?PILEYO/i', $line)) {
                $currentMember = 'pileyo';
            } elseif (preg_match('/##\s+.*?TIMELINE/i', $line)) {
                $currentMember = 'timeline';
            }

            if (isset($memberMeta[$currentMember]) && preg_match('/^\s*-\s*\[([ xX])\]\s*(.+)$/', $line, $matches)) {
                $defaultChecked = strtolower($matches[1]) === 'x';
                $title = trim($matches[2]);
                $id = 'task_' . substr(sha1($currentMember . '::' . $title), 0, 10);

                // Status tersimpan di JSON mengambil prioritas, jika belum ada gunakan default markdown
                $isCompleted = isset($savedTasks[$id]) ? (bool) $savedTasks[$id] : $defaultChecked;

                $tasks[] = [
                    'id' => $id,
                    'member' => $currentMember,
                    'title' => $title,
                    'completed' => $isCompleted,
                ];

                $memberMeta[$currentMember]['total']++;
                if ($isCompleted) {
                    $memberMeta[$currentMember]['completed']++;
                }
            }
        }

        $totalTasks = count($tasks);
        $totalCompleted = 0;

        foreach ($memberMeta as $key => &$m) {
            $m['percent'] = $m['total'] > 0 ? (int) round(($m['completed'] / $m['total']) * 100) : 0;
            $totalCompleted += $m['completed'];
        }
        unset($m);

        $overallPercent = $totalTasks > 0 ? (int) round(($totalCompleted / $totalTasks) * 100) : 0;

        return [
            'updated_at' => $stored['updated_at'] ?? date('c'),
            'storage_file' => 'storage/app/jobdesk_progress.json',
            'stats' => [
                'total' => $totalTasks,
                'completed' => $totalCompleted,
                'percent' => $overallPercent,
                'members' => $memberMeta,
            ],
            'tasks' => $tasks,
        ];
    }

    /**
     * Baca berkas JSON progress dari storage/app.
     *
     * @return array<string, mixed>
     */
    private function readProgressFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'updated_at' => date('c'),
                'tasks' => [],
            ];
        }

        $raw = (string) file_get_contents($filePath);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['updated_at' => date('c'), 'tasks' => []];
    }

    /**
     * Tulis berkas JSON progress ke storage/app dengan atomic write.
     */
    private function writeProgressFile(string $filePath, array $data): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        file_put_contents(
            $filePath,
            (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
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
