<?php
/** @var array<string, array<string, mixed>> $documents */
/** @var string $activeSlug */
/** @var array<string, mixed>|null $activeDoc */
$documents = $documents ?? [];
$activeSlug = $activeSlug ?? 'readme';
$activeDoc = $activeDoc ?? ($documents[$activeSlug] ?? null);

$this->layout('layouts/main', ['title' => 'Dokumentasi Sistem & Arsitektur Tiket Bioskop']);
?>

<?php $this->block('head') ?>
<!-- Highlight.js for Syntax Highlighting (Dark Theme) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github-dark-dimmed.min.css">
<!-- Marked.js for High-Performance Markdown Parsing -->
<script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>
<!-- Mermaid.js for Interactive Diagrams -->
<script src="https://cdn.jsdelivr.net/npm/mermaid@10.9.1/dist/mermaid.min.js"></script>
<!-- Highlight.js Core -->
<script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>

<style>
    /* Styling Typography Markdown Berstandar Tinggi */
    .doc-content {
        color: #cbd5e1;
        font-size: 0.95rem;
        line-height: 1.8;
    }
    .doc-content h1 {
        font-size: 1.75rem;
        font-weight: 800;
        color: #f8fafc;
        margin-top: 1.75rem;
        margin-bottom: 1rem;
        letter-spacing: -0.025em;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .doc-content h2 {
        font-size: 1.35rem;
        font-weight: 700;
        color: #f1f5f9;
        margin-top: 2.25rem;
        margin-bottom: 0.85rem;
        letter-spacing: -0.015em;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .doc-content h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #e2e8f0;
        margin-top: 1.6rem;
        margin-bottom: 0.6rem;
    }
    .doc-content h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #94a3b8;
        margin-top: 1.25rem;
        margin-bottom: 0.45rem;
    }
    .doc-content p {
        margin-bottom: 1.15rem;
        color: #cbd5e1;
    }
    .doc-content ul, .doc-content ol {
        margin-bottom: 1.25rem;
        padding-left: 1.5rem;
    }
    .doc-content ul {
        list-style-type: disc;
    }
    .doc-content ol {
        list-style-type: decimal;
    }
    .doc-content li {
        margin-bottom: 0.35rem;
    }
    .doc-content hr {
        border-color: rgba(255, 255, 255, 0.08);
        margin: 2rem 0;
    }
    .doc-content a {
        color: #38bdf8;
        text-decoration: underline;
        text-underline-offset: 3px;
        transition: color 0.15s ease;
    }
    .doc-content a:hover {
        color: #7dd3fc;
    }
    .doc-content code:not(pre code) {
        background-color: rgba(99, 102, 241, 0.12);
        color: #a5b4fc;
        padding: 0.2rem 0.45rem;
        border-radius: 0.375rem;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.84em;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }
    /* Pre & Code Block Styling */
    .code-block-wrapper {
        position: relative;
        background-color: #0b0f19;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        margin: 1.25rem 0 1.75rem;
        overflow: hidden;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
    }
    .code-block-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #0f172a;
        padding: 0.45rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.75rem;
        color: #94a3b8;
    }
    .code-copy-btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #94a3b8;
        font-size: 0.7rem;
        padding: 0.2rem 0.55rem;
        border-radius: 0.35rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: all 0.15s ease;
    }
    .code-copy-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #f1f5f9;
        border-color: rgba(255, 255, 255, 0.3);
    }
    .doc-content pre {
        margin: 0 !important;
        padding: 1.15rem 1.25rem !important;
        background: transparent !important;
        overflow-x: auto;
    }
    .doc-content pre code {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.825rem;
        line-height: 1.65;
        background: transparent !important;
        padding: 0 !important;
    }
    /* Mermaid Card Styling */
    .mermaid-wrapper {
        background: linear-gradient(180deg, #0e1526 0%, #090e1a 100%);
        border: 1px solid rgba(99, 102, 241, 0.25);
        border-radius: 0.85rem;
        margin: 1.75rem 0;
        overflow: hidden;
        box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.6);
    }
    .mermaid-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(15, 23, 42, 0.8);
        border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #a5b4fc;
    }
    .mermaid-chart {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 1.5rem 1rem;
        overflow-x: auto;
    }
    .mermaid-chart svg {
        max-width: 100%;
        height: auto;
    }
    /* Tables */
    .doc-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
        font-size: 0.85rem;
        border-radius: 0.6rem;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background-color: #0b0f19;
    }
    .doc-content th {
        background-color: #111827;
        color: #f8fafc;
        font-weight: 700;
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 2px solid rgba(255, 255, 255, 0.12);
        white-space: nowrap;
    }
    .doc-content td {
        padding: 0.7rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #cbd5e1;
        vertical-align: top;
    }
    .doc-content tr:nth-child(even) td {
        background-color: rgba(255, 255, 255, 0.02);
    }
    .doc-content tr:hover td {
        background-color: rgba(99, 102, 241, 0.06);
    }
    /* Blockquotes / Callouts */
    .doc-content blockquote {
        border-left: 4px solid #6366f1;
        background: rgba(99, 102, 241, 0.08);
        padding: 0.85rem 1.25rem;
        border-radius: 0 0.5rem 0.5rem 0;
        margin: 1.35rem 0;
        color: #e2e8f0;
    }
    /* Active Sidebar Item Style */
    .nav-item-active {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 15px -2px rgba(99, 102, 241, 0.45) !important;
    }
    .nav-item-active .icon-box {
        background-color: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
    }
    .nav-item-active .order-badge {
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        opacity: 1 !important;
    }
