<?php

declare(strict_types=1);

namespace App\Services;

use Core\Database\Connection;
use Exception;
use PDO;

/**
 * Class OrderStateMachine
 * 
 * State Machine & Order Status Transition Engine.
 * Menangani transisi state transaksi, penerbitan tiket, dan jaminan Idempotensi.
 * Memenuhi spesifikasi Bab 3 dan Bab 5.
 */
class OrderStateMachine
{
    /**
     * Proses notifikasi status transaksi dari Midtrans secara atomik dan idempoten.
     *
     * @param string $orderNumber
     * @param string $transactionStatus Midtrans: settlement, capture, pending, deny, expire, cancel
     * @param string|null $fraudStatus Midtrans: accept, challenge, deny
     * @param array<string, mixed> $rawPayload Seluruh payload webhook mentah
     * @param string|null $clientIp IP pengirim webhook
     * @return array{status: string, message: string, order_status: string, idempotent: bool}
     * @throws Exception
     */
    public function processMidtransNotification(
        string $orderNumber,
        string $transactionStatus,
        ?string $fraudStatus,
        array $rawPayload,
        ?string $clientIp = null
    ): array {
        $pdo = Connection::getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $nowExpr = $driver === 'sqlite' ? "datetime('now')" : "NOW()";

        // Simpan audit log awal ke tabel payment_logs
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO payment_logs (order_number, event_type, status_code, raw_payload, ip_address, signature_valid, created_at)
                VALUES (?, 'webhook', ?, ?, ?, 1, {$nowExpr})
            ");
            $logStmt->execute([
                $orderNumber,
                (string) ($rawPayload['status_code'] ?? '200'),
                json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $clientIp,
            ]);
        } catch (\Throwable) {}

        // Buka Transaksi Database
        $pdo->beginTransaction();

        try {
            // 1. Kunci baris order dengan Pessimistic Lock (SELECT ... FOR UPDATE)
            $orderSql = "SELECT * FROM orders WHERE order_number = ?";
            if ($driver !== 'sqlite') {
                $orderSql .= " FOR UPDATE";
            }

            $orderStmt = $pdo->prepare($orderSql);
            $orderStmt->execute([$orderNumber]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $pdo->rollBack();
                throw new Exception("Order dengan nomor '{$orderNumber}' tidak ditemukan.", 404);
            }

            $orderId = (int) $order['id'];
            $currentStatus = (string) $order['status'];

            // =========================================================================
            // 2. IDEMPOTENCY GUARD
            // Jika webhook dikirim ulang untuk order yang sudah PAID, langsung kembalikan 200
            // tanpa duplikasi pembuatan tiket!
            // =========================================================================
            if ($currentStatus === 'PAID') {
                $pdo->commit();
                return [
                    'status' => 'success',
                    'message' => 'Order sudah berstatus PAID sebelumnya (Idempotent Hit).',
                    'order_status' => 'PAID',
                    'idempotent' => true,
                ];
            }

            // Perbarui audit trail di tabel payments
            $midtransTxId = (string) ($rawPayload['transaction_id'] ?? '');
            $paymentType = (string) ($rawPayload['payment_type'] ?? '');
            $settlementTime = (string) ($rawPayload['settlement_time'] ?? date('Y-m-d H:i:s'));

            $payUpdate = $pdo->prepare("
                UPDATE payments 
                SET midtrans_transaction_id = ?,
                    payment_type = ?,
                    transaction_status = ?,
                    fraud_status = ?,
                    settlement_time = ?,
                    raw_response_json = ?,
                    updated_at = {$nowExpr}
                WHERE order_id = ?
            ");
            $payUpdate->execute([
                $midtransTxId,
                $paymentType,
                $transactionStatus,
                $fraudStatus,
                $settlementTime,
                json_encode($rawPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $orderId,
            ]);

            // =========================================================================
            // 3. LOGIKA SUKSES (PAID)
            // =========================================================================
            if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
                // A. Ubah status orders menjadi PAID
                $updateOrder = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'PAID', paid_at = {$nowExpr}, updated_at = {$nowExpr} 
                    WHERE id = ?
                ");
                $updateOrder->execute([$orderId]);

                // B. Ubah status seat_holds menjadi CONVERTED (Resmi Terjual)
                $updateHolds = $pdo->prepare("
                    UPDATE seat_holds 
                    SET status = 'CONVERTED' 
                    WHERE order_id = ?
                ");
                $updateHolds->execute([$orderId]);

                // C. Terbitkan Tiket dan QR Token untuk setiap kursi
                $itemsStmt = $pdo->prepare("
                    SELECT oi.id, oi.seat_id, s.row_label, s.seat_number 
                    FROM order_items oi
                    JOIN seats s ON s.id = oi.seat_id
                    WHERE oi.order_id = ?
                ");
                $itemsStmt->execute([$orderId]);
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                $ticketInsert = $pdo->prepare("
                    INSERT INTO tickets (order_item_id, ticket_code, qr_token, checkin_status, created_at)
                    VALUES (?, ?, ?, 'UNCLAIMED', {$nowExpr})
                ");

                foreach ($items as $item) {
                    $row = (string) $item['row_label'];
                    $num = (int) $item['seat_number'];
                    $ticketCode = 'TCK-' . $row . $num . '-' . strtoupper(bin2hex(random_bytes(3)));
                    $qrToken = hash('sha256', $ticketCode . $orderNumber . bin2hex(random_bytes(16)));

                    $ticketInsert->execute([$item['id'], $ticketCode, $qrToken]);
                }

                $pdo->commit();

                return [
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil diselesaikan dan tiket telah diterbitkan.',
                    'order_status' => 'PAID',
                    'idempotent' => false,
                ];
            }

            // =========================================================================
            // 4. LOGIKA GAGAL / BATAL / KEDALUWARSA (CANCELLED / EXPIRED)
            // =========================================================================
            if (in_array($transactionStatus, ['expire', 'cancel', 'deny'], true)) {
                $newStatus = ($transactionStatus === 'expire') ? 'EXPIRED' : 'CANCELLED';

                // A. Ubah status order menjadi EXPIRED / CANCELLED
                $updateOrder = $pdo->prepare("
                    UPDATE orders 
                    SET status = ?, cancelled_at = {$nowExpr}, updated_at = {$nowExpr} 
                    WHERE id = ?
                ");
                $updateOrder->execute([$newStatus, $orderId]);

                // B. Segera lepaskan status seat_holds menjadi RELEASED agar kursi dapat dipesan orang lain!
                $releaseHolds = $pdo->prepare("
                    UPDATE seat_holds 
                    SET status = 'RELEASED' 
                    WHERE order_id = ?
                ");
                $releaseHolds->execute([$orderId]);

                $pdo->commit();

                return [
                    'status' => 'success',
                    'message' => "Order {$orderNumber} ditandai sebagai {$newStatus} dan kursi berhasil dilepaskan.",
                    'order_status' => $newStatus,
                    'idempotent' => false,
                ];
            }

            // Status pending / transaksi baru
            $pdo->commit();

            return [
                'status' => 'success',
                'message' => "Status transaksi diterima: {$transactionStatus}.",
                'order_status' => 'UNPAID',
                'idempotent' => false,
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
