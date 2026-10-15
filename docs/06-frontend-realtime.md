# BAB 6: FRONTEND REAL-TIME & SNAP INTEGRATION

---

## 6.1 Denah Kursi Interaktif (Tailwind CSS + DaisyUI)

Sistem antarmuka pengguna mengadopsi komponen resmi **DaisyUI** dengan 4 status visual standar industri bioskop modern:

1. **Available (Tersedia):** Warna Netral Abu-abu (`btn-outline btn-neutral`, transisi hover ke `btn-primary`).
2. **Selected by Current User (Dipilih Pengguna):** Warna Biru Utama DaisyUI (`btn-primary shadow-lg scale-105`).
3. **Held by Other Users (Sedang Di-hold Orang Lain):** Warna Kuning/Amber (`btn-warning cursor-not-allowed opacity-75`).
4. **Booked / Sold (Terjual Resmi):** Warna Merah / Gelap Dinonaktifkan (`btn-error btn-disabled opacity-40`).

### Desain Layout Blade / View HTML
```html
<div class="card bg-base-100 shadow-xl border border-base-200">
    <div class="card-body items-center text-center">
        <!-- Visual Layar Bioskop -->
        <div class="w-full max-w-2xl mb-8">
            <div class="h-3 bg-gradient-to-b from-primary/60 to-transparent rounded-t-full shadow-[0_10px_20px_rgba(var(--color-primary),0.3)]"></div>
            <p class="text-xs uppercase tracking-widest text-base-content/50 mt-2 font-semibold">Layar Bioskop (Studio 1 - IMAX)</p>
        </div>

        <!-- Synchronized Countdown Timer Alert (Awalnya Hidden) -->
        <div id="countdown-container" class="alert alert-warning shadow-lg max-w-md mb-6 hidden">
            <i data-lucide="clock" class="w-5 h-5 text-warning-content animate-pulse"></i>
            <div>
                <h4 class="font-bold text-sm">Selesaikan Pembayaran!</h4>
                <div class="text-xs">Sisa waktu hold kursi Anda: <span id="countdown-timer" class="font-mono font-bold text-base">10:00</span></div>
            </div>
        </div>

        <!-- Matriks Layout Kursi Dinamis -->
        <div id="seating-grid" class="flex flex-col gap-3 overflow-x-auto py-4 px-2">
            <!-- Di-generate otomatis oleh Modular Vanilla JS -->
        </div>

        <!-- Legenda Status Kursi -->
        <div class="flex flex-wrap items-center justify-center gap-6 mt-6 pt-4 border-t border-base-200 text-sm">
            <div class="flex items-center gap-2">
                <span class="btn btn-xs btn-outline btn-neutral pointer-events-none">A1</span>
                <span class="text-base-content/70">Tersedia</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="btn btn-xs btn-primary pointer-events-none">A2</span>
                <span class="text-base-content/70">Pilihan Anda</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="btn btn-xs btn-warning pointer-events-none">A3</span>
                <span class="text-base-content/70">Sedang Di-hold</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="btn btn-xs btn-error btn-disabled opacity-40 pointer-events-none">A4</span>
                <span class="text-base-content/70">Terjual</span>
            </div>
        </div>

        <!-- Action Bar Checkout -->
        <div class="card-actions justify-between items-center w-full mt-8 bg-base-200/50 p-4 rounded-box">
            <div class="text-left">
                <p class="text-xs text-base-content/60">Total Pembayaran (<span id="selected-count">0</span> Kursi)</p>
                <p class="text-xl font-black text-primary" id="total-price-display">Rp 0</p>
            </div>
            <button id="btn-checkout" class="btn btn-primary px-8" disabled>
                <i data-lucide="shield-check" class="w-4 h-4 mr-1"></i> Kunci Kursi & Bayar
            </button>
        </div>
    </div>
</div>
```

---

## 6.2 Modul JavaScript: Synchronized Countdown Timer & Snap.js Handler

Letak berkas: `resources/js/modules/booking-engine.js` (Modular Vanilla JS tanpa framework berat).

