<?php
/** @var array<string, array<string, mixed>> $documents */
/** @var string $activeSlug */
/** @var array<string, mixed>|null $activeDoc */
$documents = $documents ?? [];
$activeSlug = $activeSlug ?? 'readme';
$activeDoc = $activeDoc ?? ($documents[$activeSlug] ?? null);

$this->layout('layouts/main', ['title' => 'Dokumentasi Sistem & Arsitektur Tiket Bioskop']);
?>

<?php $this->section('head') ?>
<!-- Highlight.js for Beautiful Syntax Highlighting (Dark Theme) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github-dark-dimmed.min.css">
<!-- Marked.js for High-Performance Markdown Parsing -->
<script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>
<!-- Mermaid.js for Interactive Sequence & State Diagrams -->
<script src="https://cdn.jsdelivr.net/npm/mermaid@10.9.1/dist/mermaid.min.js"></script>
<!-- Highlight.js Script -->
<script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>

<style>
    /* Styling khusus konten markdown agar serasi dengan DaisyUI Dark */
    .markdown-body {
        color: #e2e8f0;
        line-height: 1.75;
    }
    .markdown-body h1 {
        font-size: 1.875rem;
        font-weight: 800;
        color: #f8fafc;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0.5rem;
        letter-spacing: -0.025em;
    }
    .markdown-body h2 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #f1f5f9;
        margin-top: 2rem;
        margin-bottom: 0.85rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        padding-bottom: 0.35rem;
    }
    .markdown-body h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #e2e8f0;
        margin-top: 1.5rem;
        margin-bottom: 0.65rem;
    }
    .markdown-body h4 {
        font-size: 1.05rem;
        font-weight: 600;
        color: #cbd5e1;
        margin-top: 1.25rem;
        margin-bottom: 0.5rem;
    }
    .markdown-body p {
        margin-bottom: 1.2rem;
        color: #cbd5e1;
    }
    .markdown-body ul, .markdown-body ol {
        margin-bottom: 1.25rem;
        padding-left: 1.75rem;
    }
    .markdown-body ul {
        list-style-type: disc;
    }
    .markdown-body ol {
        list-style-type: decimal;
    }
    .markdown-body li {
        margin-bottom: 0.4rem;
        color: #cbd5e1;
    }
    .markdown-body code:not(pre code) {
        background-color: rgba(99, 102, 241, 0.15);
        color: #a5b4fc;
        padding: 0.2rem 0.45rem;
        border-radius: 0.375rem;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.85em;
        border: 1px solid rgba(99, 102, 241, 0.25);
    }
    .markdown-body pre {
        background-color: #0d1117 !important;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1.25rem;
        overflow-x: auto;
        margin-top: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    }
    .markdown-body pre code {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.875rem;
        background: transparent !important;
        padding: 0;
    }
    /* Mermaid Container Card */
    .mermaid-container {
        display: flex;
        justify-content: center;
        background-color: #0f172a;
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 0.85rem;
        padding: 1.5rem;
        margin: 1.75rem 0;
        overflow-x: auto;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4);
    }
    .mermaid-container svg {
        max-width: 100%;
        height: auto;
    }
    /* Tables */
    .markdown-body table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .markdown-body th {
        background-color: rgba(30, 41, 59, 0.8);
        color: #f8fafc;
        font-weight: 700;
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 2px solid rgba(255, 255, 255, 0.15);
    }
    .markdown-body td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #cbd5e1;
    }
    .markdown-body tr:hover td {
        background-color: rgba(255, 255, 255, 0.03);
    }
    /* Blockquotes */
    .markdown-body blockquote {
        border-left: 4px solid #6366f1;
        background-color: rgba(99, 102, 241, 0.08);
        padding: 0.75rem 1.25rem;
        border-radius: 0 0.5rem 0.5rem 0;
        margin: 1.5rem 0;
        color: #e2e8f0;
    }
</style>
<?php $this->endSection() ?>

