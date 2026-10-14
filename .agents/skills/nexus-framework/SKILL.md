---
name: nexus-framework
description: Official development skill for the Nexus PHP MVC Framework. Use whenever creating or modifying controllers, models, routes, migrations, views with DaisyUI 5, Lucide icons, or modular Vanilla JavaScript.
metadata:
  version: 1.0.0
  framework: Nexus MVC
  php: ">=8.2"
  css: DaisyUI 5 + Tailwind CSS 4
  icons: Lucide Icons
---

# Nexus Framework - Agent Skillset Guide

Nexus is an ultra-fast, modern PHP 8.2+ MVC framework crafted for high performance, developer ergonomics, and native single-page application (SPA) experiences without heavy frontend frameworks.

When working in this codebase, **YOU MUST** strictly comply with the architectural rules and standards detailed in this skill.

---

## 1. Core Architectural Standards

1. **Backend Language & Design**:
   - Modern PHP 8.2+ with `declare(strict_types=1);` on all PHP files.
   - PSR-4 autoloading under `App\` (application logic) and `Core\` (framework core).
   - Clean MVC separation: Handlers/Controllers (`app/Handlers`), Models (`app/Models`), Requests (`app/Requests`), Views (`resources/views`).

2. **UI Component Standard (CRITICAL: SELALU PAKAI KOMPONEN DAISYUI)**:
   - **Selama memungkinkan memakai component dari DaisyUI, GUNAKAN dari DaisyUI daripada memakai native component** (misalnya tombol mentah, input mentah, select mentah, dialog mentah, atau styling utility Tailwind CSS manual).
   - **All UI components MUST use DaisyUI 5 classes.**
   - Do NOT construct raw, repetitive Tailwind button or input styles when a DaisyUI component exists.
   - Essential DaisyUI 5 classes to always use:
     - Buttons: `btn btn-primary`, `btn btn-secondary`, `btn btn-error`, `btn btn-ghost`, `btn btn-sm`, etc.
     - Cards: `card bg-base-100 shadow-xl border border-base-200`, `card-body`, `card-title`, `card-actions`.
     - Forms: `fieldset`, `fieldset-legend`, `input input-bordered`, `textarea textarea-bordered`, `select select-bordered`, `label label-text-alt`, `checkbox`, `radio`, `toggle`.
     - Tables: `table table-zebra w-full`, `avatar placeholder`.
     - Modals: `modal`, `modal-box`, `modal-action`.
     - Feedback: `alert alert-success`, `alert alert-error`, `alert alert-info`, `badge badge-primary`, `badge badge-soft`.
     - Layout & Stats: `navbar`, `menu menu-horizontal`, `stats shadow`, `stat`, `stat-figure`, `stat-title`, `stat-value`.
     - Themes: Dark mode enabled with `<html data-theme="dark" class="dark">`.

3. **Icon Standard (CRITICAL: ALWAYS USE LUCIDE ICONS)**:
   - **Never embed raw SVG code directly in views or components.**
   - Always declare icons using Lucide's data attribute:
     ```html
     <i data-lucide="icon-name" class="w-4 h-4 text-indigo-400"></i>
     ```
   - Common icon names: `user-plus`, `users`, `home`, `trash-2`, `check-circle-2`, `alert-circle`, `terminal`, `sparkles`, `database`, `shield-check`, `arrow-right`, `external-link`.
   - The framework automatically calls `initLucideIcons()` on initial boot, on SPA transitions (`nexus:navigated`), and on Vite HMR.

4. **Frontend JavaScript Standard (CRITICAL: MODULAR VANILLA JS ONLY)**:
   - **NO heavy frontend frameworks** (No React, Vue, Svelte, or Angular).
   - **`resources/js/app.js` is strictly a gateway / entry point.**
     - It boots modules, handles global event delegations, and re-exports modules.
     - Never pile business logic, event handlers, or page-specific scripts directly into `app.js`.
   - **Organize all JavaScript into `resources/js/modules/`**:
     - `modules/icons.js`: Auto-renders Lucide icons using `createIcons`.
     - `modules/turbo.js`: Nexus Turbo SPA engine (View Transitions, AJAX forms, flashbang shield).
     - `modules/hmr.js`: Vite Hot Module Replacement status indicator.
     - Any new frontend feature (e.g. chart, modal manager, data table search) **must** be created as a new module in `resources/js/modules/<feature>.js` and imported in `app.js`.

---

## 2. Directory Structure

```text
├── app/
│   ├── Handlers/          # Controllers (e.g., HomeController, UserController)
│   ├── Middlewares/       # PSR-compatible Middlewares (CsrfMiddleware, etc.)
│   ├── Models/            # Active Record Models (e.g., User)
│   └── Requests/          # FormRequest Validators (e.g., StoreUserRequest)
├── bootstrap/
│   └── app.php            # Framework application bootstrapper
├── config/
│   ├── app.php            # App config
│   └── database.php       # SQLite / MySQL / PostgreSQL PDO config
├── core/
│   ├── App.php            # Container & App core
│   ├── Database/          # Migrator, Connection, Active Record Model, Schema Blueprint
│   ├── Http/              # Router, Route, Request, Response, Controller, FormRequest
│   ├── Validation/        # Validator engine & rules
│   ├── View/              # ViewEngine & ViteAsset bundle injector
│   └── helpers.php        # Global helper functions (route, view, old, error, etc.)
├── database/
│   └── migrations/        # Database migration files (YYYY_MM_DD_HHMMSS_*.php)
├── public/
│   ├── index.php          # Single entry point for HTTP requests
│   ├── storage/           # Symbolic link ke storage/app/public
│   └── build/             # Production bundled assets from Vite
├── storage/
│   ├── app/
│   │   ├── public/        # Berkas publik (upload avatar, gambar, dll.)
│   │   └── private/       # Berkas privat (dokumen internal, export)
│   ├── framework/         # Cache, sessions, views
│   └── logs/              # Log aplikasi
├── resources/
│   ├── css/
│   │   └── app.css        # Tailwind CSS 4 + @plugin "daisyui"
│   ├── js/
│   │   ├── app.js         # Entry point gateway
│   │   └── modules/       # Isolated JS modules (icons.js, turbo.js, hmr.js)
│   └── views/
│       ├── layouts/       # Main layouts (main.php)
│       └── pages/         # Page templates (welcome.php, users/index.php)
├── routes/
│   └── web.php            # Named web & API routes
├── nexus                  # Executable CLI tool (php nexus)
└── vite.config.js         # Vite configuration with Tailwind CSS plugin
```

---

## 3. Nexus CLI Command Reference

Always use the official `nexus` CLI for generator tasks and development:

| Command | Description | Example |
|---|---|---|
| `php nexus dev` | Runs PHP server (8000) and Vite HMR (5173) concurrently | `php nexus dev` |
| `php nexus storage:link` | Creates symbolic link `public/storage` -> `storage/app/public` | `php nexus storage:link` |
| `php nexus make:controller <Name>` | Generates a new Controller in `app/Handlers/` | `php nexus make:controller ProductController` |
| `php nexus make:model <Name>` | Generates a new Active Record Model in `app/Models/` | `php nexus make:model Product` |
| `php nexus make:request <Name>` | Generates a FormRequest validator in `app/Requests/` | `php nexus make:request StoreProductRequest` |
| `php nexus make:migration <name>` | Generates a timestamped migration in `database/migrations/` | `php nexus make:migration create_products_table` |
| `php nexus migrate` | Executes all pending migrations | `php nexus migrate` |
| `php nexus migrate:rollback` | Rolls back the most recent migration batch | `php nexus migrate:rollback` |
| `php nexus migrate:status` | Displays table of run and pending migrations | `php nexus migrate:status` |

---

## 4. Routing & Named Routes

Define routes in `routes/web.php`. Always assign names using `->name('...')`.

```php
use App\Handlers\ProductController;
use Core\Http\Router;

