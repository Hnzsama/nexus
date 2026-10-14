<!DOCTYPE html>
<html lang="id" data-theme="dark" class="dark" style="background-color: #0b0f19; color-scheme: dark;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= $this->e($this->yield('title', 'Nexus Framework')) ?> &bull; Modern Pure PHP MVC</title>

    <!-- Critical Dark Shield & Smooth View Transitions to Prevent White Flashbang (FOUC) -->
    <style>
        :root { color-scheme: dark; }
        html, body {
            background-color: #0b0f19 !important;
            color: #f1f5f9;
        }
        #nexus-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, #6366f1, #38bdf8, #818cf8);
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.8), 0 0 4px rgba(56, 189, 248, 0.8);
            z-index: 999999;
            pointer-events: none;
            opacity: 0;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
        }
        /* View Transitions API for Buttery Cross-Fade */
        ::view-transition-old(root) {
            animation: 90ms cubic-bezier(0.4, 0, 1, 1) both nexusFadeOut;
        }
        ::view-transition-new(root) {
            animation: 140ms cubic-bezier(0, 0, 0.2, 1) both nexusFadeIn;
        }
        @keyframes nexusFadeOut { from { opacity: 1; } to { opacity: 0; } }
        @keyframes nexusFadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>

    <!-- Google Fonts Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Vite Asset Pipeline (Tailwind CSS v4 + Client HMR) -->
    <?= vite(['resources/css/app.css', 'resources/js/app.js']) ?>

    <?= $this->yield('head') ?>
</head>
<body class="min-h-screen flex flex-col font-sans antialiased text-slate-100" style="background-color: #0b0f19;">
    <!-- Top Glowing Progress Bar for Smooth Navigations -->
    <div id="nexus-progress-bar"></div>

    <!-- Navbar Header with DaisyUI -->
    <header class="navbar bg-base-300/60 backdrop-blur-xl border-b border-base-content/10 sticky top-0 z-50 px-4 sm:px-8">
        <div class="navbar-start gap-3">
            <a href="<?= route('home') ?>" class="btn btn-ghost text-left px-2 gap-3 normal-case hover:bg-base-content/10">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-primary to-accent p-[1px] shadow-lg shadow-primary/20">
                    <div class="w-full h-full bg-base-300 rounded-[11px] flex items-center justify-center font-extrabold text-transparent bg-clip-text bg-gradient-to-tr from-primary to-accent text-sm">
                        NX
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-lg tracking-tight">Nexus<span class="text-primary font-light">MVC</span></span>
                    <span class="badge badge-neutral badge-xs font-mono">v1.0</span>
                </div>
            </a>
        </div>

        <?php
            $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $isHome = ($currentUri === '/');
            $isUsers = str_starts_with((string)$currentUri, '/users');
        ?>
        <div class="navbar-center hidden sm:flex">
            <ul class="menu menu-horizontal bg-base-200/90 rounded-box p-1 border border-base-content/10 gap-1 text-xs" id="main-nav">
                <li>
                    <a href="<?= route('home') ?>" data-nav="home" class="<?= $isHome ? 'bg-primary text-primary-content font-bold shadow-md shadow-primary/25' : 'text-base-content/70 hover:text-base-content hover:bg-base-content/10 font-medium' ?> gap-2 transition-all">
                        <i data-lucide="home" class="w-3.5 h-3.5"></i>
                        <span>Beranda</span>
                    </a>
                </li>
                <li>
                    <a href="<?= route('users.index') ?>" data-nav="users" class="<?= $isUsers ? 'bg-primary text-primary-content font-bold shadow-md shadow-primary/25' : 'text-base-content/70 hover:text-base-content hover:bg-base-content/10 font-medium' ?> gap-2 transition-all">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        <span>Users & ORM</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="navbar-end gap-2 sm:gap-3">
            <div class="badge badge-neutral badge-sm gap-2 font-mono py-2.5 px-3">
                <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                <span id="hmr-status">PHP <?= PHP_VERSION ?></span>
            </div>
            <a href="<?= route('api.ping') ?>" target="_blank" class="btn btn-outline btn-primary btn-sm gap-1.5 font-medium">
                <span>API Ping</span>
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    </header>

    <!-- Main Content Slot -->
    <main id="app-main" class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12 transition-opacity duration-150">
        <?= $this->yield('content') ?>
    </main>

    <!-- Footer with DaisyUI -->
    <footer class="footer footer-center p-6 bg-base-300/40 text-base-content/60 border-t border-base-content/10 text-xs">
        <aside class="flex flex-col sm:flex-row items-center justify-between w-full max-w-6xl mx-auto px-4 gap-3">
            <div class="flex items-center gap-2">
                <i data-lucide="sparkles" class="w-4 h-4 text-primary"></i>
                <span>Crafted with <strong class="text-primary font-semibold">Nexus Architecture</strong> &bull; Pure PHP 8.2+, DaisyUI 5 & Lucide Icons</span>
            </div>
            <div class="font-mono text-xs opacity-75">
                Nexus Framework &copy; <?= date('Y') ?>
            </div>
        </aside>
    </footer>

    <?= $this->yield('scripts') ?>
</body>
</html>