```javascript
/**
 * Cinema Booking Client Engine & Snap Synchronization
 */
export class BookingEngine {
    constructor(config) {
        this.showtimeId = config.showtimeId;
        this.basePrice = config.basePrice;
        this.apiBase = config.apiBase || '';
        this.selectedSeatIds = new Set();
        this.timerInterval = null;
        this.serverOffsetMs = 0; // Kompensasi selisih jam perangkat pengguna vs server

        this.initDOMElements();
        this.bindEvents();
    }

    initDOMElements() {
        this.gridEl = document.getElementById('seating-grid');
        this.btnCheckout = document.getElementById('btn-checkout');
        this.totalPriceEl = document.getElementById('total-price-display');
        this.selectedCountEl = document.getElementById('selected-count');
        this.timerContainer = document.getElementById('countdown-container');
        this.timerDisplay = document.getElementById('countdown-timer');
    }

    bindEvents() {
        if (this.btnCheckout) {
            this.btnCheckout.addEventListener('click', () => this.handleCheckout());
        }
    }

    /**
     * Hitung selisih jam klien dengan server untuk memastikan timer countdown presisi
     */
    syncServerTime(serverTimestampIso) {
        const serverTime = new Date(serverTimestampIso).getTime();
        const clientTime = Date.now();
        this.serverOffsetMs = serverTime - clientTime;
    }

    getSynchronizedNow() {
        return Date.now() + this.serverOffsetMs;
    }

    async handleCheckout() {
        if (this.selectedSeatIds.size === 0) return;

        this.btnCheckout.classList.add('loading');
        this.btnCheckout.disabled = true;

        try {
            const response = await fetch(`${this.apiBase}/checkout/create-transaction`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    showtime_id: this.showtimeId,
                    seat_ids: Array.from(this.selectedSeatIds)
                })
            });

            const data = await response.json();

            if (!response.ok) {
                alert(data.message || 'Gagal mengunci kursi. Silakan coba kembali.');
                location.reload();
                return;
            }

            // Sinkronkan jam dengan server dan jalankan countdown visual 10 menit
            this.syncServerTime(new Date().toISOString());
            this.startVisualCountdown(data.expires_in_seconds);

            // Munculkan antarmuka pembayaran Midtrans Snap
            window.snap.pay(data.snap_token, {
                onSuccess: (result) => {
                    clearInterval(this.timerInterval);
                    window.location.href = `/orders/${data.order_number}/success`;
                },
                onPending: (result) => {
                    console.log('Menunggu pembayaran pelanggan...', result);
                },
                onError: (error) => {
                    clearInterval(this.timerInterval);
                    alert('Pembayaran gagal atau ditolak bank penerbit.');
                    location.reload();
                },
                onClose: () => {
                    console.log('User menutup modal Snap tanpa menyelesaikan pembayaran.');
                }
            });

        } catch (err) {
            console.error('Checkout error:', err);
            alert('Terjadi gangguan koneksi internet.');
        } finally {
            this.btnCheckout.classList.remove('loading');
            this.btnCheckout.disabled = false;
        }
    }

    startVisualCountdown(durationSeconds) {
        if (this.timerContainer) {
            this.timerContainer.classList.remove('hidden');
        }

        const targetEndTime = this.getSynchronizedNow() + (durationSeconds * 1000);

        clearInterval(this.timerInterval);
        this.timerInterval = setInterval(() => {
            const remainingMs = targetEndTime - this.getSynchronizedNow();

            if (remainingMs <= 0) {
                clearInterval(this.timerInterval);
                if (this.timerDisplay) this.timerDisplay.textContent = '00:00';
                alert('Waktu pembayaran telah habis. Kursi Anda telah dilepas kembali ke publik.');
                window.location.reload();
                return;
            }

            const totalSec = Math.floor(remainingMs / 1000);
            const minutes = String(Math.floor(totalSec / 60)).padStart(2, '0');
            const seconds = String(totalSec % 60).padStart(2, '0');

            if (this.timerDisplay) {
                this.timerDisplay.textContent = `${minutes}:${seconds}`;
            }
        }, 1000);
    }
}
```
