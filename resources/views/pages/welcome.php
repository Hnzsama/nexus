<?php
/**
 * Welcome Page Template
 * Menggunakan Native View Engine berarsitektur Layout & Block/Section
 * 
 * @var \Core\View\ViewEngine $this
 * @var string $appName
 * @var string $phpVersion
 * @var string $environment
 * @var bool $debug
 */

$this->layout('layouts/main');
?>

<?php $this->block('title'); ?>
Overview &bull; <?= $this->e($appName) ?>
<?php $this->endBlock(); ?>

<div class="space-y-10">
    <!-- Hero Section with DaisyUI Card -->
    <div class="card bg-base-200/60 border border-base-content/10 shadow-2xl overflow-hidden relative p-6 sm:p-10">
        <div class="relative z-10 max-w-3xl">
            <div class="badge badge-primary badge-outline gap-2 py-3 px-3.5 mb-6 text-xs font-mono">
                <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></span>
                <span>Pure PHP 8.2+ Architecture &bull; Ready</span>
            </div>

            <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight mb-4 leading-tight">
                Crafted MVC with <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary via-info to-accent">
                    Pure PHP & Modern Vite DX
                </span>
            </h1>

            <p class="text-base-content/70 text-sm sm:text-base leading-relaxed mb-8 max-w-2xl">
                Framework buatan sendiri tanpa dependensi third-party. Dilengkapi native <code class="text-primary font-mono text-xs bg-base-300 px-2 py-0.5 rounded border border-base-content/10">.env</code> parser, layout view engine native, onion middleware, serta integrasi <b>Vite + Tailwind CSS v4 & DaisyUI 5</b>.
            </p>

            <div class="flex flex-wrap items-center gap-3">
                <a href="#test-section" class="btn btn-primary btn-md shadow-lg shadow-primary/25 gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Coba Live CSRF Test</span>
                </a>
                <a href="<?= route('greet', ['name' => 'Developer']) ?>" target="_blank" class="btn btn-outline btn-info btn-md gap-2">
                    <i data-lucide="terminal" class="w-4 h-4"></i>
                    <span>Route Param Test</span>
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                </a>
                <a href="<?= route('users.index') ?>" class="btn btn-secondary btn-outline btn-md gap-2">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    <span>User Management ORM</span>
                </a>
            </div>
        </div>

        <div class="absolute -right-16 -bottom-16 w-80 h-80 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- System Specs with DaisyUI Stats Component -->
    <div class="stats stats-vertical sm:stats-horizontal shadow-xl bg-base-200/50 border border-base-content/10 w-full">
        <div class="stat">
            <div class="stat-figure text-primary">
                <i data-lucide="cpu" class="w-6 h-6"></i>
            </div>
            <div class="stat-title text-xs font-mono">PHP VERSION</div>
            <div class="stat-value text-2xl text-primary font-mono"><?= $this->e($phpVersion) ?></div>
            <div class="stat-desc text-success flex items-center gap-1 font-medium">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Strict Types Active
            </div>
        </div>

        <div class="stat">
            <div class="stat-figure text-info">
                <i data-lucide="server" class="w-6 h-6"></i>
            </div>
            <div class="stat-title text-xs font-mono">ENVIRONMENT</div>
            <div class="stat-value text-2xl text-info font-mono uppercase"><?= $this->e($environment) ?></div>
            <div class="stat-desc text-info font-medium">Pure Native .env</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-accent">
                <i data-lucide="database" class="w-6 h-6"></i>
            </div>
            <div class="stat-title text-xs font-mono">DATABASE</div>
            <div class="stat-value text-2xl text-accent font-mono">SQLite / PDO</div>
            <div class="stat-desc text-accent font-medium">Active Record ORM</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-secondary">
                <i data-lucide="zap" class="w-6 h-6"></i>
            </div>
            <div class="stat-title text-xs font-mono">UI COMPONENT</div>
            <div class="stat-value text-2xl text-secondary font-mono">DaisyUI 5</div>
            <div class="stat-desc text-secondary font-medium">Tailwind v4 + Lucide</div>
        </div>
    </div>

    <!-- Core Architecture Highlights (DaisyUI Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1 -->
        <div class="card bg-base-200/50 border border-base-content/10 shadow-lg hover:border-primary/40 transition-all">
            <div class="card-body p-6 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 text-primary flex items-center justify-center font-bold text-sm mb-4">
                        <i data-lucide="code-xml" class="w-5 h-5"></i>
                    </div>
                    <h3 class="card-title text-base font-bold mb-2">Native View Engine</h3>
                    <p class="text-xs text-base-content/70 leading-relaxed mb-4">
                        Layout dan Block/Section berbasis PHP murni. Super cepat tanpa overhead kompilasi template pihak ketiga.
                    </p>
                </div>
                <div class="bg-base-300 p-3 rounded-xl border border-base-content/10 font-mono text-[11px] text-primary overflow-x-auto">
                    &lt;?= $this-&gt;yield('content') ?&gt;
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="card bg-base-200/50 border border-base-content/10 shadow-lg hover:border-info/40 transition-all">
            <div class="card-body p-6 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-info/10 border border-info/20 text-info flex items-center justify-center font-bold text-sm mb-4">
                        <i data-lucide="zap" class="w-5 h-5"></i>
                    </div>
                    <h3 class="card-title text-base font-bold mb-2">Vite Asset Pipeline</h3>
                    <p class="text-xs text-base-content/70 leading-relaxed mb-4">
                        Mendeteksi dev server port 5173 untuk Hot Module Replacement, serta membaca <code>manifest.json</code> saat build produksi.
                    </p>
                </div>
                <div class="bg-base-300 p-3 rounded-xl border border-base-content/10 font-mono text-[11px] text-info overflow-x-auto">
                    &lt;?= vite('resources/js/app.js') ?&gt;
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="card bg-base-200/50 border border-base-content/10 shadow-lg hover:border-accent/40 transition-all">
            <div class="card-body p-6 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-accent/10 border border-accent/20 text-accent flex items-center justify-center font-bold text-sm mb-4">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <h3 class="card-title text-base font-bold mb-2">Onion Middleware & CSRF</h3>
                    <p class="text-xs text-base-content/70 leading-relaxed mb-4">
                        Pipeline middleware terintegrasi dengan proteksi timing-safe attack menggunakan <code>hash_equals</code>.
                    </p>
                </div>
                <div class="bg-base-300 p-3 rounded-xl border border-base-content/10 font-mono text-[11px] text-accent overflow-x-auto">
                    &lt;?= csrf_field() ?&gt;
                </div>
            </div>
        </div>
    </div>

    <!-- Live Test Section with DaisyUI Form -->
    <div id="test-section" class="card bg-base-200/60 border border-base-content/10 shadow-xl p-6 sm:p-10">
        <div class="max-w-2xl">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="flask-conical" class="w-5 h-5 text-primary"></i>
                <h2 class="text-xl font-bold">Live Test: CSRF & Form Pipeline</h2>
            </div>
            <p class="text-base-content/70 text-sm mb-6 leading-relaxed">
                Kirim form di bawah ini untuk menguji siklus lengkap MVC: Request pembacaan payload, validasi CSRF token via Middleware, dan Controller response.
            </p>

            <form id="ajax-form" action="<?= route('submit') ?>" method="POST" data-turbo="false" data-native="true" class="space-y-4">
                <?= csrf_field() ?>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend text-xs font-semibold uppercase tracking-wider text-base-content/70">
                        Nama Pengembang
                    </legend>
                    <input type="text" id="name-input" name="developer_name" value="Nexus Developer" required
                        class="input input-bordered w-full bg-base-300/80">
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend text-xs font-semibold uppercase tracking-wider text-base-content/70">
                        Pesan Uji Coba
                    </legend>
                    <input type="text" id="message-input" name="message" value="Testing native MVC architecture with Vite!" required
                        class="input input-bordered w-full bg-base-300/80">
                </fieldset>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" id="submit-btn" class="btn btn-primary btn-md gap-2 shadow-lg shadow-primary/25 cursor-pointer">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim via POST</span>
                    </button>
                    <span id="form-feedback" class="text-xs text-base-content/70 font-medium"></span>
                </div>
            </form>

            <!-- Response Output Box -->
            <div id="result-container" class="mt-6 hidden">
                <div class="text-xs font-mono text-base-content/70 mb-1.5 flex items-center gap-2">
                    <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                    <span>RESPONSE:</span>
                    <span id="res-status" class="badge badge-success badge-sm font-bold">200 OK</span>
                </div>
                <pre id="json-output" class="bg-base-300 p-4 rounded-xl border border-base-content/10 text-accent font-mono text-xs overflow-x-auto leading-relaxed"></pre>
            </div>
        </div>
    </div>
</div>

