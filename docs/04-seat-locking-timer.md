# BAB 4: MEKANISME TEKNIS SEAT LOCKING & SYNCHRONIZED TIMER

---

## 4.1 Cacat Fatal Modifikasi Kolom Status pada Master `seats`
Dalam sistem bioskop, master kursi (`seats`) adalah representasi fisik dari baris dan nomor kursi di dalam studio. Satu studio bioskop memutar film dengan puluhan jadwal tayang (`showtimes`) yang berbeda setiap harinya.

```mermaid
graph TD
    Studio["Studio 1 (Kapasitas: 150 Kursi)"]
    SeatA1["Kursi Fisik: A1"]
    ST1["Jadwal: 13:00 - Spiderman"]
    ST2["Jadwal: 16:00 - Avatar"]
    ST3["Jadwal: 19:00 - Oppenheimer"]

    Studio --> SeatA1
    SeatA1 -.-> ST1
    SeatA1 -.-> ST2
    SeatA1 -.-> ST3
```

> [!CAUTION]
> Menambahkan kolom status seperti `status ENUM('AVAILABLE', 'LOCKED', 'BOOKED')` pada tabel `seats` adalah **pelanggaran kardinalitas fatal**. Jika kursi A1 diubah menjadi `LOCKED` pada jadwal jam 13:00, maka jadwal jam 16:00 dan 19:00 akan otomatis ikut terkunci secara keliru.

Oleh karena itu, isolasi status ketersediaan kursi dihitung secara dinamis berbasis relasi `showtime_id`:
1. **`BOOKED`**: Kursi tercatat pada `order_items` dengan `orders.status = 'PAID'` dan `orders.showtime_id = :showtime_id`.
2. **`HELD`**: Kursi tercatat pada `seat_holds` dengan `showtime_id = :showtime_id`, `status = 'HELD'`, dan `expires_at > NOW()`.
3. **`AVAILABLE`**: Kursi aktif pada studio yang tidak memenuhi kriteria `BOOKED` maupun `HELD`.

---

## 4.2 Pessimistic Locking & Canonical Sorting
Ketika terjadi lonjakan (*rush*) pemesanan tiket, puluhan pengguna dapat menekan tombol checkout pada kursi yang sama secara bersamaan (konkuren).

```mermaid
flowchart TD
    Req["Request Masuk: Checkout Kursi [12, 11]"] --> Sort["Canonical Sorting: Urutkan ID ASC [11, 12]"]
    Sort --> BeginTX["Buka Database Transaction: PDO::beginTransaction()"]
    BeginTX --> LockMaster["SELECT * FROM seats WHERE id IN (11, 12) ORDER BY id ASC FOR UPDATE"]
    LockMaster --> CheckBooked{"Apakah Kursi Sudah Terjual (PAID)?"}
    CheckBooked -- Ya --> RollbackConflict["Rollback & Return HTTP 409 Conflict"]
    CheckBooked -- Tidak --> CheckHolds{"Apakah Kursi Sedang Di-hold Aktif?"}
    CheckHolds -- Ya (expires_at > NOW) --> RollbackConflict
    CheckHolds -- Tidak --> InsertOrder["INSERT INTO orders & seat_holds (TTL 10 Menit)"]
    InsertOrder --> CommitTX["Commit Transaksi Database"]
    CommitTX --> HitMidtrans["Request Midtrans Snap Token"]
```

### Mengapa Canonical Sorting Wajib?
Jika User A mengunci `[12, 11]` dan User B mengunci `[11, 12]`, maka akan terjadi kebuntuan (*deadlock*):
- Transaksi A mengunci record 12, lalu menunggu record 11.
- Transaksi B mengunci record 11, lalu menunggu record 12.
- InnoDB mendeteksi *cyclic lock dependency* dan melempar error `1213 Deadlock found`.

Dengan melakukan **Canonical Sorting** (`sort($seatIds, SORT_NUMERIC)`):
- Transaksi A selalu meminta kunci secara berurutan: `11` kemudian `12`.
- Transaksi B selalu meminta kunci secara berurutan: `11` kemudian `12`.
- Transaksi B akan langsung tertahan (*wait*) di record 11 sampai Transaksi A selesai, sehingga **mustahil terjadi siklus deadlock**.

---

## 4.3 Strategi Dual-Layer Expiry: Lazy Check vs Cleanup Cron

Sistem menerapkan proteksi kedaluwarsa dua lapis:

### Layer 1: Lazy Expiration (Zero Latency Consistency)
Setiap kali sistem melakukan kueri status ketersediaan kursi atau validasi checkout, kueri database selalu menyertakan predikat waktu kedaluwarsa:
```sql
SELECT seat_id 
FROM seat_holds 
WHERE showtime_id = :showtime_id 
  AND seat_id IN (:seat_ids)
  AND status = 'HELD' 
  AND expires_at > NOW()
FOR UPDATE;
```
Jika waktu saat ini (`NOW()`) telah melampaui `expires_at`, kueri secara otomatis mengabaikan baris hold tersebut. Kursi langsung tersedia kembali untuk pengguna lain tanpa perlu menunggu proses background berjalan.

### Layer 2: Scheduled Background Worker (B-Tree Index Maintenance)
Untuk menjaga agar ukuran tabel `seat_holds` dan pohon indeks B-Tree tidak membengkak (*bloated*), sebuah background worker dijalankan melalui cron setiap 1 menit:
```sql
-- Lepaskan hold yang telah kadaluwarsa
UPDATE seat_holds 
SET status = 'RELEASED' 
WHERE status = 'HELD' 
  AND expires_at <= NOW();

-- Ubah order tertunggak menjadi EXPIRED
UPDATE orders 
SET status = 'EXPIRED' 
WHERE status = 'UNPAID' 
  AND expires_at <= NOW();
```

---

## 4.4 Sinkronisasi Presisi: Midtrans `custom_expiry` vs Database TTL

Salah satu sumber utama kegagalan pada integrasi payment gateway tiket adalah *time disparity* (perbedaan waktu kedaluwarsa). Jika Midtrans masih menerima pembayaran di menit ke-10 detik ke-10, padahal server lokal sudah melepaskan kursi di menit ke-10 detik ke-00, akan terjadi insiden fatal di mana kursi diambil orang lain sementara uang pelanggan sudah terpotong.

```mermaid
gantt
    title Sinkronisasi Jendela Waktu Hold vs Midtrans Snap
    dateFormat X
    axisFormat %s detik

    section Database Hold TTL
    Kursi Terkunci di Database (600 detik) :active, 0, 600

    section Midtrans Snap Window
    Link Snap Aktif & Menerima Pembayaran (540 detik) :crit, 0, 540
    Safety Buffer (Midtrans Menolak Pembayaran) :done, 540, 600
```

### Formula Sinkronisasi:
$$\text{Durasi Snap Midtrans} = \text{TTL Database Lokal} - \text{Safety Buffer}$$
$$T_{\text{snap}} = 600\text{ detik} - 60\text{ detik} = 540\text{ detik (9 menit)}$$

Dengan menyetel parameter `custom_expiry` di Snap API menjadi **9 menit**:
1. User hanya dapat membayar melalui QRIS / Virtual Account hingga menit ke-09:00.
2. Pada menit 09:01 s/d 10:00, Midtrans sudah memblokir seluruh upaya pembayaran baru.
3. Jeda waktu 60 detik (*Safety Buffer*) ini dialokasikan untuk mengakomodasi latensi jaringan perbankan saat mengirimkan webhook `settlement` ke server kita sebelum kursi dilepas ke publik pada menit ke-10:00.
