<?php
/** @var array<string, array<string, mixed>> $documents */
/** @var string $activeSlug */
/** @var array<string, mixed>|null $activeDoc */
$documents = $documents ?? [];
$activeSlug = $activeSlug ?? 'readme';
$activeDoc = $activeDoc ?? ($documents[$activeSlug] ?? null);
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark" class="dark" style="background-color: #070b14; color-scheme: dark;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= htmlspecialchars($activeDoc['title'] ?? 'Dokumentasi Sistem') ?> &bull; Nexus Cinema Docs</title>

    <!-- Google Fonts Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Vite Assets (Tailwind CSS v4 + DaisyUI 5 + Lucide Icons) -->
    <?= vite(['resources/css/app.css', 'resources/js/app.js']) ?>

    <!-- Highlight.js for Syntax Highlighting (Dark Theme) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github-dark-dimmed.min.css">
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>

    <!-- Marked.js for High-Performance Markdown Parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>

    <!-- Mermaid.js for Interactive Sequence & State Diagrams -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.9.1/dist/mermaid.min.js"></script>

    <style>
        :root {
            color-scheme: dark;
        }
        body {
            background-color: #070b14 !important;
            color: #cbd5e1;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.6);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.6);
        }

        /* Markdown Content Typography */
        .doc-content {
            color: #cbd5e1;
            font-size: 0.95rem;
            line-height: 1.85;
        }
        .doc-content h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            margin-top: 1.5rem;
            margin-bottom: 1.25rem;
            letter-spacing: -0.025em;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .doc-content h2 {
            font-size: 1.45rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            letter-spacing: -0.015em;
            padding-bottom: 0.45rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .doc-content h3 {
            font-size: 1.18rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-top: 1.75rem;
            margin-bottom: 0.75rem;
        }
        .doc-content h4 {
            font-size: 1.05rem;
            font-weight: 600;
            color: #94a3b8;
            margin-top: 1.35rem;
            margin-bottom: 0.5rem;
        }
        .doc-content p {
            margin-bottom: 1.25rem;
            color: #cbd5e1;
        }
        .doc-content ul, .doc-content ol {
            margin-bottom: 1.35rem;
            padding-left: 1.65rem;
        }
        .doc-content ul {
            list-style-type: disc;
        }
        .doc-content ol {
            list-style-type: decimal;
        }
        .doc-content li {
            margin-bottom: 0.4rem;
        }
        .doc-content hr {
            border-color: rgba(255, 255, 255, 0.08);
            margin: 2.25rem 0;
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
            margin: 1.5rem 0 2rem;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }
        .code-block-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #0f172a;
            padding: 0.5rem 1rem;
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
            gap: 0.35rem;
            transition: all 0.15s ease;
        }
        .code-copy-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #f1f5f9;
            border-color: rgba(255, 255, 255, 0.3);
        }
        .doc-content pre {
            margin: 0 !important;
            padding: 1.25rem 1.35rem !important;
            background: transparent !important;
            overflow-x: auto;
        }
        .doc-content pre code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.835rem;
            line-height: 1.65;
            background: transparent !important;
            padding: 0 !important;
        }

        /* Mermaid Wrapper */
        .mermaid-wrapper {
            background: linear-gradient(180deg, #0b1120 0%, #060913 100%);
            border: 1px solid rgba(99, 102, 241, 0.35);
            border-radius: 0.85rem;
            margin: 2.5rem 0;
            overflow: hidden;
            box-shadow: 0 16px 36px -10px rgba(0, 0, 0, 0.7);
            width: 100%;
        }
        .mermaid-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
            padding: 0.6rem 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .mermaid-chart {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2.5rem 1.5rem;
            overflow: auto;
            max-height: 80vh;
            min-height: 300px;
            background-color: #070b16;
            background-image: radial-gradient(rgba(99, 102, 241, 0.15) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .mermaid-chart svg {
            display: block;
            max-width: none !important;
            height: auto !important;
            transform-origin: top center;
            transition: transform 0.15s ease-out;
            margin: 0 auto;
        }

        /* Tables */
        .doc-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.75rem 0;
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
            padding: 0.8rem 1rem;
            text-align: left;
            border-bottom: 2px solid rgba(255, 255, 255, 0.12);
            white-space: nowrap;
        }
        .doc-content td {
            padding: 0.75rem 1rem;
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

        /* Callouts / Alerts */
        .doc-content blockquote {
            border-left: 4px solid #6366f1;
            background: rgba(99, 102, 241, 0.08);
            padding: 0.9rem 1.35rem;
            border-radius: 0 0.5rem 0.5rem 0;
            margin: 1.5rem 0;
            color: #e2e8f0;
        }

        /* Active Sidebar Item Style (Vibrant Gradient with Glowing Shadow) */
        .nav-item-active {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 15px -2px rgba(99, 102, 241, 0.5) !important;
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

        /* Interactive Jobdesk Checklist & Progress Dashboard */
        .doc-content ul.contains-task-list {
            list-style-type: none !important;
            padding-left: 0.25rem !important;
            margin-top: 0.25rem !important;
            margin-bottom: 0.5rem !important;
        }
        .doc-content ul.contains-task-list ul.contains-task-list {
            padding-left: 1.5rem !important;
            border-left: 1px dashed rgba(99, 102, 241, 0.25) !important;
            margin-left: 0.75rem !important;
            margin-top: 0.4rem !important;
            margin-bottom: 0.6rem !important;
        }
        .doc-content li.task-list-item {
            list-style-type: none !important;
            margin-bottom: 0.45rem !important;
            display: block !important;
            width: 100% !important;
        }
        .doc-content .task-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.45rem 0.65rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            background-color: rgba(255, 255, 255, 0.015);
            border: 1px solid rgba(255, 255, 255, 0.03);
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }
        .doc-content .task-row:hover {
            background-color: rgba(99, 102, 241, 0.08);
            border-color: rgba(99, 102, 241, 0.25);
            transform: translateX(2px);
        }
        .doc-content .task-row input[type="checkbox"] {
            margin-top: 0.35rem;
            cursor: pointer;
        }
        .doc-content .task-row.is-task-done {
            background-color: rgba(16, 185, 129, 0.04) !important;
            border-color: rgba(16, 185, 129, 0.15) !important;
        }
        .doc-content .task-row.is-task-done .task-content-text {
            text-decoration: line-through;
            opacity: 0.5;
            color: #94a3b8;
        }
        .jobdesk-dashboard-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(10, 15, 29, 0.98) 100%);
            border: 1px solid rgba(99, 102, 241, 0.35);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 20px 45px -12px rgba(0, 0, 0, 0.7);
            position: relative;
            overflow: hidden;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- Top Sticky Header Khusus Docs Portal -->
    <header class="h-16 bg-[#0a0f1d]/90 backdrop-blur-xl border-b border-white/10 sticky top-0 z-50 px-4 sm:px-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="<?= route('docs.index') ?>" id="brand-link" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-primary to-accent p-[1px] shadow-lg shadow-primary/25">
                    <div class="w-full h-full bg-[#0a0f1d] rounded-[11px] flex items-center justify-center font-extrabold text-transparent bg-clip-text bg-gradient-to-tr from-primary to-accent text-sm">
                        NX
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-white text-base tracking-tight">Nexus Cinema Docs</span>
                        <span class="badge badge-primary badge-xs font-mono">v1.0</span>
                    </div>
                    <p class="text-[10px] text-base-content/50 font-mono hidden sm:block">Architecture & Concurrency Engineering</p>
                </div>
            </a>
        </div>

        <div class="flex items-center gap-3">
            <button id="btn-copy-raw" class="btn btn-xs sm:btn-sm bg-slate-800 hover:bg-slate-700 text-slate-100 hover:text-white border border-slate-600/80 gap-1.5 font-medium text-xs shadow-md transition-all">
                <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                <span class="hidden sm:inline">Salin Raw MD</span>
            </button>
            <a href="<?= route('home') ?>" class="btn btn-xs sm:btn-sm btn-primary gap-1.5 font-medium text-xs">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali ke App</span>
            </a>
            <button id="btn-toggle-sidebar" class="btn btn-xs sm:btn-sm btn-ghost lg:hidden">
                <i data-lucide="menu" class="w-4 h-4"></i>
            </button>
        </div>
    </header>

    <!-- Main Workspace: Split Full-Width View -->
    <div class="flex-1 flex w-full max-w-[1700px] mx-auto">

        <!-- Sidebar Navigasi Kiri (Sticky Full-Height) -->
        <aside id="doc-sidebar" class="w-80 shrink-0 border-r border-white/10 bg-[#080d19]/80 backdrop-blur-xl h-[calc(100vh-4rem)] sticky top-16 overflow-y-auto p-4 hidden lg:flex flex-col justify-between z-40">
            <div>
                <!-- Search Box -->
                <div class="relative mb-3">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40"></i>
                    <input 
                        type="text" 
                        id="doc-search" 
                        placeholder="Cari topik atau bab..." 
                        class="input input-xs sm:input-sm input-bordered w-full pl-8 bg-base-100/50 text-xs focus:input-primary"
                    />
                </div>

                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/40 px-2 py-1 mb-1">
                    Daftar Bab & Modul Teknis
                </div>

                <!-- Menu Bab -->
                <ul class="menu menu-sm p-0 gap-1.5" id="doc-menu">
                    <?php foreach ($documents as $slug => $doc): ?>
                        <?php $isActive = ($slug === $activeSlug); ?>
                        <li>
                            <a 
                                href="<?= route('docs.show', ['slug' => $slug]) ?>" 
                                data-slug="<?= $slug ?>" 
                                class="doc-nav-item flex items-center justify-between p-2.5 rounded-xl transition-all <?= $isActive ? 'nav-item-active' : 'text-base-content/75 hover:bg-white/5 hover:text-white' ?>"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="icon-box w-7 h-7 rounded-lg flex items-center justify-center shrink-0 bg-base-300 text-primary">
                                        <i data-lucide="<?= $doc['icon'] ?>" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <div class="truncate">
                                        <div class="text-xs font-semibold truncate leading-tight"><?= htmlspecialchars($doc['title']) ?></div>
                                        <div class="text-[10px] opacity-60 truncate mt-0.5 leading-tight"><?= htmlspecialchars($doc['subtitle']) ?></div>
                                    </div>
                                </div>
                                <span class="order-badge badge badge-neutral badge-xs font-mono text-[9px] shrink-0 opacity-60">
                                    <?= str_pad((string)$doc['order'], 2, '0', STR_PAD_LEFT) ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-base-content/40">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>8 Dokumen Aktif</span>
                </span>
                <span class="font-mono text-[10px]">Pure SPA</span>
            </div>
        </aside>

        <!-- Main Content Article Reader (Center/Right Area) -->
        <main class="flex-1 min-w-0 px-6 sm:px-12 lg:px-16 py-8 lg:py-10">
            <!-- Article Header Metadata -->
            <div class="border-b border-white/10 pb-6 mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="breadcrumbs text-xs text-base-content/50 py-0 mb-2">
                        <ul>
                            <li><a href="/docs/readme" id="breadcrumb-home" class="hover:text-primary transition-colors cursor-pointer">Nexus Docs</a></li>
                            <li id="breadcrumb-current" class="text-primary font-semibold"><?= htmlspecialchars($activeDoc['title'] ?? 'Overview') ?></li>
                        </ul>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                        <span id="active-title-display"><?= htmlspecialchars($activeDoc['title'] ?? 'Dokumentasi') ?></span>
                    </h1>
                    <p class="text-xs font-mono text-base-content/40 mt-1" id="active-filename-display">
                        docs/<?= htmlspecialchars($activeDoc['filename'] ?? '') ?>
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span class="badge badge-neutral badge-sm gap-1.5 text-xs font-mono py-3 px-3" id="reading-time">
                        ~5 min read
                    </span>
                    <span class="badge badge-outline badge-primary badge-sm text-xs font-mono py-3 px-3">
                        Mermaid Engine
                    </span>
                </div>
            </div>

            <!-- Content Area: Rendered via Marked.js (Instan 0ms) -->
            <article id="markdown-container" class="doc-content max-w-5xl 2xl:max-w-6xl w-full">
                <!-- Di-inject secara instan oleh JS -->
            </article>

            <!-- Bottom Chapter Pager -->
            <div class="max-w-5xl 2xl:max-w-6xl w-full mt-14 pt-8 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4" id="chapter-pager">
                <button id="btn-prev-chapter" class="btn btn-sm bg-slate-800 hover:bg-slate-700 text-slate-100 hover:text-white border border-slate-600/80 w-full sm:w-auto gap-2 hidden shadow-md">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-slate-300"></i>
                    <span id="prev-chapter-title">Bab Sebelumnya</span>
                </button>
                <div class="flex-1"></div>
                <button id="btn-next-chapter" class="btn btn-sm btn-primary w-full sm:w-auto gap-2 hidden">
                    <span id="next-chapter-title">Bab Selanjutnya</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </main>

    </div>

    <!-- Modal Layar Penuh Diagram Mermaid (DaisyUI Standard) -->
    <dialog id="modal-diagram-zoom" class="modal modal-bottom sm:modal-middle backdrop-blur-md">
        <div class="modal-box w-11/12 max-w-7xl max-h-[92vh] bg-[#070b16] border border-primary/30 p-0 flex flex-col overflow-hidden shadow-2xl">
            <!-- Modal Header Toolbar -->
            <div class="px-5 py-3.5 bg-base-300/90 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></span>
                    <h3 class="font-bold text-sm text-white font-mono tracking-tight">Inspeksi Detail Diagram Ukuran Penuh</h3>
                    <span class="badge badge-xs badge-primary/20 text-primary border border-primary/30 font-mono hidden sm:inline-flex">High Resolution</span>
                </div>
                <div class="flex items-center gap-1.5 bg-slate-900/90 p-1 rounded-lg border border-slate-700/80 shadow-md">
                    <button type="button" class="btn btn-xs bg-slate-800 hover:bg-slate-700 text-slate-100 hover:text-white border border-slate-600/80 h-7 min-h-0 px-2.5 gap-1 text-xs font-semibold transition-all shadow-sm" onclick="zoomModalDiagram(-0.25)" title="Perkecil (-)">
                        <svg class="w-3.5 h-3.5 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        <span class="hidden sm:inline">Perkecil</span>
                    </button>
                    <span class="text-xs font-mono text-primary-content bg-primary/25 border border-primary/40 px-2 py-0.5 rounded font-extrabold min-w-[3.5rem] text-center" id="modal-zoom-level">100%</span>
                    <button type="button" class="btn btn-xs bg-slate-800 hover:bg-slate-700 text-slate-100 hover:text-white border border-slate-600/80 h-7 min-h-0 px-2.5 gap-1 text-xs font-semibold transition-all shadow-sm" onclick="zoomModalDiagram(0.25)" title="Perbesar (+)">
                        <svg class="w-3.5 h-3.5 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        <span class="hidden sm:inline">Perbesar</span>
                    </button>
                    <button type="button" class="btn btn-xs bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-600/80 h-7 min-h-0 px-2.5 gap-1 text-xs font-mono font-semibold transition-all shadow-sm" onclick="resetModalDiagramZoom()" title="Reset Zoom">
                        <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset</span>
                    </button>
                    <form method="dialog" class="ml-1">
                        <button class="btn btn-xs btn-circle bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-600 text-sm">✕</button>
                    </form>
                </div>
            </div>
            <!-- Modal Body Canvas -->
            <div id="modal-diagram-body" class="flex-1 overflow-auto p-8 flex items-center justify-center bg-[#070b16] min-h-[500px]" style="background-image: radial-gradient(rgba(99, 102, 241, 0.15) 1px, transparent 1px); background-size: 24px 24px;">
                <!-- Di-inject SVG secara dinamis -->
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Dialog Konfirmasi Reset Progress Jobdesk (DaisyUI Standard) -->
    <dialog id="modal-reset-jobdesk" class="modal modal-bottom sm:modal-middle backdrop-blur-md">
        <div class="modal-box bg-[#0c1222] border border-error/30 max-w-md shadow-2xl">
            <div class="flex items-center gap-3 text-error mb-3">
                <div class="w-10 h-10 rounded-full bg-error/10 flex items-center justify-center shrink-0 border border-error/20">
                    <svg class="w-5 h-5 text-error" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-base text-white">Reset Semua Checklist?</h3>
                    <p class="text-xs text-base-content/60">Tindakan ini akan mengosongkan seluruh progress checklist di storage JSON.</p>
                </div>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed mb-6">
                Seluruh sub-task yang telah dicentang oleh <span class="text-primary font-bold">Aku</span>, <span class="text-success font-bold">Ais</span>, <span class="text-info font-bold">Adrian</span>, dan <span class="text-secondary font-bold">Pileyo</span> akan kembali menjadi 0%.
            </p>
            <div class="modal-action flex items-center justify-end gap-2">
                <form method="dialog">
                    <button class="btn btn-sm btn-ghost text-slate-300">Batal</button>
                </form>
                <button type="button" id="btn-confirm-reset-jobdesk" class="btn btn-sm btn-error shadow-lg shadow-error/20 gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Ya, Reset Semua</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Global Toast Container Notifikasi Realtime -->
    <div id="docs-toast-container" class="toast toast-top toast-end z-50 p-4 pointer-events-none space-y-2"></div>

    <!-- Data Dictionary Embed (Seluruh teks markdown tersimpan untuk render instan 0ms) -->
    <script id="docs-data" type="application/json">
    <?= json_encode($documents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>

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
        const searchInput = document.getElementById('doc-search');
        const btnCopyRaw = document.getElementById('btn-copy-raw');
        const btnToggleSidebar = document.getElementById('btn-toggle-sidebar');
        const sidebar = document.getElementById('doc-sidebar');

        const btnPrev = document.getElementById('btn-prev-chapter');
        const btnNext = document.getElementById('btn-next-chapter');
        const prevTitle = document.getElementById('prev-chapter-title');
        const nextTitle = document.getElementById('next-chapter-title');

        // 2. Inisialisasi Mermaid.js dengan Full Resolution & Dark Theme (Teks Tajam & Besar)
        let mermaidCounter = 0;
        window.diagramZoomStates = {};

        if (window.mermaid) {
            mermaid.initialize({
                startOnLoad: false,
                theme: 'dark',
                securityLevel: 'loose',
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                fontSize: 14,
                themeVariables: {
                    darkMode: true,
                    background: '#070b16',
                    primaryColor: '#6366f1',
                    primaryTextColor: '#f8fafc',
                    primaryBorderColor: '#818cf8',
                    lineColor: '#38bdf8',
                    secondaryColor: '#ec4899',
                    tertiaryColor: '#1e293b',
                    mainBkg: '#0d1527',
                    nodeBorder: '#6366f1',
                    clusterBkg: '#0b1120',
                    titleColor: '#f8fafc',
                    edgeLabelBackground: '#0d1527',
                    actorBkg: '#1e1b4b',
                    actorBorder: '#818cf8',
                    actorTextColor: '#ffffff',
                    actorLineColor: '#6366f1',
                    signalColor: '#38bdf8',
                    signalTextColor: '#e2e8f0',
                    labelBoxBkgColor: '#1e1b4b',
                    labelBoxBorderColor: '#818cf8',
                    labelTextColor: '#ffffff',
                    loopTextColor: '#e2e8f0',
                    noteBorderColor: '#f59e0b',
                    noteBkgColor: '#2d2006',
                    noteTextColor: '#fef3c7',
                    fontSize: '13px'
                },
                flowchart: { useMaxWidth: false, htmlLabels: true, padding: 18 },
                sequence: { 
                    useMaxWidth: false, 
                    showSequenceNumbers: true, 
                    actorFontSize: 14, 
                    noteFontSize: 13, 
                    messageFontSize: 13,
                    boxMargin: 12,
                    boxTextMargin: 6,
                    noteMargin: 12,
                    messageMargin: 38
                },
                er: { 
                    useMaxWidth: false, 
                    fontSize: 13,
                    layoutDirection: 'TB'
                },
                state: { useMaxWidth: false }
            });
        }

        // Helper Kontrol Zoom Interaktif Diagram
        window.zoomMermaid = function(diagId, delta) {
            const chart = document.getElementById(diagId + '-chart');
            if (!chart) return;
            const svg = chart.querySelector('svg');
            if (!svg) return;

            let current = window.diagramZoomStates[diagId] || 1.0;
            current = Math.max(0.4, Math.min(2.5, current + delta));
            window.diagramZoomStates[diagId] = current;

            svg.style.transform = `scale(${current})`;
            const levelEl = document.getElementById(diagId + '-zoom-level');
            if (levelEl) levelEl.textContent = Math.round(current * 100) + '%';
        };

        window.resetZoomMermaid = function(diagId) {
            const chart = document.getElementById(diagId + '-chart');
            if (!chart) return;
            const svg = chart.querySelector('svg');
            if (!svg) return;

            window.diagramZoomStates[diagId] = 1.0;
            svg.style.transform = 'scale(1)';
            const levelEl = document.getElementById(diagId + '-zoom-level');
            if (levelEl) levelEl.textContent = '100%';
        };

        window.openFullscreenDiagram = function(diagId) {
            const chart = document.getElementById(diagId + '-chart');
            if (!chart) return;
            const svg = chart.querySelector('svg');
            if (!svg) return;

            const modal = document.getElementById('modal-diagram-zoom');
            const modalBody = document.getElementById('modal-diagram-body');
            if (!modal || !modalBody) return;

            modalBody.innerHTML = '';
            const clone = svg.cloneNode(true);
            clone.id = 'modal-cloned-svg';
            clone.style.transform = 'scale(1)';
            clone.style.maxWidth = 'none';
            clone.style.height = 'auto';
            clone.style.transformOrigin = 'center center';
            modalBody.appendChild(clone);

            window.modalZoomLevel = 1.0;
            const modalLevel = document.getElementById('modal-zoom-level');
            if (modalLevel) modalLevel.textContent = '100%';

            modal.showModal();
            if (window.lucide) lucide.createIcons();
        };

        window.zoomModalDiagram = function(delta) {
            const svg = document.getElementById('modal-cloned-svg');
            if (!svg) return;

            window.modalZoomLevel = Math.max(0.3, Math.min(3.0, (window.modalZoomLevel || 1.0) + delta));
            svg.style.transform = `scale(${window.modalZoomLevel})`;
            const modalLevel = document.getElementById('modal-zoom-level');
            if (modalLevel) modalLevel.textContent = Math.round(window.modalZoomLevel * 100) + '%';
        };

        window.resetModalDiagramZoom = function() {
            const svg = document.getElementById('modal-cloned-svg');
            if (!svg) return;

            window.modalZoomLevel = 1.0;
            svg.style.transform = 'scale(1)';
            const modalLevel = document.getElementById('modal-zoom-level');
            if (modalLevel) modalLevel.textContent = '100%';
        };

        // 3. Konfigurasi Kustom Marked.js Renderer
        const renderer = new marked.Renderer();

        renderer.code = function(tokenOrCode, infostring) {
            const rawCode = (typeof tokenOrCode === 'object' && tokenOrCode !== null) 
                ? (tokenOrCode.text || '') 
                : (tokenOrCode || '');
            const language = ((typeof tokenOrCode === 'object' && tokenOrCode !== null) 
                ? (tokenOrCode.lang || '') 
                : (infostring || '')).trim().toLowerCase();

            // Diagram Mermaid dengan Toolbar Interaktif & Dukungan Zoom
            if (language === 'mermaid') {
                mermaidCounter++;
                const diagId = 'mermaid-' + mermaidCounter;
                return `
                    <div class="mermaid-wrapper" id="${diagId}-wrap">
                        <div class="mermaid-header">
                            <div class="flex items-center gap-2 font-mono">
                                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                                <span class="text-xs font-semibold text-slate-200">Diagram Arsitektur Interaktif</span>
                                <span class="badge badge-xs badge-primary/20 text-primary border border-primary/30 font-mono">Mermaid Engine</span>
                            </div>
                            <div class="flex items-center gap-1.5 bg-slate-900/90 p-1 rounded-lg border border-slate-700/80 shadow-md">
                                <button type="button" class="btn btn-xs bg-slate-800 hover:bg-slate-700 text-slate-100 hover:text-white border border-slate-600/80 h-7 min-h-0 px-2.5 gap-1 text-[11px] font-semibold transition-all shadow-sm" onclick="zoomMermaid('${diagId}', -0.2)" title="Perkecil (-)">
                                    <svg class="w-3.5 h-3.5 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                    <span class="hidden sm:inline">Perkecil</span>
                                </button>
                                <span class="text-xs font-mono text-primary-content bg-primary/25 border border-primary/40 px-2 py-0.5 rounded font-extrabold min-w-[3.25rem] text-center" id="${diagId}-zoom-level">100%</span>
                                <button type="button" class="btn btn-xs bg-slate-800 hover:bg-slate-700 text-slate-100 hover:text-white border border-slate-600/80 h-7 min-h-0 px-2.5 gap-1 text-[11px] font-semibold transition-all shadow-sm" onclick="zoomMermaid('${diagId}', 0.2)" title="Perbesar (+)">
                                    <svg class="w-3.5 h-3.5 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                    <span class="hidden sm:inline">Perbesar</span>
                                </button>
                                <button type="button" class="btn btn-xs bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-600/80 h-7 min-h-0 px-2.5 gap-1 text-[11px] font-mono font-semibold transition-all shadow-sm" onclick="resetZoomMermaid('${diagId}')" title="Reset Skala">
                                    <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Reset</span>
                                </button>
                                <button type="button" class="btn btn-xs btn-primary h-7 min-h-0 px-2.5 gap-1.5 text-[11px] font-bold text-white ml-1 shadow-md shadow-primary/30" onclick="openFullscreenDiagram('${diagId}')" title="Buka Mode Layar Penuh">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h6v6m-6 0l6-6M9 21H3v-6m0 6l6-6"/></svg>
                                    <span>Layar Penuh</span>
                                </button>
                            </div>
                        </div>
                        <div class="mermaid-chart" id="${diagId}-chart">
                            <pre class="mermaid" id="${diagId}">${rawCode}</pre>
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

        // 4. Update Status Aktif Sidebar secara Sinkron & Instan (0ms Delay)
        function updateSidebarActive(slug) {
            currentSlug = slug;
            const items = document.querySelectorAll('.doc-nav-item');
            items.forEach(el => {
                const elSlug = el.getAttribute('data-slug');
                const isMatch = (elSlug === slug);

                if (isMatch) {
                    el.classList.add('nav-item-active');
                    el.classList.remove('text-base-content/75', 'hover:bg-white/5', 'hover:text-white');
                } else {
                    el.classList.remove('nav-item-active');
                    el.classList.add('text-base-content/75', 'hover:bg-white/5', 'hover:text-white');
                }
            });
        }

        // 5. Render Dokumen Instan (0ms Delay)
        async function renderDocument(slug) {
            const doc = documents[slug];
            if (!doc) {
                container.innerHTML = `<div class="alert alert-error">Dokumen [${slug}] tidak ditemukan.</div>`;
                return;
            }

            currentSlug = slug;

            // Update Status Aktif Sidebar & Pager LANGSUNG di awal
            updateSidebarActive(slug);
            updateChapterPager(slug);

            // Update Metadata Header
            titleDisplay.textContent = doc.title;
            filenameDisplay.textContent = `docs/${doc.filename}`;
            breadcrumbCurrent.textContent = doc.title;
            document.title = `${doc.title} • Nexus Cinema Docs`;

            // Estimasi Waktu Baca
            const words = doc.content.split(/\s+/).length;
            const minutes = Math.max(1, Math.ceil(words / 200));
            readingTimeDisplay.textContent = `~${minutes} min read (${words.toLocaleString()} kata)`;

            // Render Markdown ke HTML
            try {
                container.innerHTML = marked.parse(doc.content);
            } catch (err) {
                console.error('Marked parse error:', err);
                container.innerHTML = `<pre class="text-error p-4">${doc.content}</pre>`;
            }

            // Inisialisasi Checklist Interaktif & Live Team Progress Dashboard khusus Bab 8
            if (slug === '08-jobdesk-team') {
                await initJobdeskInteractiveSystem();
            }

            // Render Diagram Mermaid jika ada (non-blocking)
            if (window.mermaid) {
                const mermaidEls = container.querySelectorAll('.mermaid');
                if (mermaidEls.length > 0) {
                    try {
                        await mermaid.run({
                            nodes: mermaidEls
                        });

                        // Post-process SVG agar teks dan ukuran diagram tampil tajam & tidak menciut
                        mermaidEls.forEach(el => {
                            const svg = el.querySelector('svg') || el;
                            if (svg && svg.tagName && svg.tagName.toLowerCase() === 'svg') {
                                svg.style.maxWidth = 'none';
                                svg.style.height = 'auto';
                                svg.removeAttribute('height');

                                const viewBox = svg.getAttribute('viewBox');
                                if (viewBox) {
                                    const parts = viewBox.trim().split(/\s+/);
                                    if (parts.length === 4) {
                                        const vbWidth = parseFloat(parts[2]);
                                        if (vbWidth > 0) {
                                            // Terapkan min-width natural diagram agar tidak ada teks yang menciut
                                            svg.style.minWidth = Math.max(vbWidth, 750) + 'px';
                                        }
                                    }
                                }
                            }
                        });
                    } catch (err) {
                        console.warn('Mermaid render notice:', err);
                    }
                }
            }

            // Refresh Lucide Icons (non-blocking)
            if (window.lucide) {
                try {
                    lucide.createIcons();
                } catch (err) {
                    console.warn('Lucide icon error:', err);
                }
            }
        }

        // =========================================================================
        // 🚀 INTERACTIVE JOBDESK & TEAM PROGRESS TRACKING ENGINE (JSON STORAGE)
        // =========================================================================
        let currentJobdeskData = null;

        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('docs-toast-container');
            if (!toastContainer) return;

            const toast = document.createElement('div');
            const alertClass = type === 'success' 
                ? 'alert-success text-emerald-300 border-emerald-500/30' 
                : (type === 'error' ? 'alert-error text-red-300 border-red-500/30' : 'alert-info text-sky-300 border-sky-500/30');
            
            const iconSvg = type === 'success' 
                ? '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>'
                : '<svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';

            toast.className = `alert ${alertClass} alert-soft shadow-2xl py-2.5 px-4 text-xs font-semibold flex items-center gap-2 border bg-[#0b1222]/95 backdrop-blur-md rounded-lg pointer-events-auto transition-all duration-300 transform translate-y-2 opacity-0`;
            toast.innerHTML = `${iconSvg}<span>${message}</span>`;

            toastContainer.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 350);
            }, 2500);
        }

        async function initJobdeskInteractiveSystem() {
            try {
                // 1. Ambil data progress terkini dari storage/app/jobdesk_progress.json
                const res = await fetch('/api/docs-jobdesk/progress', {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.status !== 'success') {
                    console.error('Gagal memuat progress jobdesk:', json);
                    return;
                }

                currentJobdeskData = json.data;

                // 2. Render Team Progress Dashboard Card di bagian atas artikel bab 8
                renderJobdeskDashboard(currentJobdeskData);

                // 3. Pasang interactivity pada checkbox list di dalam artikel
                setupInteractiveChecklists(currentJobdeskData);

            } catch (err) {
                console.error('Jobdesk init error:', err);
            }
        }

        function renderJobdeskDashboard(data) {
            // Hindari duplikasi dashboard
            const existing = document.getElementById('jobdesk-team-dashboard');
            if (existing) existing.remove();

            const dashboardWrapper = document.createElement('div');
            dashboardWrapper.id = 'jobdesk-team-dashboard';
            dashboardWrapper.className = 'jobdesk-dashboard-card mb-8';

            const stats = data.stats;
            const aku = stats.members.aku;
            const ais = stats.members.ais;
            const adrian = stats.members.adrian;
            const pileyo = stats.members.pileyo;

            dashboardWrapper.innerHTML = `
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-white/10 mb-6">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <h2 class="text-lg font-extrabold text-white tracking-tight m-0 p-0 border-none">📊 Live Team Progress Dashboard</h2>
                            <span class="badge badge-success badge-sm font-mono text-[10px] py-1 px-2">Storage JSON Active</span>
                        </div>
                        <p class="text-xs text-base-content/60 m-0">
                            Checklist di bawah ini interaktif dan otomatis tersimpan ke <code class="font-mono text-primary text-[11px]">storage/app/jobdesk_progress.json</code>.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" id="btn-refresh-jobdesk" class="btn btn-xs bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600/80 gap-1.5 h-8">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Sinkronkan</span>
                        </button>
                        <button type="button" id="btn-open-reset-jobdesk" class="btn btn-xs btn-outline btn-error gap-1.5 h-8">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>

                <!-- Overall Team Progress Bar -->
                <div class="bg-black/30 p-4 rounded-xl border border-white/5 mb-6">
                    <div class="flex items-center justify-between text-xs mb-2">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-white text-sm">Total Progress Tim Bioskop:</span>
                            <span class="font-mono text-emerald-400 font-extrabold text-sm" id="stat-overall-percent">${stats.percent}%</span>
                        </div>
                        <span class="font-mono text-xs text-base-content/60" id="stat-overall-fraction">${stats.completed} dari ${stats.total} Sub-Task Selesai</span>
                    </div>
                    <progress id="stat-overall-progress" class="progress progress-primary w-full h-3.5 bg-slate-800" value="${stats.percent}" max="100"></progress>
                </div>

                <!-- 4 Member Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <!-- 1. Aku -->
                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-primary/20 flex flex-col justify-between">
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-sm text-white">1. Aku</span>
                                <span class="badge badge-primary badge-sm text-[10px] font-mono" id="stat-badge-aku">${aku.percent}%</span>
                            </div>
                            <p class="text-[11px] text-primary/80 font-medium leading-tight mb-2">Lead Backend & DB Architect</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-mono text-base-content/60 mb-1">
                                <span>Progress</span>
                                <span id="stat-fraction-aku">${aku.completed} / ${aku.total}</span>
                            </div>
                            <progress id="stat-progress-aku" class="progress progress-primary w-full h-2 bg-slate-800" value="${aku.percent}" max="100"></progress>
                        </div>
                    </div>

                    <!-- 2. Ais -->
                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-success/20 flex flex-col justify-between">
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-sm text-white">2. Ais</span>
                                <span class="badge badge-success badge-sm text-[10px] font-mono" id="stat-badge-ais">${ais.percent}%</span>
                            </div>
                            <p class="text-[11px] text-emerald-400/80 font-medium leading-tight mb-2">Lead UI/UX & Design Specialist</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-mono text-base-content/60 mb-1">
                                <span>Progress</span>
                                <span id="stat-fraction-ais">${ais.completed} / ${ais.total}</span>
                            </div>
                            <progress id="stat-progress-ais" class="progress progress-success w-full h-2 bg-slate-800" value="${ais.percent}" max="100"></progress>
                        </div>
                    </div>

                    <!-- 3. Adrian -->
                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-info/20 flex flex-col justify-between">
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-sm text-white">3. Adrian</span>
                                <span class="badge badge-info badge-sm text-[10px] font-mono" id="stat-badge-adrian">${adrian.percent}%</span>
                            </div>
                            <p class="text-[11px] text-sky-400/80 font-medium leading-tight mb-2">Frontend Client Logic & Runtime</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-mono text-base-content/60 mb-1">
                                <span>Progress</span>
                                <span id="stat-fraction-adrian">${adrian.completed} / ${adrian.total}</span>
                            </div>
                            <progress id="stat-progress-adrian" class="progress progress-info w-full h-2 bg-slate-800" value="${adrian.percent}" max="100"></progress>
                        </div>
                    </div>

                    <!-- 4. Pileyo -->
                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-secondary/20 flex flex-col justify-between">
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-sm text-white">4. Pileyo</span>
                                <span class="badge badge-secondary badge-sm text-[10px] font-mono" id="stat-badge-pileyo">${pileyo.percent}%</span>
                            </div>
                            <p class="text-[11px] text-purple-400/80 font-medium leading-tight mb-2">QA, Security Audit & DevOps</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-mono text-base-content/60 mb-1">
                                <span>Progress</span>
                                <span id="stat-fraction-pileyo">${pileyo.completed} / ${pileyo.total}</span>
                            </div>
                            <progress id="stat-progress-pileyo" class="progress progress-secondary w-full h-2 bg-slate-800" value="${pileyo.percent}" max="100"></progress>
                        </div>
                    </div>
                </div>
            `;

            // Pasang dashboard di awal container markdown
            container.prepend(dashboardWrapper);

            // Pasang event listener untuk tombol Refresh & Reset
            document.getElementById('btn-refresh-jobdesk')?.addEventListener('click', async () => {
                await initJobdeskInteractiveSystem();
                showToast('Progress checklist berhasil disinkronkan dari server', 'info');
            });

            document.getElementById('btn-open-reset-jobdesk')?.addEventListener('click', () => {
                const modal = document.getElementById('modal-reset-jobdesk');
                if (modal) modal.showModal();
            });
        }

        function updateDashboardStats(stats) {
            if (!stats) return;

            // Overall
            const overallPercentEl = document.getElementById('stat-overall-percent');
            const overallFractionEl = document.getElementById('stat-overall-fraction');
            const overallProgressEl = document.getElementById('stat-overall-progress');

            if (overallPercentEl) overallPercentEl.textContent = `${stats.percent}%`;
            if (overallFractionEl) overallFractionEl.textContent = `${stats.completed} dari ${stats.total} Sub-Task Selesai`;
            if (overallProgressEl) overallProgressEl.value = stats.percent;

            // Members
            const members = ['aku', 'ais', 'adrian', 'pileyo'];
            members.forEach(m => {
                const data = stats.members[m];
                if (!data) return;

                const badge = document.getElementById(`stat-badge-${m}`);
                const fraction = document.getElementById(`stat-fraction-${m}`);
                const progress = document.getElementById(`stat-progress-${m}`);

                if (badge) badge.textContent = `${data.percent}%`;
                if (fraction) fraction.textContent = `${data.completed} / ${data.total}`;
                if (progress) progress.value = data.percent;
            });
        }

        function setupInteractiveChecklists(data) {
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');
            
            checkboxes.forEach((cb, index) => {
                const task = data.tasks[index];
                if (!task) return;

                cb.disabled = false;
                cb.setAttribute('data-task-id', task.id);
                cb.checked = Boolean(task.completed);
                cb.className = 'checkbox checkbox-primary checkbox-sm shrink-0 cursor-pointer mt-1 transition-all duration-200';

                const parentLi = cb.closest('li');
                if (!parentLi) return;
                parentLi.classList.add('task-list-item');

                // Bungkus checkbox dan teks saudara sekandungnya ke dalam .task-row jika belum dibungkus
                if (!cb.closest('.task-row')) {
                    const taskRow = document.createElement('div');
                    taskRow.className = `task-row ${task.completed ? 'is-task-done' : ''}`;

                    // Pindahkan checkbox ke dalam taskRow
                    parentLi.insertBefore(taskRow, cb);
                    taskRow.appendChild(cb);

                    // Buat wrapper teks
                    const textSpan = document.createElement('span');
                    textSpan.className = 'task-content-text flex-1 leading-relaxed text-slate-200';

                    // Pindahkan semua node teks/inline sebelum <ul> ke dalam textSpan
                    const childNodes = Array.from(parentLi.childNodes);
                    for (const node of childNodes) {
                        if (node === taskRow) continue;
                        if (node.tagName && (node.tagName.toLowerCase() === 'ul' || node.tagName.toLowerCase() === 'ol')) {
                            // Biarkan child <ul> tetap di parentLi di bawah taskRow!
                            break;
                        }
                        textSpan.appendChild(node);
                    }
                    taskRow.appendChild(textSpan);

                    // Klik pada baris task otomatis mentrigger checkbox
                    taskRow.addEventListener('click', (e) => {
                        if (e.target !== cb && !e.target.closest('a') && !e.target.closest('button')) {
                            cb.checked = !cb.checked;
                            cb.dispatchEvent(new Event('change'));
                        }
                    });
                } else {
                    const existingRow = cb.closest('.task-row');
                    if (existingRow) {
                        if (task.completed) {
                            existingRow.classList.add('is-task-done');
                        } else {
                            existingRow.classList.remove('is-task-done');
                        }
                    }
                }

                // Pasang event change jika belum dipasang
                if (!cb.hasAttribute('data-bound')) {
                    cb.setAttribute('data-bound', 'true');
                    cb.addEventListener('change', async (e) => {
                        const isChecked = e.target.checked;
                        const taskId = e.target.getAttribute('data-task-id');
                        const row = cb.closest('.task-row');

                        if (row) {
                            if (isChecked) {
                                row.classList.add('is-task-done');
                            } else {
                                row.classList.remove('is-task-done');
                            }
                        }

                        await sendTaskToggle(taskId, isChecked);
                    });
                }
            });
        }

        async function sendTaskToggle(taskId, completed) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('/api/docs-jobdesk/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ taskId, completed })
                });

                let json = null;
                try {
                    json = await res.json();
                } catch (e) {
                    // Response bukan JSON valid (misal 500 fatal/permission error HTML)
                }

                if (res.ok && json && json.status === 'success') {
                    currentJobdeskData = json.data;
                    updateDashboardStats(json.data.stats);
                    showToast(`Tersimpan di JSON: ${completed ? 'Sub-task Selesai' : 'Sub-task Dibatalkan'} (${json.data.stats.percent}% Total)`, 'success');
                } else {
                    const errMsg = (json && json.message) ? json.message : `Gagal menyimpan checklist (Server HTTP ${res.status}: ${res.statusText})`;
                    showToast(errMsg, 'error');
                }
            } catch (err) {
                console.error('Error toggling task:', err);
                showToast(err.message || 'Gagal terhubung ke server untuk menyimpan checklist', 'error');
            }
        }

        // Listener untuk tombol konfirmasi reset
        const btnConfirmReset = document.getElementById('btn-confirm-reset-jobdesk');
        if (btnConfirmReset) {
            btnConfirmReset.addEventListener('click', async () => {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const res = await fetch('/api/docs-jobdesk/reset', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    let json = null;
                    try {
                        json = await res.json();
                    } catch (e) {
                        // Response bukan JSON
                    }

                    if (res.ok && json && json.status === 'success') {
                        currentJobdeskData = json.data;
                        const modal = document.getElementById('modal-reset-jobdesk');
                        if (modal) modal.close();

                        // Uncheck semua checkbox di UI
                        const checkboxes = container.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(cb => {
                            cb.checked = false;
                            const li = cb.closest('li');
                            if (li) li.classList.remove('is-task-done');
                        });

                        updateDashboardStats(json.data.stats);
                        showToast('Seluruh progress checklist berhasil direset ke 0%', 'info');
                    } else {
                        const errMsg = (json && json.message) ? json.message : `Gagal mereset progress (Server HTTP ${res.status})`;
                        showToast(errMsg, 'error');
                    }
                } catch (err) {
                    console.error('Error resetting progress:', err);
                    showToast('Gagal mereset progress', 'error');
                }
            });
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
            if (!slug || !documents[slug]) return;
            
            // Switch highlight sidebar INSTAN sebelum operasi lainnya
            updateSidebarActive(slug);

            // Update URL browser History API
            if (window.location.pathname !== `/docs/${slug}`) {
                history.pushState({ slug }, '', `/docs/${slug}`);
            }

            // Render dokumen
            renderDocument(slug);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // 6. Klik Menu Sidebar SPA
        document.getElementById('doc-menu').addEventListener('click', (e) => {
            const item = e.target.closest('.doc-nav-item');
            if (!item) return;

            e.preventDefault();
            const slug = item.getAttribute('data-slug');
            navigateTo(slug);

            // Tutup sidebar mobile jika terbuka
            if (window.innerWidth < 1024 && sidebar) {
                sidebar.classList.add('hidden');
            }
        });

        // 7. Intercept Klik Link di Dalam Konten Markdown
        container.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;
            const href = link.getAttribute('href');
            if (href && (href.startsWith('/docs/') || href === '/docs')) {
                e.preventDefault();
                const targetSlug = href.replace(/^\/docs\/?/, '') || 'readme';
                navigateTo(targetSlug);
            }
        });

        // 8. Intercept Klik Brand Logo & Breadcrumb
        const brandLink = document.getElementById('brand-link');
        if (brandLink) {
            brandLink.addEventListener('click', (e) => {
                e.preventDefault();
                navigateTo('readme');
            });
        }
        const breadcrumbHome = document.getElementById('breadcrumb-home');
        if (breadcrumbHome) {
            breadcrumbHome.addEventListener('click', (e) => {
                e.preventDefault();
                navigateTo('readme');
            });
        }

        // 9. Popstate Back/Forward
        window.addEventListener('popstate', (e) => {
            let slug = e.state?.slug;
            if (!slug) {
                const parts = window.location.pathname.replace(/\/+$/, '').split('/');
                const last = parts[parts.length - 1];
                slug = (last && last !== 'docs' && documents[last]) ? last : 'readme';
            }
            if (documents[slug]) {
                navigateTo(slug);
            }
        });

        // 10. Salin Full Raw Markdown
        btnCopyRaw.addEventListener('click', () => {
            const doc = documents[currentSlug];
            if (!doc) return;

            navigator.clipboard.writeText(doc.content).then(() => {
                const orig = btnCopyRaw.innerHTML;
                btnCopyRaw.innerHTML = `<svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg><span class="text-emerald-400 font-semibold">Tersalin!</span>`;
                setTimeout(() => {
                    btnCopyRaw.innerHTML = orig;
                }, 2000);
            });
        });

        // 11. Filter Pencarian Cepat
        searchInput.addEventListener('input', (e) => {
            const val = e.target.value.toLowerCase().trim();
            document.querySelectorAll('#doc-menu li').forEach(li => {
                const txt = li.textContent.toLowerCase();
                li.style.display = txt.includes(val) ? '' : 'none';
            });
        });

        // 12. Toggle Sidebar Mobile
        if (btnToggleSidebar && sidebar) {
            btnToggleSidebar.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
            });
        }

        // Jalankan render awal
        renderDocument(currentSlug);
    });
    </script>
</body>
</html>
