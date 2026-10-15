# BAB 1: ARSITEKTUR SISTEM & COMPLETE MIDTRANS PAYMENT FLOW

---

## 1.1 Gambaran Umum Alur Bisnis
Aplikasi ini dirancang untuk menangani beban konkurensi tinggi (*high-concurrency ticket rush*) saat peluncuran film *blockbuster*. Alur transaksi dirancang *fail-safe* dan *strictly consistent* dengan tahapan:

1. **Discovery & Scheduling:** User memilih Film (`movies`), Bioskop (`cinemas`), Studio (`studios`), dan Jam Tayang (`showtimes`).
2. **Interactive Seat Selection:** Frontend memuat layout kursi studio secara real-time. Status ketersediaan kursi dihitung secara dinamis dari kursi yang sudah berstatus `BOOKED` atau sedang `HELD` dengan sisa waktu valid.
3. **Pessimistic Hold & Order Creation:** Saat user menekan tombol checkout, sistem Backend melakukan penguncian pesimistik (`SELECT ... FOR UPDATE`) pada kursi-kursi terkait dengan pengurutan ID kanonikal (*canonical sorting*). Jika semua kursi valid dan tersedia, baris pada `orders` dan `seat_holds` dibuat dengan masa berlaku *Time-To-Live* (TTL) tepat 10 menit (600 detik).
4. **Midtrans Snap Token Exchange:** Backend melakukan panggilan sinkron ke Midtrans API (`/snap/v1/transactions`) dengan menyertakan payload `custom_expiry`. Parameter expiry disinkronkan persis dengan sisa waktu hold kursi dikurangi batas penyangga (*safety buffer*).
5. **Payment Gateway Popup:** Frontend memanggil `window.snap.pay(snapToken)` dan menampilkan countdown timer visual yang disinkronkan dengan jam server. User menyelesaikan pembayaran (Virtual Account BCA/Mandiri/BRI, QRIS/GoPay/ShopeePay, atau Kartu Kredit).
6. **Asynchronous Webhook Processing:** Midtrans mengirimkan notifikasi HTTP POST ke endpoint `/payment/webhook`. Backend memverifikasi otentisitas payload menggunakan Signature Key SHA-512, mengeksekusi *idempotency lock*, mengubah status order menjadi `PAID`, mengonversi status kursi dari `HELD` menjadi `CONVERTED` (resmi `BOOKED`), dan menerbitkan tiket digital serta QR Code.
7. **Client Feedback:** Webhook memicu pembaruan state database, dan saat user dialihkan ke halaman callback `/checkout/finish`, sistem menampilkan tiket resmi.

---

## 1.2 Sequence Diagram Lengkap (End-to-End)

