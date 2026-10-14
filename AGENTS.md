# Project Rules & Guidelines

## 1. UI Component Standard: Prioritaskan Komponen DaisyUI
- **Aturan Utama**: Selama memungkinkan memakai komponen dari **DaisyUI**, **SELALU gunakan komponen dari DaisyUI** daripada menggunakan native HTML component atau meracik styling manual Tailwind CSS dari nol.
- **Dilarang Menggunakan Native/Raw Styling**: Jangan membuat tombol mentah, input mentah, dropdown mentah, modal mentah, atau card mentah jika sudah ada komponen padanan dari DaisyUI.

### Pemetaan Komponen yang Wajib Digunakan:
- **Buttons**: Gunakan `btn`, `btn-primary`, `btn-secondary`, `btn-accent`, `btn-error`, `btn-ghost`, `btn-sm`, dsb.
- **Forms & Inputs**: Gunakan `fieldset`, `fieldset-legend`, `input input-bordered`, `textarea textarea-bordered`, `select select-bordered`, `checkbox`, `radio`, `toggle`, `range`.
- **Cards & Layout**: Gunakan `card`, `card-body`, `card-title`, `card-actions` (bukan sekadar `div` dengan rounded dan shadow kustom).
- **Tables**: Gunakan `table table-zebra w-full`, `avatar`, `avatar placeholder`.
- **Feedback & Alerts**: Gunakan `alert alert-success`, `alert alert-error`, `alert alert-info`, `alert alert-warning`, `badge`, `badge-soft`.
- **Stats & Metrics**: Gunakan `stats`, `stat`, `stat-figure`, `stat-title`, `stat-value`, `stat-desc`.
- **Modals & Dialogs**: Gunakan komponen DaisyUI `modal`, `modal-box`, `modal-action`.
- **Navigation & Menus**: Gunakan `navbar`, `menu menu-horizontal`, `breadcrumbs`, `tabs`, `footer`.
- **Loaders & Tooltips**: Gunakan `loading loading-spinner`, `tooltip`.

---

## 2. Icon Standard: Lucide Icons
- **Gunakan `<i data-lucide="...">`**: Selalu gunakan atribut `data-lucide="nama-ikon"` dengan class sizing (misal: `w-4 h-4`).
- **Dilarang Inline Raw SVG**: Jangan menyalin kode `<svg>...</svg>` mentah yang panjang ke dalam view template atau komponen.

---

## 3. Frontend Architecture: Modular Vanilla JS
- **`resources/js/app.js` sebagai Entry Point**: File ini bertindak sebagai orkestrator / gateway saja.
- **Logika Modular di `resources/js/modules/`**: Tempatkan fitur-fitur baru ke dalam modul terpisah di `resources/js/modules/*.js`.
- **Tanpa Framework Berat**: Dilarang menginstal atau menambahkan React, Vue, Svelte, atau Angular. Gunakan Vanilla JavaScript yang modular.

---

## 4. Backend Standard: Clean MVC & Active Record
- Gunakan strict typing `declare(strict_types=1);` pada seluruh file PHP.
- Gunakan FormRequest (`app/Requests/`) untuk validasi input formulir.
- Selalu beri nama pada rute di `routes/web.php` dan gunakan helper `route('nama.rute')` untuk URL generation.

---

## 5. Storage & Filesystem Standard
- **Gunakan Facade `Core\Storage\Storage`**: Jangan memanipulasi file dengan `file_put_contents` path absolut manual. Selalu gunakan `Storage::disk('public')` atau `Storage::disk('local')`.
- **Struktur Storage Terpusat**:
  - `storage/app/public/`: Berkas yang dapat diakses publik (avatar, upload gambar). Selalu hubungkan via symlink: `php nexus storage:link`.
  - `storage/app/private/`: Berkas sensitif (invoice, backup, export data).
  - `storage/logs/`: Berkas log aplikasi.
- **Handling File Upload**: Gunakan `$request->file('avatar')->store('avatars', 'public')`. Dapatkan URL publik menggunakan `Storage::url($path)` atau `asset('storage/' . $path)`.

