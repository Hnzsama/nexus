# DOKUMENTASI SPESIFIKASI TEKNIS SISTEM
# CINEMA TICKETING & REAL-TIME SEAT BOOKING ENGINE
**Referensi Arsitektur:** Cinema XXI / TIX ID / M-Tix Style Engine  
**Stack Teknologi:** Handmade PHP MVC (Strict Types), MySQL 8.0+ (InnoDB Engine), Tailwind CSS + DaisyUI, Vite, Modular Vanilla JS, Midtrans Snap & Core API.  
**Klasifikasi Dokumen:** *Confidential & Mission-Critical Systems Architecture*  
**Versi:** 1.0.0-PROD  

---

## 📚 STRUKTUR DOKUMENTASI TEKNIS

Spesifikasi teknis ini dipecah ke dalam modul-modul terpisah agar memudahkan navigasi tim arsitektur, backend engineer, dan frontend engineer:

```
docs/
├── README.md                          # Indeks & Panduan Navigasi Arsitektur
├── 01-architecture-payment-flow.md     # Bab 1: Arsitektur Sistem & Midtrans Sequence Flow (Mermaid)
├── 02-database-schema-erd.md          # Bab 2: Skema Database, ERD (Mermaid) & MySQL 8.0 DDL
├── 03-state-machine.md                # Bab 3: State Machine Kursi & Transaksi vs Midtrans (Mermaid)
├── 04-seat-locking-timer.md           # Bab 4: Concurrency Control, Pessimistic Lock & Timer Sync
├── 05-endpoints-webhook.md            # Bab 5: Spesifikasi Endpoint, SHA-512 Signature & Webhook
├── 06-frontend-realtime.md            # Bab 6: Frontend DaisyUI Interactive Seating & Snap.js
├── 07-critical-system-audit.md        # Bab 7: Audit Brutal, Edge-Cases Finansial & Mitigasi Konkret
└── 08-jobdesk-team.md                 # Bab 8: Pembagian Jobdesk & Roadmap Tim Teknis (4 Orang)
```

---

## 🧭 DAFTAR ISI & TAUTAN MODUL

1. [Bab 1: Arsitektur Sistem & Complete Midtrans Payment Flow](/docs/01-architecture-payment-flow)
   - Gambaran alur bisnis end-to-end
   - Sequence Diagram interaktif Midtrans (Mermaid)
2. [Bab 2: Skema Database & ERD Lengkap](/docs/02-database-schema-erd)
   - Diagram Relasi Antar Entitas (Mermaid ERD)
   - DDL MySQL 8.0+ InnoDB (11 Tabel Relasional + Composite Indexes)
3. [Bab 3: State Machine Kursi, Transaksi & Midtrans Status](/docs/03-state-machine)
   - Diagram State Machine Kursi (Mermaid StateDiagram)
   - Matriks Pemetaan Status Transaksi Internal vs Status Engine Midtrans
4. [Bab 4: Mekanisme Teknis Seat Locking & Synchronized Timer](/docs/04-seat-locking-timer)
   - Larangan modifikasi status di master seats (Kardinalitas)
   - Pessimistic Locking (`SELECT ... FOR UPDATE`) & Canonical Sorting
   - Strategi Dual-Layer Expiry (Lazy vs Cron Cleanup)
   - Sinkronisasi Timer Presisi: Midtrans `custom_expiry` vs Database TTL
5. [Bab 5: Spesifikasi Endpoint & Webhook Handler Idempoten](/docs/05-endpoints-webhook)
   - Service Controller `CheckoutController::createTransaction`
   - Webhook Handler Idempoten & Verifikasi Kriptografis Signature SHA-512
6. [Bab 6: Frontend Real-Time & Snap Integration](/docs/06-frontend-realtime)
   - Denah Kursi Interaktif (Tailwind CSS + DaisyUI) dengan 4 Status Visual
   - Modul Vanilla JS `BookingEngine` dengan kompensasi jam server & integrasi `window.snap.pay`
7. [Bab 7: Critical System Audit & Midtrans Edge-Cases](/docs/07-critical-system-audit)
   - Audit Finansial: Pembayaran Sukses di Detik Terakhir vs Webhook Terlambat (Auto-Refund Engine)
   - Audit Keamanan: Webhook Spoofing & Replay Attack (Reverse API Verification)
   - Audit Konkurensi: Race Condition & Deadlock MySQL InnoDB
   - Audit Operasional: Abuse Penguncian Kursi (Troll Booking & Seat Hoarding)
   - Matriks Evaluasi Mitigasi Arsitektur
8. [Bab 8: Pembagian Jobdesk & Roadmap Tim Teknis (4 Orang)](/docs/08-jobdesk-team)
   - Matriks Tanggung Jawab & Diagram Kolaborasi (Mermaid)
   - Jobdesk & Checklist Sub-Task: **Aku** (Core Backend & DB Architect)
   - Jobdesk & Checklist Sub-Task: **Ais** (Lead UI/UX & Frontend Design Specialist)
   - Jobdesk & Checklist Sub-Task: **Pileyo** (Backend Integration & Payment Gateway Specialist)
   - Jobdesk & Checklist Sub-Task: **Adrian** (Frontend Client Logic, Real-Time Engine & QA Specialist)
   - Roadmap Milestone Mingguan & Deliverables Kunci


---

## ⚡ PRINSIP UTAMA ARSITEKTUR SISTEM

1. **Zero Double-Booking Guarantee:** Tidak ada kursi yang boleh terjual ganda dalam kondisi lonjakan konkurensi ekstrem (*ticket war*).
2. **Deterministic Locking Order:** Semua penguncian kursi wajib menggunakan *Canonical Sorting* (`seat_id ASC`) untuk meniadakan risiko kebuntuan (*deadlock*).
3. **Idempotent Webhook Processing:** Webhook Midtrans dapat dipanggil berulang kali (*at-least-once delivery*) tanpa menimbulkan efek samping ganda pada database.
4. **Strictly Synchronized Expiry:** Waktu pembayaran di gateway pembayaran ditutup sebelum waktu hold kursi di server lokal berakhir.