```mermaid
sequenceDiagram
    autonumber
    actor User as User / Browser
    participant FE as Frontend (Vanilla JS + Snap.js)
    participant API as Backend (PHP MVC Engine)
    participant DB as MySQL 8.0 (InnoDB)
    participant MD as Midtrans Payment Engine

    %% Phase 1: Selection & Hold
    Note over User, DB: FASE 1: PEMILIHAN KURSI & PESSIMISTIC LOCKING
    User->>FE: Pilih Kursi (misal: A1, A2) & Klik "Checkout"
    FE->>API: POST /checkout/create-transaction {showtime_id, seat_ids: [12, 13]}
    activate API
    API->>DB: BEGIN TRANSACTION
    API->>DB: SELECT * FROM seats WHERE id IN (12, 13) ORDER BY id ASC FOR UPDATE
    API->>DB: SELECT * FROM seat_holds WHERE showtime_id = X AND seat_id IN (12,13) AND status = 'HELD' AND expires_at > NOW() FOR UPDATE
    alt Kursi Tidak Tersedia / Sudah Di-hold
        DB-->>API: Conflict Detected (Kursi Sedang Di-hold / Terjual)
        API->>DB: ROLLBACK
        API-->>FE: 409 Conflict {"message": "Kursi A1/A2 sudah tidak tersedia"}
        FE-->>User: Tampilkan Alert "Kursi baru saja dipilih orang lain"
    else Kursi Tersedia
        API->>DB: INSERT INTO orders (order_number, total_amount, status, expires_at)
        API->>DB: INSERT INTO order_items (order_id, seat_id, price)
        API->>DB: INSERT INTO seat_holds (showtime_id, seat_id, order_id, user_id, expires_at, status='HELD')
        API->>DB: COMMIT
        
        %% Phase 2: Snap Token Request
        Note over API, MD: FASE 2: MIDTRANS SNAP TOKEN ACQUISITION
        API->>MD: POST /snap/v1/transactions (OrderPayload + custom_expiry: 540s)
        MD-->>API: 201 Created {token: "SNAP_TOKEN_XYZ", redirect_url: "..."}
        API->>DB: INSERT INTO payments (order_id, snap_token, transaction_status='pending')
        API-->>FE: 200 OK {snap_token: "SNAP_TOKEN_XYZ", expires_in: 600, order_id: 1001}
    end
    deactivate API

    %% Phase 3: Payment Interaction
    Note over User, MD: FASE 3: USER PAYMENT INTERACTION
    FE->>FE: Jalankan Visual Countdown Timer (600s sync dgn server)
    FE->>FE: window.snap.pay("SNAP_TOKEN_XYZ")
    FE-->>User: Muncul Modal Snap (Pilih BCA VA / QRIS)
    User->>MD: Lakukan Pembayaran via M-Banking / E-Wallet

    %% Phase 4: Webhook & Settlement
    Note over MD, DB: FASE 4: WEBHOOK ASINKRON & IDEMPOTENT SETTLEMENT
    MD->>API: POST /payment/webhook (Payload JSON + Signature Key)
    activate API
    API->>API: Validasi Signature: SHA512(order_id + status_code + gross_amount + ServerKey)
    alt Signature Tidak Cocok
        API-->>MD: 403 Forbidden {"message": "Invalid Signature"}
    else Signature Valid
        API->>DB: BEGIN TRANSACTION
        API->>DB: SELECT * FROM orders WHERE order_number = ? FOR UPDATE
        API->>DB: Check Idempotency: Apakah status order sudah 'PAID'?
        alt Order Sudah 'PAID'
            API->>DB: ROLLBACK
            API-->>MD: 200 OK {"message": "Already processed"}
        else Order Masih 'UNPAID'
            API->>DB: UPDATE orders SET status = 'PAID', paid_at = NOW() WHERE id = ?
            API->>DB: UPDATE payments SET transaction_status = 'settlement', settlement_time = NOW(), raw_response_json = ?
            API->>DB: UPDATE seat_holds SET status = 'CONVERTED' WHERE order_id = ?
            API->>DB: INSERT INTO tickets (order_item_id, ticket_code, qr_token)
            API->>DB: COMMIT
            API-->>MD: 200 OK {"status": "success"}
        end
    end
    deactivate API

    %% Phase 5: Ticket Display
    Note over User, FE: FASE 5: REDIRECT & DISPLAY E-TICKET
    FE->>API: GET /orders/{order_number}/status
    API-->>FE: 200 OK {"status": "PAID", "tickets": [...]}
    FE-->>User: Tampilkan Halaman Tiket Sukses + QR Code Bioskop
```

---

## 1.3 Peran Tiap Komponen Sistem

```mermaid
graph TD
    User["Client Browser (Desktop/Mobile)"]
    Vite["Vite + Tailwind CSS + DaisyUI"]
    SnapJS["Midtrans Snap.js SDK"]
    NexusMVC["Nexus PHP MVC (No Heavy Framework)"]
    MySQL["MySQL 8.0 InnoDB (Row-Level Locking)"]
    MidtransAPI["Midtrans Payment Gateway (Snap API & Webhook Engine)"]

    User --> Vite
    User --> SnapJS
    Vite --> NexusMVC
    SnapJS --> MidtransAPI
    NexusMVC --> MySQL
    NexusMVC --> MidtransAPI
    MidtransAPI -->|HTTP Webhook POST| NexusMVC
```

1. **Client Browser & Modular Vanilla JS:** Menangani *seat layout rendering*, pemilih baris/kursi, *server-time offset calculation*, dan memanggil modal `window.snap.pay`.
2. **Nexus PHP MVC Framework:** Engine backend murni dengan arsitektur bersih, kepatuhan *strict types*, penanganan rute, Form Request validation, transaksi pesimistik database, dan verifikasi kriptografi.
3. **MySQL 8.0 InnoDB:** Menyimpan data dengan integritas referensial ketat (*Foreign Keys*), mengisolasi baris dengan `SELECT ... FOR UPDATE`, serta indeks komposit untuk kueri berkecepatan tinggi.
4. **Midtrans Engine:** Menerima metode pembayaran lokal (BCA/Mandiri/BRI VA, QRIS, GoPay, ShopeePay, Kartu Kredit), mengelola masa kedaluwarsa pembayaran (*custom expiry*), dan mengirim notifikasi *webhook* secara asinkron.