</style>
<?php $this->endBlock() ?>

<!-- Subheader Breadcrumbs & Controls -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-base-content/10 pb-4">
    <div>
        <div class="breadcrumbs text-xs text-base-content/60 py-0 mb-1">
            <ul>
                <li><a href="<?= route('home') ?>" class="hover:text-primary">Nexus MVC</a></li>
                <li><span class="text-base-content/40">Dokumentasi</span></li>
                <li id="breadcrumb-current" class="text-primary font-semibold"><?= htmlspecialchars($activeDoc['title'] ?? 'Overview') ?></li>
            </ul>
        </div>
        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white flex items-center gap-2">
            <span>Spesifikasi Teknis Sistem Bioskop</span>
            <span class="badge badge-primary badge-xs font-mono">v1.0-PROD</span>
        </h1>
    </div>

    <div class="flex items-center gap-2">
        <button id="btn-copy-raw" class="btn btn-xs sm:btn-sm btn-outline btn-neutral gap-1.5 font-medium">
            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
            <span>Salin MD</span>
        </button>
        <button id="btn-toggle-sidebar" class="btn btn-xs sm:btn-sm btn-ghost gap-1.5 font-medium lg:hidden">
            <i data-lucide="menu" class="w-4 h-4"></i>
            <span>Daftar Bab</span>
        </button>
    </div>
</div>

