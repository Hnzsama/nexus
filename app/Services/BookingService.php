<?php

declare(strict_types=1);

namespace App\Services;

use Core\Database\Connection;
use Exception;
use PDO;
use RuntimeException;

/**
 * Class BookingService
 * 
 * Concurrency Control, Pessimistic Row Locking & Anti-Deadlock Booking Engine.
 * Memenuhi spesifikasi Bab 2, 4, dan 5.
 */
class BookingService
{
    public const HOLD_DURATION_SECONDS = 600; // 10 Menit TTL
    public const MIDTRANS_EXPIRY_BUFFER_SECONDS = 60; // 60 Detik Asymmetric Buffer

    /**
     * Dapatkan peta denah seluruh kursi pada studio jadwal tayang tertentu
     * dengan kalkulasi status ketersediaan dinamis & Lazy Expiry Checking.
     *
     * @param int $showtimeId
     * @param int|null $currentUserId
     * @return array<string, mixed>
     */
    public function getShowtimeSeatLayout(int $showtimeId, ?int $currentUserId = null): array
    {
        $pdo = Connection::getPdo();

        // 1. Ambil detail showtime, movie, dan studio
        $stStmt = $pdo->prepare("
            SELECT st.*, m.title as movie_title, m.poster_url, m.age_rating,
                   s.name as studio_name, s.studio_type, s.capacity,
                   c.name as cinema_name, c.city
            FROM showtimes st
            JOIN movies m ON m.id = st.movie_id
            JOIN studios s ON s.id = st.studio_id
            JOIN cinemas c ON c.id = s.cinema_id
            WHERE st.id = ?
        ");
        $stStmt->execute([$showtimeId]);
        $showtime = $stStmt->fetch(PDO::FETCH_ASSOC);

        if (!$showtime) {
            throw new RuntimeException("Jadwal tayang dengan ID {$showtimeId} tidak ditemukan.");
        }

        $studioId = (int) $showtime['studio_id'];

        // 2. Ambil master seluruh kursi fisik di studio (DILARANG baca kolom status di sini)
        $seatStmt = $pdo->prepare("
            SELECT id, studio_id, row_label, seat_number, seat_type, is_active
            FROM seats
            WHERE studio_id = ? AND is_active = 1
            ORDER BY row_label ASC, seat_number ASC
        ");
        $seatStmt->execute([$studioId]);
        $seats = $seatStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Ambil daftar kursi yang sudah TERJUAL / BOOKED (orders.status = 'PAID')
        $bookedStmt = $pdo->prepare("
            SELECT oi.seat_id
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.showtime_id = ? AND o.status = 'PAID'
        ");
        $bookedStmt->execute([$showtimeId]);
        $bookedSeatIds = array_fill_keys($bookedStmt->fetchAll(PDO::FETCH_COLUMN), true);

        // 4. Ambil daftar kursi yang sedang di-HOLD aktif (Lazy Expiry: status = 'HELD' AND expires_at > datetime('now'))
        // Dukung fungsi CURRENT_TIMESTAMP / NOW() kompatibel di SQLite & MySQL
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = $driver === 'sqlite' ? "datetime('now')" : "NOW()";

        $holdStmt = $pdo->prepare("
            SELECT seat_id, user_id, expires_at,
                   CAST((strftime('%s', expires_at) - strftime('%s', 'now')) AS INT) as remaining_seconds
            FROM seat_holds
            WHERE showtime_id = ? AND status = 'HELD' AND expires_at > {$nowExpr}
        ");
        $holdStmt->execute([$showtimeId]);
        $activeHolds = [];
        while ($row = $holdStmt->fetch(PDO::FETCH_ASSOC)) {
            $activeHolds[(int) $row['seat_id']] = $row;
        }

        // 5. Petakan status visual untuk setiap kursi
        $formattedSeats = [];
        $totalBooked = 0;
        $totalHeld = 0;
        $totalAvailable = 0;

        foreach ($seats as $s) {
            $sId = (int) $s['id'];
            $status = 'AVAILABLE';
            $isMine = false;
            $remainingSeconds = 0;

            if (isset($bookedSeatIds[$sId])) {
                $status = 'BOOKED'; // Terjual (Merah / Disabled)
                $totalBooked++;
            } elseif (isset($activeHolds[$sId])) {
                $hold = $activeHolds[$sId];
                $status = 'HELD'; // Sedang dikunci sementara (Kuning)
                $totalHeld++;
                if ($currentUserId !== null && (int) $hold['user_id'] === $currentUserId) {
                    $isMine = true;
                }
                $remainingSeconds = max(0, (int) ($hold['remaining_seconds'] ?? 0));
            } else {
                $totalAvailable++;
            }

            $formattedSeats[] = [
                'id' => $sId,
                'row' => $s['row_label'],
                'number' => (int) $s['seat_number'],
                'code' => $s['row_label'] . $s['seat_number'],
                'type' => $s['seat_type'],
                'status' => $status,
                'is_mine' => $isMine,
                'remaining_seconds' => $remainingSeconds,
            ];
        }

        return [
            'showtime' => [
                'id' => (int) $showtime['id'],
                'movie_title' => $showtime['movie_title'],
                'poster_url' => $showtime['poster_url'],
                'age_rating' => $showtime['age_rating'],
                'cinema_name' => $showtime['cinema_name'],
                'city' => $showtime['city'],
                'studio_name' => $showtime['studio_name'],
                'studio_type' => $showtime['studio_type'],
                'start_time' => $showtime['start_time'],
                'base_price' => (float) $showtime['base_price'],
            ],
            'summary' => [
                'total_seats' => count($seats),
                'available' => $totalAvailable,
                'held' => $totalHeld,
                'booked' => $totalBooked,
            ],
            'seats' => $formattedSeats,
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Kunci Kursi secara Atomik dengan Pessimistic Locking & Canonical Sorting.
     * Mengeliminasi kemungkinan Deadlock (InnoDB Error 1213) dan Double-Booking.
     *
     * @param int $showtimeId
     * @param array<int> $seatIds
     * @param int $userId
     * @param array{name: string, email: string, phone: string} $customer
     * @return array{order_number: string, snap_token: string, redirect_url: string, expires_at: string, total_amount: float}
     * @throws Exception Jika validasi gagal atau terjadi konflik kursi
     */
    public function lockSeatsAndCreateOrder(
        int $showtimeId,
        array $seatIds,
        int $userId,
        array $customer
    ): array {
        if ($showtimeId <= 0 || empty($seatIds)) {
            throw new Exception("Jadwal tayang atau daftar kursi tidak boleh kosong.", 422);
        }

        if (count($seatIds) > 6) {
            throw new Exception("Maksimal pemesanan adalah 6 kursi per transaksi.", 422);
        }

        // =========================================================================
        // ATURAN 1: CANONICAL SORTING ORDER (seat_ids ASC)
        // Mencegah siklus Deadlock (InnoDB 1213) jika ada transaksi paralel
        // =========================================================================
        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));
        sort($seatIds, SORT_NUMERIC);
        $seatCount = count($seatIds);

        $pdo = Connection::getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Jika MySQL: potong antrean transaksi yang tertahan (Bab 4)
        if ($driver === 'mysql') {
            try {
                $pdo->exec("SET innodb_lock_wait_timeout = 3;");
            } catch (\Throwable) {}
        }

        // Buka Transaksi Database Eksklusif
        $pdo->beginTransaction();

        try {
            // =========================================================================
            // ATURAN 2: QUERY PENGUNCIAN BARIS EKSPLISIT (SELECT ... FOR UPDATE)
            // =========================================================================
            $inPlaceholders = implode(',', array_fill(0, $seatCount, '?'));
            $lockSql = "
                SELECT id, row_label, seat_number, seat_type, studio_id 
                FROM seats 
                WHERE id IN ({$inPlaceholders}) AND is_active = 1 
                ORDER BY id ASC
            ";
            if ($driver !== 'sqlite') {
                $lockSql .= " FOR UPDATE";
            }

            $seatStmt = $pdo->prepare($lockSql);
            $seatStmt->execute($seatIds);
            $lockedSeats = $seatStmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($lockedSeats) !== $seatCount) {
                $pdo->rollBack();
                throw new Exception("Sebagian kursi yang dipilih tidak ditemukan atau non-aktif.", 404);
            }

            // =========================================================================
            // ATURAN 3: DOUBLE-CHECK CONFLICT - Validasi Kursi Belum Terjual (PAID)
            // =========================================================================
            $bookedSql = "
                SELECT oi.seat_id 
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.showtime_id = ? 
                  AND oi.seat_id IN ({$inPlaceholders})
                  AND o.status = 'PAID'
            ";
            if ($driver !== 'sqlite') {
                $bookedSql .= " FOR UPDATE";
            }

            $bookedStmt = $pdo->prepare($bookedSql);
            $bookedStmt->execute(array_merge([$showtimeId], $seatIds));
            if ($bookedStmt->rowCount() > 0) {
                $pdo->rollBack();
                throw new Exception("Maaf, kursi yang Anda pilih baru saja terjual kepada pengguna lain.", 409);
            }

            // =========================================================================
            // ATURAN 4: DOUBLE-CHECK CONFLICT - Validasi Kursi Tidak Di-HOLD Aktif
            // =========================================================================
            $nowExpr = $driver === 'sqlite' ? "datetime('now')" : "NOW()";
            $holdSql = "
                SELECT seat_id, user_id 
                FROM seat_holds 
                WHERE showtime_id = ? 
                  AND seat_id IN ({$inPlaceholders})
                  AND status = 'HELD' 
                  AND expires_at > {$nowExpr}
            ";
            if ($driver !== 'sqlite') {
                $holdSql .= " FOR UPDATE";
            }

            $holdStmt = $pdo->prepare($holdSql);
            $holdStmt->execute(array_merge([$showtimeId], $seatIds));
            $existingHold = $holdStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingHold) {
                $pdo->rollBack();
                throw new Exception("Maaf, kursi sedang dipilih oleh pelanggan lain. Silakan pilih kursi lain.", 409);
            }

            // Ambil base price jadwal tayang
            $stQuery = "SELECT base_price FROM showtimes WHERE id = ?";
            if ($driver !== 'sqlite') {
                $stQuery .= " FOR UPDATE";
            }
            $stStmt = $pdo->prepare($stQuery);
            $stStmt->execute([$showtimeId]);
            $basePrice = (float) $stStmt->fetchColumn();
            if ($basePrice <= 0) {
                $basePrice = 50000.00;
            }

            $totalAmount = $basePrice * $seatCount;

            // =========================================================================
            // ATURAN 5: HOLD DURATION TEPAT 10 MENIT (600 DETIK) DI DATABASE
            // =========================================================================
            $orderNumber = 'NX-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $expiresAt = date('Y-m-d H:i:s', time() + self::HOLD_DURATION_SECONDS);

            // Buat entri Order
            $orderInsert = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, showtime_id, total_seats, total_amount, status, expires_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'UNPAID', ?, datetime('now'), datetime('now'))
            ");
            if ($driver !== 'sqlite') {
                $orderInsert = $pdo->prepare("
                    INSERT INTO orders (order_number, user_id, showtime_id, total_seats, total_amount, status, expires_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'UNPAID', ?, NOW(), NOW())
                ");
            }
            $orderInsert->execute([$orderNumber, $userId, $showtimeId, $seatCount, $totalAmount, $expiresAt]);
            $orderId = (int) $pdo->lastInsertId();

            // Buat entri OrderItems & SeatHolds
            $itemInsert = $pdo->prepare("
                INSERT INTO order_items (order_id, seat_id, price, created_at)
                VALUES (?, ?, ?, datetime('now'))
            ");
            $holdInsert = $pdo->prepare("
                INSERT INTO seat_holds (showtime_id, seat_id, order_id, user_id, status, held_at, expires_at)
                VALUES (?, ?, ?, ?, 'HELD', datetime('now'), ?)
            ");

            if ($driver !== 'sqlite') {
                $itemInsert = $pdo->prepare("INSERT INTO order_items (order_id, seat_id, price, created_at) VALUES (?, ?, ?, NOW())");
                $holdInsert = $pdo->prepare("INSERT INTO seat_holds (showtime_id, seat_id, order_id, user_id, status, held_at, expires_at) VALUES (?, ?, ?, ?, 'HELD', NOW(), ?)");
            }

            $itemDetails = [];
            foreach ($lockedSeats as $seat) {
                $sId = (int) $seat['id'];
                $itemInsert->execute([$orderId, $sId, $basePrice]);
                $holdInsert->execute([$showtimeId, $sId, $orderId, $userId, $expiresAt]);

                $itemDetails[] = [
                    'id' => (string) $sId,
                    'price' => $basePrice,
                    'quantity' => 1,
                    'name' => 'Kursi ' . $seat['row_label'] . $seat['seat_number'],
                ];
            }

            // =========================================================================
            // ATURAN 6: REQUEST MIDTRANS SNAP TOKEN DENGAN SINKRONISASI ASIMETRIS (540s)
            // =========================================================================
            $midtrans = new MidtransService();
            $snapResult = $midtrans->createSnapTransaction($orderNumber, $totalAmount, $itemDetails, $customer);

            // Simpan catatan pembayaran
            $payInsert = $pdo->prepare("
                INSERT INTO payments (order_id, snap_token, gross_amount, transaction_status, created_at, updated_at)
                VALUES (?, ?, ?, 'pending', datetime('now'), datetime('now'))
            ");
            if ($driver !== 'sqlite') {
                $payInsert = $pdo->prepare("
                    INSERT INTO payments (order_id, snap_token, gross_amount, transaction_status, created_at, updated_at)
                    VALUES (?, ?, ?, 'pending', NOW(), NOW())
                ");
            }
            $payInsert->execute([$orderId, $snapResult['token'], $totalAmount]);

            // Commit seluruh transaksi jika semua tahapan berhasil
            $pdo->commit();

            return [
                'order_number' => $orderNumber,
                'snap_token' => $snapResult['token'],
                'redirect_url' => $snapResult['redirect_url'],
                'expires_at' => $expiresAt,
                'expires_in_seconds' => self::HOLD_DURATION_SECONDS,
                'total_amount' => $totalAmount,
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Sapu Bersih Hold Kedaluwarsa (Layer 2 Scheduled Background Worker).
     * Sesuai Bab 4: php nexus seats:cleanup
     *
     * @return array{released_holds: int, expired_orders: int}
     */
    public function cleanupExpiredHolds(): array
    {
        $pdo = Connection::getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = $driver === 'sqlite' ? "datetime('now')" : "NOW()";

        // 1. Lepas hold yang telah melewati expires_at
        $releaseStmt = $pdo->prepare("
            UPDATE seat_holds 
            SET status = 'RELEASED' 
            WHERE status = 'HELD' AND expires_at <= {$nowExpr}
        ");
        $releaseStmt->execute();
        $releasedCount = $releaseStmt->rowCount();

        // 2. Ubah order tertunggak yang belum dibayar menjadi EXPIRED
        $expireStmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'EXPIRED' 
            WHERE status = 'UNPAID' AND expires_at <= {$nowExpr}
        ");
        $expireStmt->execute();
        $expiredCount = $expireStmt->rowCount();

        return [
            'released_holds' => $releasedCount,
            'expired_orders' => $expiredCount,
        ];
    }
}
