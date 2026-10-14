# 🚀 Nexus Framework

<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![DaisyUI](https://img.shields.io/badge/DaisyUI-5-1ad1a5?style=for-the-badge&logo=daisyui&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.0-38bdf8?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Lucide Icons](https://img.shields.io/badge/Lucide-Icons-f43f5e?style=for-the-badge&logo=lucide&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-6.0-646cff?style=for-the-badge&logo=vite&logoColor=white)
![Architecture](https://img.shields.io/badge/Frontend-Modular_Vanilla_JS-f59e0b?style=for-the-badge&logo=javascript&logoColor=white)

**A high-performance, developer-friendly PHP 8.2+ MVC framework engineered with an Active Record ORM, FormRequest validation, native SPA View Transitions, DaisyUI 5 component styling, Lucide icons, and modular Vanilla JavaScript.**

[Fitur Utama](#-fitur-utama) • [Instalasi Cepat](#-instalasi-cepat) • [Panduan Arsitektur](#-panduan-arsitektur) • [CLI Nexus](#-cli-command-runner) • [Panduan UI & Frontend](#-panduan-ui--frontend) • [Dokumentasi Lengkap](#-panduan-pengembangan)

</div>

---

## 🌟 Fitur Utama

- ⚡ **Ultra-Fast PHP 8.2+ Core**: Arsitektur MVC murni tanpa overhead berlebihan, mendukung strict typing, router regex terkompilasi, dan dependency container ringan.
- 🎨 **DaisyUI 5 & Tailwind CSS 4**: Komponen antarmuka modern siap pakai (Cards, Badges, Tables, Forms, Stats, Alerts) dengan dark mode elegan bawaan.
- ✨ **Lucide Icons Terintegrasi**: Render icon deklaratif melalui atribut `data-lucide` dengan otomatisasi re-scan saat navigasi SPA dan Vite HMR.
- ⚡ **Nexus Turbo SPA (Zero-Flashbang)**: Navigasi antar halaman secepat kilat tanpa reload layar putih (flashbang), dilengkapi glowing top progress bar dan native CSS View Transitions API.
- 🧩 **Modular Vanilla JS Architecture**: Tidak menggunakan framework JS berat (bebas React/Vue). `app.js` bertindak murni sebagai gateway/orchestrator, sementara logika fitur tersusun rapi di `resources/js/modules/`.
- 🗄️ **Active Record ORM & Migrations**: Query builder berbasis PDO, model Active Record (`create`, `find`, `where`, `save`, `delete`), serta migrator skema database otomatis.
- 🛡️ **FormRequest Validation**: Validasi request deklaratif dengan rules, error messages kustom, dan proteksi CSRF otomatis.
- 🛠️ **Nexus CLI Generator**: Command runner bawaan (`php nexus`) untuk menjalankan dev server, migrasi, dan generator controller, model, request, serta file migrasi.

---

## 📋 Persyaratan Sistem

- **PHP**: Versi `>= 8.2` (dengan ekstensi `pdo`, `pdo_sqlite` atau `pdo_mysql`, `mbstring`, `openssl`)
- **Composer**: Versi `>= 2.0`
- **Node.js**: Versi `>= 18.0` & **npm**

---

## 🚀 Instalasi Cepat

### 1. Clone & Masuk ke Direktori Proyek
```bash
git clone <repository-url> mvc
cd mvc
```

### 2. Install Dependensi PHP & JavaScript
```bash
# Install paket backend (PSR-4 autoloading)
composer install

# Install paket frontend (Tailwind CSS 4, DaisyUI 5, Lucide, Vite)
npm install
```

### 3. Konfigurasi Environment File
Salin file `.env.example` ke `.env`:
```bash
cp .env.example .env
```
Secara default, database menggunakan SQLite lokal yang tersimpan di `database/database.sqlite`. Jika file belum ada, sistem akan membuatnya secara otomatis.

### 4. Jalankan Migrasi Database
Eksekusi migrasi untuk membuat tabel bawaan (`users`, `migrations`):
```bash
php nexus migrate
```

### 5. Jalankan Development Server
Anda dapat menjalankan server PHP dan Vite Hot Module Replacement (HMR) secara bersamaan:

```bash
# Opsi 1: Menjalankan dev server via Nexus CLI
php nexus dev

# Opsi 2: Menjalankan di dua terminal terpisah (disarankan untuk dev aktif)
# Terminal 1 (Vite HMR):
npm run dev

# Terminal 2 (PHP Local Server):
php -S 127.0.0.1:8000 -t public
```

Buka browser Anda di: **`http://127.0.0.1:8000`**

---

## 📂 Struktur Direktori Proyek

```text
├── app/
│   ├── Handlers/          # Controller HTTP (HomeController, UserController, dsb.)
│   ├── Middlewares/       # Middleware aplikasi (CsrfMiddleware, dsb.)
│   ├── Models/            # Model Active Record ORM (User, dsb.)
│   └── Requests/          # Validator FormRequest (StoreUserRequest, dsb.)
├── bootstrap/
│   └── app.php            # Inisialisasi container & kernel aplikasi
├── config/
│   ├── app.php            # Konfigurasi aplikasi
│   └── database.php       # Konfigurasi koneksi database (SQLite / MySQL)
├── core/                  # Engine inti framework
│   ├── Database/          # Migrator, Schema Blueprint, Connection, ORM Model
│   ├── Http/              # Router, Route, Request, Response, Controller, FormRequest
│   ├── Validation/        # Validator engine & validation rules
│   ├── View/              # Native ViewEngine & ViteAsset injector
│   └── helpers.php        # Global helper functions (route, view, old, error, csrf_field)
├── database/
│   ├── database.sqlite    # File database SQLite lokal
│   └── migrations/        # File migrasi skema tabel
├── public/
│   ├── index.php          # Entry point tunggal untuk request web
│   ├── storage/           # Symbolic link ke storage/app/public
│   └── build/             # Hasil bundling aset produksi dari Vite
├── storage/
│   ├── app/
│   │   ├── public/        # Berkas publik (upload avatar, dokumen publik)
│   │   └── private/       # Berkas privat (data internal, invoice, export)
│   ├── framework/         # Cache, session files, views
│   └── logs/              # Log aplikasi
├── resources/
│   ├── css/
│   │   └── app.css        # Stylesheet utama (Tailwind CSS 4 + DaisyUI 5 plugin)
│   ├── js/
│   │   ├── app.js         # Entry point / Gateway Vanilla JS
│   │   └── modules/       # Modul modular JS (icons.js, turbo.js, hmr.js)
│   └── views/
│       ├── layouts/       # Master layout template (main.php)
│       └── pages/         # Template halaman (welcome.php, users/index.php)
├── routes/
│   └── web.php            # Pendaftaran rute web & API
├── .agents/skills/        # Skillset AI Agent (nexus-framework, daisyui)
├── nexus                  # Tool CLI resmi executable (php nexus)
└── vite.config.js         # Konfigurasi Vite & Tailwind CSS
```

---

## 🛠️ CLI Command Runner (`php nexus`)

Framework menyediakan tool CLI bawaan untuk mempermudah alur kerja pengembangan:

```bash
# Menjalankan development server terintegrasi (PHP + Vite)
php nexus dev

# Manajemen Storage Link
php nexus storage:link        # Menghubungkan public/storage -> storage/app/public

# Manajemen Migrasi Database
php nexus migrate             # Menjalankan seluruh migrasi yang belum dieksekusi
php nexus migrate:rollback    # Membatalkan (rollback) batch migrasi terakhir
php nexus migrate:status      # Menampilkan status tabel seluruh file migrasi

# Generator Kode (Scaffolding)
php nexus make:controller ProductController   # Membuat controller baru di app/Handlers/
php nexus make:model Product                  # Membuat model Active Record di app/Models/
php nexus make:request StoreProductRequest    # Membuat FormRequest validator di app/Requests/
php nexus make:migration create_products_table # Membuat file migrasi skema di database/migrations/
```

---

## 📖 Panduan Pengembangan

### 1. Routing & Named Routes

Daftarkan rute di file [routes/web.php](file:///home/Akuma/projects/mvc/routes/web.php). Berikan nama pada setiap rute menggunakan chaining method `->name('...')`:

```php
use App\Handlers\UserController;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;

// Rute GET menuju Controller Action
Router::get('/users', [UserController::class, 'index'])->name('users.index');

// Rute POST dengan FormRequest
Router::post('/users', [UserController::class, 'store'])->name('users.store');

// Rute Dinamis dengan Parameter
Router::get('/users/{id}', [UserController::class, 'show'])->name('users.show');

// Rute DELETE
Router::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

// Route Grouping dengan Prefix
Router::group(['prefix' => '/api'], function (Router $router) {
    $router->addGet('/ping', fn() => Response::json(['pong' => true]))->name('api.ping');
});
```

#### Helper `route()`
Gunakan helper `route()` di controller atau view untuk menghasilkan URL dinamis:
```php
// Menghasilkan: "/users"
$url = route('users.index');

// Menghasilkan: "/users/25"
$detailUrl = route('users.show', ['id' => 25]);
```

---

### 2. Controller & Response

Controller berada di direktori `app/Handlers/` dan mewarisi `App\Handlers\Controller`:

```php
namespace App\Handlers;

use App\Models\User;
use Core\Http\Request;
use Core\Http\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::latest()->get();
        $total = User::count();

        // Render template di resources/views/pages/users/index.php
        return $this->view('pages/users/index', [
            'users' => $users,
            'totalUsers' => $total,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $user = User::find($id);
        if (!$user) {
            return $this->json(['error' => 'User tidak ditemukan'], 404);
        }

        return $this->json($user->toArray());
    }
}
```

---

### 3. Active Record ORM & Database

Model berada di direktori `app/Models/` dan mewarisi `Core\Database\Model`.

```php
namespace App\Models;

use Core\Database\Model;

class User extends Model
{
    protected ?string $table = 'users';
    protected string $primaryKey = 'id';

    // Kolom yang dapat diisi melalui mass-assignment
    protected array $fillable = ['name', 'email', 'password'];
}
```

#### Operasi CRUD Model
```php
use App\Models\User;

// 1. Create (Menambahkan data baru)
$user = User::create([
    'name'     => 'Alice Wonderland',
    'email'    => 'alice@nexus.dev',
    'password' => password_hash('secret123', PASSWORD_BCRYPT),
]);

// 2. Read (Querying)
$user = User::find(1);                                  // Cari berdasarkan Primary Key
$active = User::where('name', 'Alice Wonderland')->first(); // Filter data
$allUsers = User::latest('id')->limit(10)->get();       // Mengurutkan dan melimit hasil
$count = User::count();                                 // Hitung jumlah record

// 3. Update (Memperbarui data)
$user = User::find(1);
$user->name = 'Alice Bob';
$user->save();

// 4. Delete (Menghapus data)
$user->delete();
```

---

### 4. FormRequest Validation

Validasi formulir dilakukan secara deklaratif menggunakan class `FormRequest` di `app/Requests/`:

```php
namespace App\Requests;

use Core\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|min:3|max:60',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Nama lengkap wajib diisi.',
            'email.unique'   => 'Email ini sudah terdaftar.',
            'password.min'   => 'Kata sandi minimal memiliki :min karakter.',
        ];
    }
}
```

#### Penggunaan di Controller:
Cukup jadikan typehint parameter pada method controller. Framework akan otomatis memvalidasi input sebelum method dieksekusi:
```php
public function store(StoreUserRequest $request): Response
{
    // Mengambil data yang telah lolos verifikasi validasi
    $validated = $request->validated();

    User::create([
        'name'     => $validated['name'],
        'email'    => $validated['email'],
        'password' => password_hash($validated['password'], PASSWORD_BCRYPT),
    ]);

    set_flash('success', 'User berhasil ditambahkan!');
    return $this->redirect(route('users.index'));
}
```

---

### 5. Penyimpanan Berkas & File Upload (Storage)

Framework menyediakan abstraksi filesystem modern melalui class `Core\Storage\Storage` dan helper `storage()`:

#### Menghubungkan Symbolic Link Publik
Untuk file yang di-upload agar dapat diakses dari browser (URL `/storage/...`), jalankan:
```bash
php nexus storage:link
```

#### Operasi Berkas Dasar
```php
use Core\Storage\Storage;

// 1. Simpan konten teks/data ke disk default ('local' privat)
Storage::put('exports/data.json', json_encode($data));

// 2. Simpan file ke disk publik ('public')
Storage::disk('public')->put('banners/hero.txt', 'Welcome to Nexus');

// 3. Membaca & memeriksa keberadaan berkas
if (Storage::disk('public')->exists('banners/hero.txt')) {
    $content = Storage::disk('public')->get('banners/hero.txt');
    $url = Storage::disk('public')->url('banners/hero.txt'); // http://localhost:8000/storage/banners/hero.txt
}

// 4. Menghapus berkas
Storage::disk('public')->delete('banners/hero.txt');
```

#### Menangani File Upload di Controller
```php
public function updateAvatar(Request $request): Response
{
    if ($request->hasFile('avatar')) {
        $file = $request->file('avatar');

        // Simpan otomatis dengan nama hash acak ke storage/app/public/avatars/
        $storedPath = $file->store('avatars', 'public');

        // Atau gunakan nama custom:
        // $storedPath = $file->storeAs('avatars', 'user-1.jpg', 'public');

        // URL publik untuk view/database:
        $publicUrl = Storage::disk('public')->url($storedPath);
    }

    return $this->redirect(route('users.index'));
}
```

---

## 🎨 Panduan UI & Frontend

### 1. Komponen DaisyUI 5
Proyek ini mengadopsi **DaisyUI 5** yang diintegrasikan langsung ke dalam **Tailwind CSS 4** via `@plugin "daisyui";` di [resources/css/app.css](file:///home/Akuma/projects/mvc/resources/css/app.css).

> [!IMPORTANT]
> **Aturan Utama UI**: Selama memungkinkan menggunakan komponen dari **DaisyUI**, **SELALU gunakan komponen dari DaisyUI** daripada menggunakan native HTML component atau merakit styling utilitas CSS manual.

Gunakan selalu komponen DaisyUI alih-alih merakit style CSS mentah:

| Komponen | Kelas DaisyUI 5 Utama |
|---|---|
| **Buttons** | `btn btn-primary`, `btn btn-secondary`, `btn btn-ghost`, `btn btn-error`, `btn-sm` |
| **Cards** | `card bg-base-100 shadow-xl border border-base-200`, `card-body`, `card-title` |
| **Forms** | `fieldset`, `fieldset-legend`, `input input-bordered`, `textarea`, `select` |
| **Tables** | `table table-zebra w-full`, `avatar placeholder` |
| **Alerts & Badges** | `alert alert-success`, `alert alert-error`, `badge badge-primary`, `badge-soft` |
| **Stats** | `stats shadow`, `stat`, `stat-figure`, `stat-title`, `stat-value` |
| **Navigation** | `navbar`, `menu menu-horizontal`, `footer` |

Contoh Formulir DaisyUI 5 dengan Helper Validasi & CSRF:
```php
<form action="<?= route('users.store') ?>" method="POST" class="space-y-4">
    <?= csrf_field() ?>

    <fieldset class="fieldset">
        <legend class="fieldset-legend text-xs font-semibold uppercase text-base-content/70">
            Nama Lengkap <span class="text-error">*</span>
        </legend>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/40">
                <i data-lucide="user" class="w-4 h-4"></i>
            </span>
            <input
                type="text"
                name="name"
                value="<?= htmlspecialchars((string) old('name', '')) ?>"
                placeholder="Contoh: John Doe"
                class="input input-bordered w-full pl-10 <?= has_error('name') ? 'input-error' : '' ?>"
                required
            />
        </div>
        <?php if (has_error('name')): ?>
            <label class="label text-error text-xs flex items-center gap-1 mt-1">
                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                <span><?= htmlspecialchars(error('name')) ?></span>
            </label>
        <?php endif; ?>
    </fieldset>

    <button type="submit" class="btn btn-primary w-full gap-2">
        <i data-lucide="user-plus" class="w-4 h-4"></i>
        <span>Simpan Data</span>
    </button>
</form>
```

---

### 2. Lucide Icons (`data-lucide`)

Hindari menempelkan kode SVG mentah yang panjang di dalam view template. Cukup gunakan tag dengan atribut `data-lucide`:

```html
<!-- Tombol Tambah User -->
<button class="btn btn-primary">
    <i data-lucide="user-plus" class="w-4 h-4"></i>
    <span>Tambah User</span>
</button>

<!-- Badge Status -->
<div class="badge badge-success gap-1.5">
    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
    <span>Aktif</span>
</div>
```

Modul [resources/js/modules/icons.js](file:///home/Akuma/projects/mvc/resources/js/modules/icons.js) secara otomatis merender ikon tersebut menjadi SVG berkualitas tinggi saat aplikasi dimuat, serta setelah setiap transisi halaman SPA.

---

### 3. Arsitektur Modular Vanilla JavaScript

Untuk menjaga aplikasi tetap cepat dan mudah dipelihara tanpa framework frontend berat:

1. **`resources/js/app.js` sebagai Gateway (Entry Point)**:
   File ini bertindak sebagai orkestrator yang mengimpor stylesheet, menginisialisasi modul, mendengarkan event global `nexus:navigated`, dan mengekspos API penting.
2. **Modul Terspesialisasi di `resources/js/modules/`**:
   - [resources/js/modules/icons.js](file:///home/Akuma/projects/mvc/resources/js/modules/icons.js): Pengelola render icon Lucide.
   - [resources/js/modules/turbo.js](file:///home/Akuma/projects/mvc/resources/js/modules/turbo.js): Mesin SPA Router, penanganan form AJAX, dan CSS View Transitions.
   - [resources/js/modules/hmr.js](file:///home/Akuma/projects/mvc/resources/js/modules/hmr.js): Pemantau status koneksi Vite Dev HMR.

#### Menambahkan Modul Baru
Jika ingin membuat fungsionalitas JavaScript baru (misal: modal dialog, data table filter, grafik analytics):
1. Buat file baru di `resources/js/modules/my-feature.js`:
   ```javascript
   export function initMyFeature() {
     // Logika JavaScript modular Anda
   }
   ```
2. Impor dan jalankan di `resources/js/app.js`:
   ```javascript
   import { initMyFeature } from './modules/my-feature.js';

   // Panggil di dalam bootApplication() dan event nexus:navigated
   ```

---

### 4. Nexus Turbo SPA & Anti-Flashbang System

Framework ini dilengkapi sistem transisi SPA bawaan yang memecahkan masalah layar putih (*white flashbang*) saat berpindah halaman:
- **Critical Dark Shield**: Style inline `#0b0f19` pada elemen `<html>` dan `<body>` di [resources/views/layouts/main.php](file:///home/Akuma/projects/mvc/resources/views/layouts/main.php) memastikan tidak ada celah layar putih bahkan sebelum CSS selesai dimuat.
- **Top Glowing Progress Bar**: Bar progres indigo bercahaya di bagian atas layar mengindikasikan aktivitas jaringan secara visual.
- **Native View Transitions API**: Perpindahan konten halaman di dalam `<div id="nexus-app">` terjadi dengan efek cross-fade yang sangat halus.
- **Form Submission Hijacking**: Form standar POST/DELETE otomatis ditangani secara asinkron tanpa reload penuh, mempertahankan state UI dengan rapi.
- **Opt-out**: Tambahkan atribut `data-turbo="false"` pada link atau form jika ingin menggunakan reload browser standar.

---

## 🚀 Build untuk Produksi

Saat siap merilis aplikasi ke server produksi:

```bash
# Kompilasi aset CSS (Tailwind + DaisyUI) dan JS (Vite Bundle)
npm run build
```

Hasil kompilasi akan tersimpan di direktori `public/build/` dengan manifest aset otomatis yang langsung dikenali oleh helper `vite()` bawaan framework.

---

## 🤖 Skillset AI Agent

Proyek ini telah dilengkapi dengan AI Skillset resmi untuk pair programming dan automasi:
- **Nexus Framework Skill**: [.agents/skills/nexus-framework/SKILL.md](file:///home/Akuma/projects/mvc/.agents/skills/nexus-framework/SKILL.md)
- **DaisyUI 5 Skill**: [.agents/skills/daisyui/SKILL.md](file:///home/Akuma/projects/mvc/.agents/skills/daisyui/SKILL.md)

Skillset ini memandu AI agent agar selalu:
1. Menghasilkan kode MVC yang bersih dan sesuai konvensi Nexus.
2. Menggunakan komponen resmi DaisyUI 5 pada seluruh view/layout.
3. Menggunakan ikon Lucide melalui `data-lucide`.
4. Mempertahankan arsitektur modular Vanilla JS dan menjaga `app.js` sebagai entry point gateway saja.

---

## 📄 Lisensi

Nexus Framework dirilis di bawah lisensi terbuka [MIT](LICENSE).