<!-- Layout 2 Kolom: Sidebar SPA + Content Reader -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    
    <!-- Sidebar Kiri: Daftar Bab & Navigasi SPA -->
    <aside id="doc-sidebar" class="lg:col-span-4 xl:col-span-3 card bg-base-200/50 backdrop-blur-xl border border-base-content/10 shadow-xl sticky top-20 z-20">
        <div class="card-body p-3.5">
            <!-- Search Box -->
            <div class="relative mb-2.5">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40"></i>
                <input 
                    type="text" 
                    id="doc-search" 
                    placeholder="Cari topik... (Ketik untuk filter)" 
                    class="input input-xs sm:input-sm input-bordered w-full pl-8 bg-base-100 text-xs focus:input-primary"
                />
            </div>

            <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/40 px-2 py-1">
                Daftar Bab & Modul Teknis
            </div>

            <!-- List Menu Bab -->
            <ul class="menu menu-xs p-0 gap-1" id="doc-menu">
                <?php foreach ($documents as $slug => $doc): ?>
                    <?php $isActive = ($slug === $activeSlug); ?>
                    <li>
                        <a 
                            href="<?= route('docs.show', ['slug' => $slug]) ?>" 
                            data-slug="<?= $slug ?>" 
                            class="doc-nav-item flex items-center justify-between p-2 rounded-lg transition-all <?= $isActive ? 'nav-item-active' : 'text-base-content/70 hover:bg-base-content/10 hover:text-base-content' ?>"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="icon-box w-6 h-6 rounded flex items-center justify-center shrink-0 <?= $isActive ? 'bg-white/20 text-white' : 'bg-base-300 text-primary' ?>">
                                    <i data-lucide="<?= $doc['icon'] ?>" class="w-3.5 h-3.5"></i>
                                </div>
                                <div class="truncate">
                                    <div class="text-xs font-semibold truncate leading-tight"><?= htmlspecialchars($doc['title']) ?></div>
                                </div>
                            </div>
                            <span class="order-badge badge badge-neutral badge-xs font-mono text-[9px] shrink-0 opacity-60">
                                <?= str_pad((string)$doc['order'], 2, '0', STR_PAD_LEFT) ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="mt-3 pt-2.5 border-t border-base-content/10 flex items-center justify-between text-[10px] text-base-content/40">
                <span class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>8 Bab Tersedia</span>
                </span>
                <span class="font-mono">Pure SPA</span>
            </div>
        </div>
    </aside>

    <!-- Main Content Reader -->
    <main class="lg:col-span-8 xl:col-span-9 card bg-base-200/40 backdrop-blur-xl border border-base-content/10 shadow-2xl overflow-hidden min-h-[600px]">
        
        <!-- Header Info Bar -->
        <div class="bg-base-300/50 border-b border-base-content/10 px-6 py-3.5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary/20 text-primary flex items-center justify-center font-bold">
                    <i data-lucide="<?= $activeDoc['icon'] ?? 'file-text' ?>" class="w-4 h-4" id="header-icon"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white leading-tight" id="active-title-display">
                        <?= htmlspecialchars($activeDoc['title'] ?? 'Dokumentasi') ?>
                    </h2>
                    <p class="text-[11px] text-base-content/50 font-mono" id="active-filename-display">
                        docs/<?= htmlspecialchars($activeDoc['filename'] ?? '') ?>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="badge badge-neutral badge-xs sm:badge-sm gap-1 text-[11px] font-mono" id="reading-time">
                    ~5 min read
                </span>
                <span class="badge badge-outline badge-primary badge-xs sm:badge-sm text-[11px] font-mono">
                    Mermaid Engine
                </span>
            </div>
        </div>

        <!-- Article Reader Body -->
        <div class="card-body p-6 sm:p-10">
            <article id="markdown-container" class="doc-content">
                <!-- Konten Markdown di-render secara instan di sini -->
            </article>

            <!-- Bottom Chapter Pager (Next & Prev buttons) -->
            <div class="mt-12 pt-6 border-t border-base-content/10 flex flex-col sm:flex-row items-center justify-between gap-4" id="chapter-pager">
                <button id="btn-prev-chapter" class="btn btn-sm btn-outline btn-neutral w-full sm:w-auto gap-2 hidden">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span id="prev-chapter-title">Bab Sebelumnya</span>
                </button>
                <div class="flex-1"></div>
                <button id="btn-next-chapter" class="btn btn-sm btn-primary w-full sm:w-auto gap-2 hidden">
                    <span id="next-chapter-title">Bab Selanjutnya</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </main>

</div>