<!-- Container Header -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-base-content/10 pb-5">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="badge badge-primary badge-sm font-mono tracking-wider uppercase font-semibold">Technical Docs</span>
            <span class="badge badge-neutral badge-sm font-mono">Cinema Booking Engine</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-base-content">
            Spesifikasi Arsitektur & Rekayasa Konkurensi
        </h1>
        <p class="text-xs sm:text-sm text-base-content/60 mt-1">
            Panduan teknis lengkap integrasi Midtrans Snap, Pessimistic Locking MySQL, dan Analisis Audit Sistem.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <button id="btn-copy-raw" class="btn btn-sm btn-outline btn-neutral gap-2 font-medium">
            <i data-lucide="copy" class="w-4 h-4"></i>
            <span>Salin Markdown</span>
        </button>
        <a href="<?= route('home') ?>" class="btn btn-sm btn-ghost gap-2 font-medium">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke App</span>
        </a>
    </div>
</div>

<!-- Layout Dua Kolom: Sidebar SPA + Content Area -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    
    <!-- Left Sidebar: Chapter Navigation -->
    <aside class="lg:col-span-4 xl:col-span-3 card bg-base-200/50 backdrop-blur-md border border-base-content/10 shadow-xl sticky top-20">
        <div class="card-body p-4">
            <!-- Search Input -->
            <div class="relative mb-3">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40"></i>
                <input 
                    type="text" 
                    id="doc-search" 
                    placeholder="Cari topik atau bab..." 
                    class="input input-sm input-bordered w-full pl-9 bg-base-100 text-xs focus:input-primary"
                />
            </div>

            <div class="text-[11px] font-bold uppercase tracking-wider text-base-content/40 px-2 mb-1">
                Daftar Bab & Modul
            </div>

            <!-- Chapter Menu Items -->
            <ul class="menu menu-sm p-0 gap-1" id="doc-menu">
                <?php foreach ($documents as $slug => $doc): ?>
                    <?php $isActive = ($slug === $activeSlug); ?>
                    <li>
                        <a 
                            href="<?= route('docs.show', ['slug' => $slug]) ?>" 
                            data-slug="<?= $slug ?>" 
                            class="doc-nav-item flex items-start gap-3 p-2.5 rounded-xl transition-all <?= $isActive ? 'bg-primary text-primary-content font-bold shadow-md shadow-primary/20 active-doc' : 'text-base-content/75 hover:bg-base-content/10 hover:text-base-content font-medium' ?>"
                        >
                            <div class="mt-0.5 p-1 rounded-lg <?= $isActive ? 'bg-primary-content/20 text-primary-content' : 'bg-base-300 text-primary' ?>">
                                <i data-lucide="<?= $doc['icon'] ?>" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold truncate leading-tight doc-title-text"><?= htmlspecialchars($doc['title']) ?></div>
                                <div class="text-[10px] opacity-70 truncate mt-0.5 leading-tight"><?= htmlspecialchars($doc['subtitle']) ?></div>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Info Card -->
            <div class="mt-4 pt-3 border-t border-base-content/10 flex items-center justify-between text-[11px] text-base-content/50">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>8 Dokumen Aktif</span>
                </span>
                <span class="font-mono text-[10px]">SPA Renderer</span>
            </div>
        </div>
    </aside>

    <!-- Right Main Area: Content View -->
    <main class="lg:col-span-8 xl:col-span-9 card bg-base-200/40 backdrop-blur-md border border-base-content/10 shadow-2xl overflow-hidden min-h-[600px]">
        <!-- Content Header Bar -->
        <div class="bg-base-300/40 border-b border-base-content/10 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary/20 text-primary flex items-center justify-center font-bold" id="active-icon-badge">
                    <i data-lucide="<?= $activeDoc['icon'] ?? 'file-text' ?>" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-base-content leading-tight" id="active-title-display">
                        <?= htmlspecialchars($activeDoc['title'] ?? 'Dokumentasi') ?>
                    </h2>
                    <p class="text-[11px] text-base-content/50 font-mono" id="active-filename-display">
                        docs/<?= htmlspecialchars($activeDoc['filename'] ?? '') ?>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="badge badge-neutral badge-sm gap-1 text-[11px] font-mono">
                    <i data-lucide="clock" class="w-3 h-3"></i>
                    <span id="reading-time">~5 min read</span>
                </div>
                <div class="badge badge-outline badge-primary badge-sm text-[11px] font-mono">
                    Mermaid Supported
                </div>
            </div>
        </div>

        <!-- Rendered Markdown Body -->
        <div class="card-body p-6 sm:p-10">
            <!-- Loading Indicator (SPA transition) -->
            <div id="content-loading" class="hidden py-16 flex flex-col items-center justify-center gap-3">
                <span class="loading loading-spinner loading-lg text-primary"></span>
                <p class="text-xs text-base-content/60 font-mono">Rendering Markdown & Diagram Mermaid...</p>
            </div>

            <!-- Content Slot -->
            <article id="markdown-container" class="markdown-body">
                <!-- Di-inject secara otomatis oleh JS -->
            </article>
        </div>
    </main>

