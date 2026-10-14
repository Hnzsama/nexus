<?php
/**
 * Users Index Page Template
 * 
 * @var \Core\View\ViewEngine $this
 * @var \Core\Database\Collection|\App\Models\User[] $users
 * @var int $totalUsers
 * @var string|null $success
 * @var string|null $error
 */

$this->layout('layouts/main');
$this->block('title', 'Manajemen Pengguna & Pengujian ORM');
?>

<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-base-content/10">
        <div>
            <div class="flex items-center gap-3">
                <span class="badge badge-primary badge-outline font-mono text-xs font-medium py-3 px-3">
                    Active Record ORM & Request Validator
                </span>
                <span class="badge badge-success font-mono text-xs font-medium py-3 px-3">
                    Total: <?= (int) ($totalUsers ?? 0) ?> User
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-2">
                Halaman Pengujian User & ORM
            </h1>
            <p class="text-sm text-base-content/70 mt-1 max-w-2xl">
                Demonstrasi menyeluruh fitur <code class="text-primary font-mono text-xs bg-base-300 px-1.5 py-0.5 rounded border border-base-content/10">StoreUserRequest</code> (FormRequest Validator), database query mass-assignment, proteksi CSRF, auto timestamps, serta Active Record QueryBuilder.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?= route('users.seed') ?>" class="btn btn-outline btn-sm gap-2">
                <i data-lucide="database" class="w-4 h-4 text-accent"></i>
                <span>Seed 3 Dummy Users</span>
            </a>
            <a href="<?= route('api.ping') ?>" target="_blank" class="btn btn-primary btn-outline btn-sm gap-1.5 font-medium">
                <span>API Ping</span>
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    </div>

    <!-- Flash Notifications with DaisyUI Alerts -->
    <?php if (!empty($success)): ?>
        <div role="alert" class="alert alert-success shadow-lg">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
            <div>
                <h4 class="font-bold text-sm">Operasi Berhasil</h4>
                <div class="text-xs"><?= htmlspecialchars((string) $success) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div role="alert" class="alert alert-error shadow-lg">
            <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i>
            <div>
                <h4 class="font-bold text-sm">Terjadi Kesalahan</h4>
                <div class="text-xs"><?= htmlspecialchars((string) $error) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Kolom Kiri: Form Input User dengan Request Validator (DaisyUI Card) -->
        <div class="lg:col-span-4">
            <div class="card bg-base-200/60 backdrop-blur-xl border border-base-content/10 shadow-xl">
                <div class="card-body p-6 space-y-4">
                    <div>
                        <span class="badge badge-primary badge-sm font-mono uppercase tracking-wider font-semibold">
                            FormRequest Validation
                        </span>
                        <h2 class="card-title text-lg font-bold mt-2">Tambah Pengguna</h2>
                        <p class="text-xs text-base-content/70 mt-1">
                            Input divalidasi langsung oleh class <code class="text-primary font-mono">StoreUserRequest</code>. Coba masukkan email duplikat atau password pendek untuk menguji validasi.
                        </p>
                    </div>

                    <form action="<?= route('users.store') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <?= csrf_field() ?>

                        <!-- Input Nama -->
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend text-xs font-semibold text-base-content/80">
                                Nama Lengkap <span class="text-error">*</span>
                            </legend>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?= htmlspecialchars((string) old('name', '')) ?>"
                                placeholder="Contoh: John Doe"
                                class="input input-bordered w-full bg-base-300/80 <?= has_error('name') ? 'input-error' : '' ?>"
                            >
                            <?php if (has_error('name')): ?>
                                <p class="text-xs text-error flex items-center gap-1.5 mt-1 font-medium">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span><?= htmlspecialchars(error('name')) ?></span>
                                </p>
                            <?php endif; ?>
                        </fieldset>

                        <!-- Input Email -->
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend text-xs font-semibold text-base-content/80">
                                Alamat Email <span class="text-error">*</span>
                            </legend>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars((string) old('email', '')) ?>"
                                placeholder="nama@domain.com"
                                class="input input-bordered w-full bg-base-300/80 <?= has_error('email') ? 'input-error' : '' ?>"
                            >
                            <?php if (has_error('email')): ?>
                                <p class="text-xs text-error flex items-center gap-1.5 mt-1 font-medium">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span><?= htmlspecialchars(error('email')) ?></span>
                                </p>
                            <?php endif; ?>
                        </fieldset>

                        <!-- Input Password -->
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend text-xs font-semibold text-base-content/80">
                                Kata Sandi <span class="text-error">*</span>
                            </legend>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Minimal 6 karakter"
                                class="input input-bordered w-full bg-base-300/80 <?= has_error('password') ? 'input-error' : '' ?>"
                            >
                            <?php if (has_error('password')): ?>
                                <p class="text-xs text-error flex items-center gap-1.5 mt-1 font-medium">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span><?= htmlspecialchars(error('password')) ?></span>
                                </p>
                            <?php endif; ?>
                        </fieldset>

                        <!-- Input Avatar (Contoh Upload Storage) -->
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend text-xs font-semibold text-base-content/80 flex items-center justify-between">
                                <span>Foto Profil / Avatar</span>
                                <span class="badge badge-neutral badge-xs font-normal">Contoh Upload Storage</span>
                            </legend>
                            <input
                                type="file"
                                id="avatar"
                                name="avatar"
                                accept="image/png,image/jpeg,image/webp,image/gif"
                                class="file-input file-input-bordered file-input-sm w-full bg-base-300/80 file-input-primary"
                            >
                            <p class="text-[11px] text-base-content/60 mt-1">
                                Disimpan via <code class="text-primary font-mono text-[10px]">Storage::disk('public')</code> &bull; Max 2MB (JPG, PNG, WEBP)
                            </p>
                        </fieldset>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="btn btn-primary w-full shadow-lg shadow-primary/25 gap-2 uppercase tracking-wider text-xs"
                        >
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                            <span>Simpan Pengguna</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Tabel Pengguna dengan ORM (DaisyUI Table) -->
        <div class="lg:col-span-8 space-y-6">
            <div class="card bg-base-200/60 backdrop-blur-xl border border-base-content/10 shadow-xl overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-base-content/10 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">Daftar Pengguna Terdaftar</h3>
                        <p class="text-xs text-base-content/70 mt-0.5">
                            Ditarik langsung via QueryBuilder: <code class="text-accent font-mono text-xs">User::latest('id')->get()</code>
                        </p>
                    </div>
                    <span class="badge badge-neutral font-mono text-xs py-2 px-3">
                        <?= count($users ?? []) ?> baris
                    </span>
                </div>

                <?php if (empty($users)): ?>
                    <div class="p-12 text-center space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-base-300 flex items-center justify-center mx-auto text-base-content/50 border border-base-content/10">
                            <i data-lucide="users" class="w-7 h-7"></i>
                        </div>
                        <h4 class="text-sm font-semibold">Belum Ada Pengguna</h4>
                        <p class="text-xs text-base-content/70 max-w-sm mx-auto">
                            Tabel <code class="text-primary font-mono">users</code> masih kosong. Tambahkan pengguna baru melalui formulir di samping atau gunakan tombol Seed.
                        </p>
                        <div class="pt-2">
                            <a href="<?= route('users.seed') ?>" class="btn btn-primary btn-outline btn-sm gap-2">
                                <i data-lucide="database" class="w-4 h-4"></i>
                                <span>Generate Dummy Users Sekarang</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full text-xs">
                            <thead>
                                <tr class="bg-base-300/60 text-base-content/70 font-mono text-[11px] uppercase tracking-wider">
                                    <th>#ID</th>
                                    <th>Pengguna</th>
                                    <th>Email</th>
                                    <th>Dibuat Pada</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($users ?? []) as $user): ?>
                                    <tr class="hover">
                                        <td class="font-mono text-base-content/60">
                                            #<?= htmlspecialchars((string) $user->id) ?>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <?php if (!empty($user->avatar)): ?>
                                                    <div class="avatar">
                                                        <div class="w-9 h-9 rounded-full ring-2 ring-primary/40 shadow-sm overflow-hidden bg-base-300">
                                                            <img src="<?= htmlspecialchars((string) $user->avatarUrl()) ?>" alt="<?= htmlspecialchars((string) $user->name) ?>" class="object-cover w-full h-full" />
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="avatar avatar-placeholder">
                                                        <div class="bg-primary text-primary-content rounded-full w-9 h-9 flex items-center justify-center text-center font-bold text-xs shadow-sm ring-1 ring-primary/20">
                                                            <span class="leading-none text-center inline-block select-none"><?= strtoupper(substr((string) $user->name, 0, 1)) ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="font-semibold text-base-content">
                                                        <?= htmlspecialchars((string) $user->name) ?>
                                                    </div>
                                                    <?php if (!empty($user->avatar)): ?>
                                                        <span class="badge badge-success badge-soft badge-xs font-mono text-[9px] gap-1 py-1">
                                                            <i data-lucide="image" class="w-2.5 h-2.5"></i> Custom Avatar
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="font-mono text-base-content/80">
                                            <?= htmlspecialchars((string) $user->email) ?>
                                        </td>
                                        <td class="text-base-content/60 font-mono text-[11px]">
                                            <?= htmlspecialchars((string) ($user->created_at ?? '-')) ?>
                                        </td>
                                        <td class="text-right">
                                            <form action="<?= route('users.delete', $user->id) ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user \'<?= htmlspecialchars(addslashes((string)$user->name)) ?>\'?');" class="inline">
                                                <?= csrf_field() ?>
                                                <button
                                                    type="submit"
                                                    title="Hapus User"
                                                    class="btn btn-ghost btn-xs text-error hover:bg-error/15 cursor-pointer"
                                                >
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Testing & Architecture Guide -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="card bg-base-200/40 border border-base-content/10 p-5 space-y-2 shadow-sm">
                    <div class="flex items-center gap-2">
                        <i data-lucide="layers" class="w-4 h-4 text-primary"></i>
                        <h4 class="text-xs font-bold text-base-content">Active Record ORM Flow</h4>
                    </div>
                    <p class="text-[11px] text-base-content/70 leading-relaxed">
                        Data disimpan menggunakan <code class="text-primary font-mono">User::create([...])</code> yang otomatis melakukan mass assignment protection via <code class="text-primary font-mono">$fillable</code>, menginjeksi <code class="text-primary font-mono">created_at</code> dan <code class="text-primary font-mono">updated_at</code>, serta memetakan entitas ke driver database aktif.
                    </p>
                </div>

                <div class="card bg-base-200/40 border border-base-content/10 p-5 space-y-2 shadow-sm">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-accent"></i>
                        <h4 class="text-xs font-bold text-base-content">Request Validator Lifecycle</h4>
                    </div>
                    <p class="text-[11px] text-base-content/70 leading-relaxed">
                        Jika validasi gagal, engine otomatis melempar <code class="text-accent font-mono">ValidationException</code> yang di-catch oleh framework untuk me-redirect ke referer dengan flash session errors & old input (atau response 422 JSON jika AJAX/API).
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