<!-- Data Dictionary Embed (Seluruh teks markdown untuk render 0ms) -->
<script id="docs-data" type="application/json">
<?= json_encode($documents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<?php $this->block('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Baca data dokumen JSON dari server
    const rawDataEl = document.getElementById('docs-data');
    const documents = JSON.parse(rawDataEl.textContent || '{}');
    const slugList = Object.keys(documents);
    let currentSlug = '<?= $activeSlug ?>';

    const container = document.getElementById('markdown-container');
    const titleDisplay = document.getElementById('active-title-display');
    const filenameDisplay = document.getElementById('active-filename-display');
    const readingTimeDisplay = document.getElementById('reading-time');
    const breadcrumbCurrent = document.getElementById('breadcrumb-current');
    const headerIcon = document.getElementById('header-icon');
    const searchInput = document.getElementById('doc-search');
    const btnCopyRaw = document.getElementById('btn-copy-raw');
    const btnToggleSidebar = document.getElementById('btn-toggle-sidebar');
    const sidebar = document.getElementById('doc-sidebar');

    const btnPrev = document.getElementById('btn-prev-chapter');
    const btnNext = document.getElementById('btn-next-chapter');
    const prevTitle = document.getElementById('prev-chapter-title');
    const nextTitle = document.getElementById('next-chapter-title');

    // 2. Inisialisasi Mermaid.js dengan setting dark mode elegan
    if (window.mermaid) {
        mermaid.initialize({
            startOnLoad: false,
            theme: 'dark',
            securityLevel: 'loose',
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            themeVariables: {
                darkMode: true,
                background: '#090e1a',
                primaryColor: '#6366f1',
                primaryTextColor: '#f8fafc',
                primaryBorderColor: '#818cf8',
                lineColor: '#38bdf8',
                secondaryColor: '#ec4899',
                tertiaryColor: '#1e293b'
            }
        });
    }

    // 3. Konfigurasi Kustom Marked.js Renderer (Dukungan Marked v12 Token Object)
    const renderer = new marked.Renderer();

    // Override renderer code untuk menangani Mermaid dan Highlight.js secara aman
    renderer.code = function(tokenOrCode, infostring) {
        const rawCode = (typeof tokenOrCode === 'object' && tokenOrCode !== null) 
            ? (tokenOrCode.text || '') 
            : (tokenOrCode || '');
        const language = ((typeof tokenOrCode === 'object' && tokenOrCode !== null) 
            ? (tokenOrCode.lang || '') 
            : (infostring || '')).trim().toLowerCase();

        // Diagram Mermaid
        if (language === 'mermaid') {
            return `
                <div class="mermaid-wrapper">
                    <div class="mermaid-header">
                        <span class="flex items-center gap-1.5 font-mono">
                            <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                            Diagram Interaktif
                        </span>
                        <span class="badge badge-xs badge-neutral font-mono">Mermaid.js</span>
                    </div>
                    <div class="mermaid-chart">
                        <pre class="mermaid">${rawCode}</pre>
                    </div>
                </div>
            `;
        }

        // Code Block dengan Highlight.js dan Tombol Salin
        let highlighted = rawCode;
        if (window.hljs && language && hljs.getLanguage(language)) {
            try {
                highlighted = hljs.highlight(rawCode, { language }).value;
            } catch (err) {
                console.warn('Highlight syntax error:', err);
            }
        } else if (window.hljs) {
            highlighted = hljs.highlightAuto(rawCode).value;
        }

        const langLabel = language ? language.toUpperCase() : 'CODE';
        const escapedCode = encodeURIComponent(rawCode);

        return `
            <div class="code-block-wrapper">
                <div class="code-block-header">
                    <span>${langLabel}</span>
                    <button class="code-copy-btn" onclick="copyCodeSnippet(this, '${escapedCode}')">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span>Salin</span>
                    </button>
                </div>
                <pre><code class="hljs ${language ? 'language-' + language : ''}">${highlighted}</code></pre>
            </div>
        `;
    };

    marked.use({
        renderer: renderer,
        gfm: true,
        breaks: false
    });

    // Helper salin snippet
    window.copyCodeSnippet = function(button, encodedCode) {
        const text = decodeURIComponent(encodedCode);
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = button.innerHTML;
            button.innerHTML = `<span>✔ Tersalin!</span>`;
            button.style.borderColor = '#34d399';
            button.style.color = '#34d399';
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.style.borderColor = '';
                button.style.color = '';
            }, 1800);
        });
    };

    // 4. Fungsi Render Dokumen Instan (0ms Delay)
    async function renderDocument(slug) {
        const doc = documents[slug];
        if (!doc) {
            container.innerHTML = `<div class="alert alert-error">Dokumen [${slug}] tidak ditemukan.</div>`;
            return;
        }

        currentSlug = slug;

        // Update header & breadcrumbs
        titleDisplay.textContent = doc.title;
        filenameDisplay.textContent = `docs/${doc.filename}`;
        breadcrumbCurrent.textContent = doc.title;
        if (headerIcon) {
            headerIcon.setAttribute('data-lucide', doc.icon || 'file-text');
        }

        // Estimasi waktu baca
        const words = doc.content.split(/\s+/).length;
        const minutes = Math.max(1, Math.ceil(words / 200));
        readingTimeDisplay.textContent = `~${minutes} min read (${words.toLocaleString()} kata)`;

        // Render Markdown ke HTML
        container.innerHTML = marked.parse(doc.content);

        // Render Diagram Mermaid jika ada
        if (window.mermaid) {
            const mermaidEls = container.querySelectorAll('.mermaid');
            if (mermaidEls.length > 0) {
                try {
                    await mermaid.run({
                        nodes: mermaidEls
                    });
                } catch (err) {
                    console.error('Mermaid render issue:', err);
                }
            }
        }

        // Refresh Lucide Icons
        if (window.lucide) {
            lucide.createIcons();
        }

        // Update active status di menu navigasi sidebar
        document.querySelectorAll('.doc-nav-item').forEach(el => {
            const isMatch = (el.getAttribute('data-slug') === slug);
            const iconBox = el.querySelector('.icon-box');
            const badge = el.querySelector('.order-badge');

            if (isMatch) {
                el.classList.add('nav-item-active');
                el.classList.remove('text-base-content/70');
                if (iconBox) {
                    iconBox.className = 'icon-box w-6 h-6 rounded flex items-center justify-center shrink-0 bg-white/20 text-white';
                }
                if (badge) {
                    badge.className = 'order-badge badge badge-xs font-mono text-[9px] shrink-0 bg-white/20 text-white border-0 opacity-100';
                }
            } else {
                el.classList.remove('nav-item-active');
                el.classList.add('text-base-content/70');
                if (iconBox) {
                    iconBox.className = 'icon-box w-6 h-6 rounded flex items-center justify-center shrink-0 bg-base-300 text-primary';
                }
                if (badge) {
                    badge.className = 'order-badge badge badge-neutral badge-xs font-mono text-[9px] shrink-0 opacity-60';
                }
            }
        });

        // Update Pager (Tombol Bab Sebelumnya & Selanjutnya)
        updateChapterPager(slug);
    }

    function updateChapterPager(slug) {
        const idx = slugList.indexOf(slug);

        if (idx > 0) {
            const prevSlug = slugList[idx - 1];
            btnPrev.classList.remove('hidden');
            prevTitle.textContent = documents[prevSlug].title;
            btnPrev.onclick = () => navigateTo(prevSlug);
        } else {
            btnPrev.classList.add('hidden');
        }

        if (idx >= 0 && idx < slugList.length - 1) {
            const nextSlug = slugList[idx + 1];
            btnNext.classList.remove('hidden');
            nextTitle.textContent = documents[nextSlug].title;
            btnNext.onclick = () => navigateTo(nextSlug);
        } else {
            btnNext.classList.add('hidden');
        }
    }

    function navigateTo(slug) {
        if (slug === currentSlug) return;
        history.pushState({ slug }, '', `/docs/${slug}`);
        renderDocument(slug);
        window.scrollTo({ top: 80, behavior: 'smooth' });
    }

    // 5. Interseptor Klik Menu Sidebar (SPA Navigation)
    document.getElementById('doc-menu').addEventListener('click', (e) => {
        const item = e.target.closest('.doc-nav-item');
        if (!item) return;

        e.preventDefault();
        const slug = item.getAttribute('data-slug');
        navigateTo(slug);
    });

    // 6. Popstate untuk tombol Back/Forward browser
    window.addEventListener('popstate', (e) => {
        const slug = e.state?.slug || location.pathname.split('/').pop() || 'readme';
        if (documents[slug]) {
            renderDocument(slug);
        }
    });

    // 7. Salin Full Raw Markdown
    btnCopyRaw.addEventListener('click', () => {
        const doc = documents[currentSlug];
        if (!doc) return;

        navigator.clipboard.writeText(doc.content).then(() => {
            const orig = btnCopyRaw.innerHTML;
            btnCopyRaw.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5 text-success"></i><span class="text-success">Tersalin!</span>`;
            if (window.lucide) lucide.createIcons();
            setTimeout(() => {
                btnCopyRaw.innerHTML = orig;
                if (window.lucide) lucide.createIcons();
            }, 2000);
        });
    });

    // 8. Filter Pencarian Cepat
    searchInput.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase().trim();
        document.querySelectorAll('#doc-menu li').forEach(li => {
            const txt = li.textContent.toLowerCase();
            li.style.display = txt.includes(val) ? '' : 'none';
        });
    });

    // 9. Toggle Sidebar Mobile
    if (btnToggleSidebar && sidebar) {
        btnToggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });
    }

    // Jalankan Render Pertama Kali
    renderDocument(currentSlug);
});
</script>
<?php $this->endBlock() ?>