// Basic named route
Router::get('/products', [ProductController::class, 'index'])->name('products.index');

// Route with dynamic parameter
Router::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

// POST route with CSRF protection
Router::post('/products', [ProductController::class, 'store'])->name('products.store');

// DELETE route
Router::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

// Route Group with prefix
Router::group(['prefix' => '/admin'], function (Router $router) {
    $router->addGet('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
});
```

### URL Generation via `route()` Helper
Always generate URLs using the `route()` helper:
```php
// In Views or Controllers:
$indexUrl = route('products.index');                      // "/products"
$showUrl  = route('products.show', ['id' => $product->id]); // "/products/42"
$delUrl   = route('products.destroy', 42);                // "/products/42"
```

---

## 5. Active Record ORM Guide

Models extend `Core\Database\Model`.

```php
namespace App\Models;

use Core\Database\Model;

class Product extends Model
{
    protected ?string $table = 'products';
    protected string $primaryKey = 'id';
    
    // Mass-assignable attributes
    protected array $fillable = ['name', 'sku', 'price', 'stock'];
}
```

### Common ORM Operations
```php
// Create
$product = Product::create([
    'name'  => 'Mechanical Keyboard',
    'sku'   => 'KB-001',
    'price' => 1250000,
    'stock' => 15,
]);

// Find by ID
$product = Product::find(1);

// Querying with filters
$activeProducts = Product::where('stock', '>', 0)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Counting
$total = Product::count();

// Updating an instance
$product->price = 1100000;
$product->save();

// Deleting an instance
$product->delete();
```

---

## 6. FormRequest Validation Guide

Extend `Core\Http\FormRequest` for robust, declarative validation:

```php
namespace App\Requests;

use Core\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|min:3|max:100',
            'sku'   => 'required|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Nama produk wajib diisi.',
            'sku.unique'     => 'SKU ini telah digunakan produk lain.',
            'price.required' => 'Harga produk wajib diisi.',
        ];
    }
}
```

### Controller Injection
Typehint the FormRequest directly into the controller action. The framework validates automatically before execution:
```php
public function store(StoreProductRequest $request): Response
{
    $validated = $request->validated();
    
    Product::create($validated);
    
    set_flash('success', 'Produk berhasil ditambahkan!');
    return $this->redirect(route('products.index'));
}
```

---

## 7. View Templates & DaisyUI 5 Patterns

Views are standard PHP files located in `resources/views/pages/` rendered inside `resources/views/layouts/main.php`.

### Standard Form with DaisyUI 5, Lucide Icons & CSRF:
```php
<form action="<?= route('products.store') ?>" method="POST" class="space-y-4">
    <?= csrf_field() ?>

    <fieldset class="fieldset">
        <legend class="fieldset-legend text-xs font-semibold uppercase text-base-content/70">
            Nama Produk <span class="text-error">*</span>
        </legend>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                <i data-lucide="package" class="w-4 h-4"></i>
            </span>
            <input
                type="text"
                name="name"
                value="<?= htmlspecialchars((string) old('name', '')) ?>"
                placeholder="Contoh: Mechanical Keyboard"
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

    <button type="submit" class="btn btn-primary w-full gap-2 shadow-lg shadow-primary/20">
        <i data-lucide="save" class="w-4 h-4"></i>
        <span>Simpan Produk</span>
    </button>