</div>

<!-- Data Dictionary Embed (Menyimpan seluruh teks markdown untuk SPA instan 0ms) -->
<script id="docs-data" type="application/json">
<?= json_encode($documents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<?php $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Ambil data dokumen yang di-embed dari backend
    const rawDataEl = document.getElementById('docs-data');
    const documents = JSON.parse(rawDataEl.textContent || '{}');
    let currentSlug = '<?= $activeSlug ?>';

    const container = document.getElementById('markdown-container');
    const loadingEl = document.getElementById('content-loading');
    const titleDisplay = document.getElementById('active-title-display');
    const filenameDisplay = document.getElementById('active-filename-display');
    const readingTimeDisplay = document.getElementById('reading-time');
    const searchInput = document.getElementById('doc-search');
    const btnCopyRaw = document.getElementById('btn-copy-raw');

    // 2. Inisialisasi Mermaid.js untuk tema dark
    if (window.mermaid) {
        mermaid.initialize({
            startOnLoad: false,
            theme: 'dark',
            securityLevel: 'loose',
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            themeVariables: {
                darkMode: true,
                background: '#0f172a',
                primaryColor: '#6366f1',
                primaryTextColor: '#f8fafc',
                primaryBorderColor: '#818cf8',
                lineColor: '#38bdf8',
                secondaryColor: '#ec4899',
                tertiaryColor: '#1e293b'
            }
        });
    }

    // 3. Konfigurasi Kustom Marked.js Renderer untuk Mermaid & Syntax Highlighting
    const renderer = new marked.Renderer();
    const defaultCodeRenderer = renderer.code.bind(renderer);

    renderer.code = function(code, language) {
        if (language === 'mermaid') {
            return `<div class="mermaid-container"><pre class="mermaid">${code}</pre></div>`;
        }

        // Gunakan Highlight.js jika bahasa dikenali
        let highlighted = code;
        if (window.hljs && language && hljs.getLanguage(language)) {
            try {
                highlighted = hljs.highlight(code, { language }).value;
            } catch (err) {
                console.warn('Highlight.js error:', err);
            }
        } else if (window.hljs) {
            highlighted = hljs.highlightAuto(code).value;
        }

        return `<pre><code class="hljs ${language ? 'language-' + language : ''}">${highlighted}</code></pre>`;
    };

    marked.use({
        renderer: renderer,
        gfm: true,
        breaks: false
    });

    // 4. Fungsi Render Dokumen secara Asinkron (SPA Mode)
    async function renderDocument(slug) {
        const doc = documents[slug];
        if (!doc) {
            container.innerHTML = `<div class="alert alert-error">Dokumen [${slug}] tidak ditemukan.</div>`;
            return;
        }

        currentSlug = slug;

        // Tampilkan loading sebentar jika konten sangat besar
        loadingEl.classList.remove('hidden');
        container.style.opacity = '0.3';

        // Update header UI
        titleDisplay.textContent = doc.title;
        filenameDisplay.textContent = `docs/${doc.filename}`;

        // Hitung estimasi waktu baca (rata-rata 200 kata/menit)
        const wordCount = doc.content.split(/\s+/).length;
        const minutes = Math.ceil(wordCount / 200);
        readingTimeDisplay.textContent = `~${minutes} min read (${wordCount.toLocaleString()} kata)`;

        // Parse Markdown ke HTML
        const html = marked.parse(doc.content);
        container.innerHTML = html;

        // Render diagram Mermaid jika ada
        if (window.mermaid) {
            const mermaidEls = container.querySelectorAll('.mermaid');
            if (mermaidEls.length > 0) {
                try {
                    await mermaid.run({
                        nodes: mermaidEls
                    });
                } catch (e) {
                    console.error('Gagal me-render diagram Mermaid:', e);
                }
            }
        }

        // Re-inisialisasi ikon Lucide di konten
        if (window.lucide) {
            lucide.createIcons();
        }

        loadingEl.classList.add('hidden');
        container.style.opacity = '1';

        // Update active class di sidebar menu
        document.querySelectorAll('.doc-nav-item').forEach(el => {
            const elSlug = el.getAttribute('data-slug');
            if (elSlug === slug) {
                el.className = 'doc-nav-item flex items-start gap-3 p-2.5 rounded-xl transition-all bg-primary text-primary-content font-bold shadow-md shadow-primary/20 active-doc';
                const iconBox = el.querySelector('div:first-child');
                if (iconBox) iconBox.className = 'mt-0.5 p-1 rounded-lg bg-primary-content/20 text-primary-content';
            } else {
                el.className = 'doc-nav-item flex items-start gap-3 p-2.5 rounded-xl transition-all text-base-content/75 hover:bg-base-content/10 hover:text-base-content font-medium';
                const iconBox = el.querySelector('div:first-child');
                if (iconBox) iconBox.className = 'mt-0.5 p-1 rounded-lg bg-base-300 text-primary';
            }
        });
    }

    // 5. Tangani Klik Navigasi Sidebar (Interseptor SPA)
    document.getElementById('doc-menu').addEventListener('click', (e) => {
        const item = e.target.closest('.doc-nav-item');
        if (!item) return;

        e.preventDefault();
        const slug = item.getAttribute('data-slug');
        if (slug === currentSlug) return;

        // Update URL di browser tanpa reload (SPA History API)
        history.pushState({ slug }, '', `/docs/${slug}`);
        renderDocument(slug);

        // Smooth scroll ke atas konten pada layar mobile
        window.scrollTo({ top: 100, behavior: 'smooth' });
    });

    // 6. Dukungan Tombol Back/Forward Browser (popstate)
    window.addEventListener('popstate', (e) => {
        const slug = e.state?.slug || location.pathname.split('/').pop() || 'readme';
        if (documents[slug]) {
            renderDocument(slug);
        }
    });

    // 7. Salin Raw Markdown ke Clipboard
    btnCopyRaw.addEventListener('click', () => {
        const doc = documents[currentSlug];
        if (!doc) return;

        navigator.clipboard.writeText(doc.content).then(() => {
            const originalText = btnCopyRaw.innerHTML;
            btnCopyRaw.innerHTML = `<i data-lucide="check" class="w-4 h-4 text-success"></i><span class="text-success">Tersalin!</span>`;
            if (window.lucide) lucide.createIcons();
            setTimeout(() => {
                btnCopyRaw.innerHTML = originalText;
                if (window.lucide) lucide.createIcons();
            }, 2000);
        });
    });

    // 8. Filter Pencarian Cepat pada Sidebar
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase().trim();
        document.querySelectorAll('#doc-menu li').forEach(li => {
            const text = li.textContent.toLowerCase();
            if (text.includes(term)) {
                li.style.display = '';
            } else {
                li.style.display = 'none';
            }
        });
    });

    // 9. Jalankan Render Awal
    renderDocument(currentSlug);
});
</script>
<?php $this->endSection() ?>
