# UI Component Rules: Prioritaskan DaisyUI

Selama memungkinkan memakai komponen dari **DaisyUI**, **SELALU gunakan komponen dari DaisyUI** daripada menggunakan native HTML component atau membuat styling kustom manual dengan Tailwind CSS murni.

## Ketentuan:
1. **Prioritas Utama**:
   - Jika membuat tombol: Gunakan kelas `btn` dan variannya (`btn-primary`, `btn-secondary`, `btn-error`, `btn-ghost`, dll.).
   - Jika membuat formulir: Gunakan `fieldset`, `fieldset-legend`, `input input-bordered`, `textarea textarea-bordered`, `select select-bordered`, `checkbox`, `radio`, `toggle`.
   - Jika membuat kartu / panel: Gunakan `card`, `card-body`, `card-title`, `card-actions`.
   - Jika membuat tabel: Gunakan `table table-zebra w-full`.
   - Jika membuat modal: Gunakan DaisyUI `modal`, `modal-box`, `modal-action`.
   - Jika membuat notifikasi pesan: Gunakan `alert alert-success`, `alert alert-error`, dll.
   - Jika membuat status / badge: Gunakan `badge`, `badge-primary`, `badge-soft`, dll.
   - Jika membuat loading indicator: Gunakan `loading loading-spinner`, `loading loading-dots`.
   - Jika membuat statistik: Gunakan `stats`, `stat`, `stat-figure`, `stat-value`.
   - Jika membuat navigasi: Gunakan `navbar`, `menu`, `breadcrumbs`, `tabs`.

2. **Larangan**:
   - Dilarang membuat komponen UI dari tag native mentah yang tidak diberi kelas komponen DaisyUI yang relevan.
   - Dilarang merangkai utilitas Tailwind CSS dasar yang panjang (misalnya `px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 text-white font-medium`) untuk membuat tombol jika class `btn btn-primary` dari DaisyUI sudah ada.