</form>
```

### Standard Table with DaisyUI 5:
```php
<div class="overflow-x-auto rounded-xl border border-base-200">
    <table class="table table-zebra w-full">
        <thead>
            <tr>
                <th>Produk</th>
                <th>SKU</th>
                <th>Harga</th>
                <th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $item): ?>
                <tr>
                    <td class="font-medium"><?= htmlspecialchars($item->name) ?></td>
                    <td><span class="badge badge-neutral"><?= htmlspecialchars($item->sku) ?></span></td>
                    <td class="text-success font-semibold">Rp <?= number_format($item->price, 0, ',', '.') ?></td>
                    <td class="text-right">
                        <form action="<?= route('products.delete', $item->id) ?>" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-ghost btn-xs text-error">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

---

## 8. Frontend Modular JS Architecture

The frontend entry point is `resources/js/app.js`:

```javascript
import '../css/app.css';
import { initLucideIcons } from './modules/icons.js';
import { initTurboRouter } from './modules/turbo.js';
import { initHmrListener } from './modules/hmr.js';

function bootApplication() {
  initLucideIcons();
  initTurboRouter();
  initHmrListener(() => initLucideIcons());
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootApplication);
} else {
  bootApplication();
}

document.addEventListener('nexus:navigated', (e) => {
  initLucideIcons(e.detail?.container || document);
});
```

### Adding a New JS Feature
1. Create a dedicated module in `resources/js/modules/<feature>.js`:
   ```javascript
   export function initChart() {
     // ...
   }
   ```
2. Import and initialize it inside `resources/js/app.js`:
   ```javascript
   import { initChart } from './modules/chart.js';
   // Call inside bootApplication and nexus:navigated listener if applicable
   ```
3. Never drop inline `<script>` tags with heavy business logic into view templates. Keep all logic modular in `resources/js/modules/`.

---

## 9. Storage & Filesystem Guide

Nexus Framework includes an elegant file storage system via `Core\Storage\Storage`.

### 1. Storage Disks
- `local`: Private files in `storage/app/private/` (not accessible directly via browser).
- `public`: Public files in `storage/app/public/` (accessible via symlink `public/storage`).

### 2. Connect Storage Symlink
Always ensure the public link is active:
```bash
php nexus storage:link
```

### 3. File Operations
```php
use Core\Storage\Storage;

// Save string or content to default disk
Storage::put('reports/summary.txt', 'Content here...');

// Save specifically to public disk
Storage::disk('public')->put('avatars/user-1.jpg', $binaryData);

// Check if file exists
if (Storage::disk('public')->exists('avatars/user-1.jpg')) {
    $content = Storage::disk('public')->get('avatars/user-1.jpg');
    $url = Storage::disk('public')->url('avatars/user-1.jpg'); // http://.../storage/avatars/user-1.jpg
}

// Delete files
Storage::disk('public')->delete('avatars/user-1.jpg');
```

### 4. Handling File Uploads in Controllers
```php
public function uploadAvatar(Request $request): Response
{
    if ($request->hasFile('avatar')) {
        $file = $request->file('avatar');
        
        // Validates file and saves with random hashName to storage/app/public/avatars/
        $path = $file->store('avatars', 'public');
        
        // Or with custom name:
        // $path = $file->storeAs('avatars', 'custom-name.jpg', 'public');
        
        $url = Storage::disk('public')->url($path);
    }
}
```

