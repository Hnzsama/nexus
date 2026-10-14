<?php

declare(strict_types=1);

namespace Core\View;

use RuntimeException;
use Throwable;

/**
 * Class ViewEngine
 * 
 * Native PHP View Engine berkinerja tinggi dengan arsitektur Layout & Block/Section.
 * Memisahkan logika tampilan dengan elegan tanpa menggunakan syntax parser pihak ketiga,
 * memberikan kecepatan rendering 100% native PHP.
 */
class ViewEngine
{
    /**
     * Path direktori root template view.
     */
    protected string $viewsPath;

    /**
     * Nama file layout yang aktif (jika ada).
     */
    protected ?string $layout = null;

    /**
     * Data khusus untuk layout.
     *
     * @var array<string, mixed>
     */
    protected array $layoutData = [];

    /**
     * Konten blok/section yang telah ditangkap (misal: 'content', 'title', 'scripts').
     *
     * @var array<string, string>
     */
    protected array $blocks = [];

    /**
     * Stack penampung nama blok yang sedang dibuka.
     *
     * @var array<int, string>
     */
    protected array $blockStack = [];

    /**
     * Data variabel yang di-passing ke view.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = rtrim($viewsPath ?? (function_exists('base_path') ? base_path('resources/views') : dirname(__DIR__, 2) . '/resources/views'), '/\\');
    }

    /**
     * Tentukan layout utama untuk view ini (dipanggil di dalam file view).
     *
     * @param string $layout Path relatif layout (misal: 'layouts/main')
     * @param array<string, mixed> $data Data tambahan untuk layout
     */
    public function layout(string $layout, array $data = []): void
    {
        $this->layout = $layout;
        $this->layoutData = array_merge($this->layoutData, $data);
    }

    /**
     * Buka penangkapan blok/section konten, atau isi konten langsung jika parameter $content diberikan.
     *
     * @param string $name Nama blok (misal: 'content', 'title')
     * @param string|null $content Konten langsung (opsional)
     */
    public function block(string $name, ?string $content = null): void
    {
        if ($content !== null) {
            $this->blocks[$name] = $content;
            return;
        }

        $this->blockStack[] = $name;
        ob_start();
    }

    /**
     * Tutup penangkapan blok konten terakhir yang dibuka.
     */
    public function endBlock(): void
    {
        if (empty($this->blockStack)) {
            throw new RuntimeException("Memanggil endBlock() tanpa ada blok yang sedang aktif.");
        }

        $name = array_pop($this->blockStack);
        $this->blocks[$name] = (string) ob_get_clean();
    }

    /**
     * Render konten blok tertentu ke dalam layout.
     *
     * @param string $name Nama blok yang ingin ditampilkan
     * @param string $default Nilai fallback jika blok tidak diisi
     * @return string
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->blocks[$name] ?? $default;
    }

    /**
     * Cek apakah sebuah blok telah didefinisikan dan memiliki isi.
     */
    public function hasBlock(string $name): bool
    {
        return isset($this->blocks[$name]) && trim($this->blocks[$name]) !== '';
    }

    /**
     * Render sub-view/partial di dalam view saat ini.
     *
     * @param string $partial Path relatif partial (misal: 'partials/header')
     * @param array<string, mixed> $data Data yang di-passing ke partial
     * @return string
     */
    public function include(string $partial, array $data = []): string
    {
        $file = $this->resolveViewPath($partial);
        $mergedData = array_merge($this->data, $data);

        return $this->evaluateTemplate($file, $mergedData);
    }

    /**
     * Helper escaping HTML untuk mencegah serangan XSS.
     *
     * @param mixed $value
     * @return string
     */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Render file view beserta layout-nya jika dideklarasikan.
     *
     * @param string $view Path relatif view (misal: 'pages/welcome')
     * @param array<string, mixed> $data
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        $this->data = $data;
        $viewFile = $this->resolveViewPath($view);

        // Evaluasi view utama
        $viewContent = $this->evaluateTemplate($viewFile, $this->data);

        // Jika view utama memiliki layout pembungkus
        if ($this->layout !== null) {
            // Jika view tidak secara eksplisit membungkus konten dalam block('content'),
            // jadikan seluruh output utama sebagai block 'content' default
            if (!isset($this->blocks['content'])) {
                $this->blocks['content'] = $viewContent;
            }

            $layoutFile = $this->resolveViewPath($this->layout);
            $layoutMergedData = array_merge($this->data, $this->layoutData);

            // Reset layout aktif sebelum merender layout agar tidak terjadi rekursi
            $this->layout = null;

            return $this->evaluateTemplate($layoutFile, $layoutMergedData);
        }

        return $viewContent;
    }

    /**
     * Evaluasi file PHP template dengan isolasi variabel.
     *
     * @param string $filePath
     * @param array<string, mixed> $data
     * @return string
     */
    protected function evaluateTemplate(string $filePath, array $data): string
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File view tidak ditemukan pada [{$filePath}].");
        }

        $renderClosure = function () use ($filePath, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();

            try {
                include $filePath;
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            }

            return (string) ob_get_clean();
        };

        // Ikat closure ke instance ViewEngine ini agar $this->layout(), $this->block(), dll. dapat dipanggil langsung di template
        return $renderClosure->call($this);
    }

    /**
     * Resolusi path absolut file view berdasar nama relatif.
     */
    protected function resolveViewPath(string $view): string
    {
        $view = str_replace('.', '/', $view);
        $view = ltrim($view, '/');

        if (!str_ends_with($view, '.php')) {
            $view .= '.php';
        }

        return $this->viewsPath . '/' . $view;
    }
}
