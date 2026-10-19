<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateCinemaSystemTables
 * 
 * Migrasi arsitektur 11 tabel relasional InnoDB untuk Cinema Ticketing & Concurrency Engine.
 * Sesuai Dokumen Arsitektur Bab 2.
 */
class CreateCinemaSystemTables extends Migration
{
    public function up(PDO $pdo): void
    {
        // 1. Tambah kolom phone ke users jika belum ada
        try {
            $this->execute("ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL DEFAULT '';", $pdo);
        } catch (\Throwable $e) {
            // Abaikan jika kolom sudah ada
        }

        // 2. Master: cinemas
        $this->execute("
            CREATE TABLE IF NOT EXISTS cinemas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                city VARCHAR(100) NOT NULL,
                address TEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_cinemas_city ON cinemas (city);", $pdo);

        // 3. Master: studios
        $this->execute("
            CREATE TABLE IF NOT EXISTS studios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cinema_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                studio_type VARCHAR(50) NOT NULL DEFAULT 'REGULAR',
                capacity INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_studios_cinema FOREIGN KEY (cinema_id) REFERENCES cinemas (id) ON DELETE CASCADE
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_studios_cinema ON studios (cinema_id);", $pdo);

        // 4. Master: pricing_tiers
        $this->execute("
            CREATE TABLE IF NOT EXISTS pricing_tiers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                multiplier DECIMAL(5, 2) NOT NULL DEFAULT 1.00,
                surcharge DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);

        // 5. Master: seats (Dilarang status LOCKED/BOOKED di sini demi integritas kardinalitas)
        $this->execute("
            CREATE TABLE IF NOT EXISTS seats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                studio_id INT NOT NULL,
                row_label VARCHAR(5) NOT NULL,
                seat_number INT NOT NULL,
                seat_type VARCHAR(50) NOT NULL DEFAULT 'STANDARD',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uk_studio_seat UNIQUE (studio_id, row_label, seat_number),
                CONSTRAINT fk_seats_studio FOREIGN KEY (studio_id) REFERENCES studios (id) ON DELETE CASCADE
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_seats_studio ON seats (studio_id, is_active);", $pdo);

        // 6. Master: movies
        $this->execute("
            CREATE TABLE IF NOT EXISTS movies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                synopsis TEXT NULL,
                duration_minutes INT NOT NULL,
                age_rating VARCHAR(10) NOT NULL DEFAULT '13+',
                poster_url VARCHAR(500) NULL,
                trailer_url VARCHAR(500) NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'NOW_PLAYING',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_movies_status ON movies (status);", $pdo);

        // 7. Master/Schedule: showtimes
        // Composite Index berkinerja tinggi: showtimes(movie_id, studio_id, start_time)
        $this->execute("
            CREATE TABLE IF NOT EXISTS showtimes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                movie_id INT NOT NULL,
                studio_id INT NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                base_price DECIMAL(12, 2) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_showtimes_movie FOREIGN KEY (movie_id) REFERENCES movies (id) ON DELETE RESTRICT,
                CONSTRAINT fk_showtimes_studio FOREIGN KEY (studio_id) REFERENCES studios (id) ON DELETE RESTRICT
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_showtimes_movie_studio_time ON showtimes (movie_id, studio_id, start_time);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_showtimes_schedule ON showtimes (studio_id, start_time);", $pdo);

        // 8. Transaksional: orders
        $this->execute("
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(64) NOT NULL UNIQUE,
                user_id INT NOT NULL,
                showtime_id INT NOT NULL,
                total_seats INT NOT NULL,
                total_amount DECIMAL(12, 2) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'UNPAID',
                expires_at DATETIME NOT NULL,
                paid_at DATETIME NULL,
                cancelled_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
                CONSTRAINT fk_orders_showtime FOREIGN KEY (showtime_id) REFERENCES showtimes (id) ON DELETE RESTRICT
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_user ON orders (user_id);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_showtime ON orders (showtime_id);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_status_expires ON orders (status, expires_at);", $pdo);

        // 9. Transaksional: order_items
        // Composite Index berkinerja tinggi: order_items(order_id, seat_id)
        $this->execute("
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                seat_id INT NOT NULL,
                price DECIMAL(12, 2) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uk_order_seat UNIQUE (order_id, seat_id),
                CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_order_items_seat FOREIGN KEY (seat_id) REFERENCES seats (id) ON DELETE RESTRICT
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_order_items_order_seat ON order_items (order_id, seat_id);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_order_items_seat ON order_items (seat_id);", $pdo);

        // 10. Transaksional: seat_holds (Kritis untuk Concurrency Lock)
        // Composite Index berkinerja tinggi: seat_holds(showtime_id, seat_id, status)
        $this->execute("
            CREATE TABLE IF NOT EXISTS seat_holds (
                id INT AUTO_INCREMENT PRIMARY KEY,
                showtime_id INT NOT NULL,
                seat_id INT NOT NULL,
                order_id INT NOT NULL,
                user_id INT NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'HELD',
                held_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                CONSTRAINT fk_holds_showtime FOREIGN KEY (showtime_id) REFERENCES showtimes (id) ON DELETE CASCADE,
                CONSTRAINT fk_holds_seat FOREIGN KEY (seat_id) REFERENCES seats (id) ON DELETE CASCADE,
                CONSTRAINT fk_holds_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_holds_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_seat_holds_composite ON seat_holds (showtime_id, seat_id, status);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_active_holds ON seat_holds (showtime_id, seat_id, status, expires_at);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_order_holds ON seat_holds (order_id);", $pdo);

        // 11. Transaksional: payments & payment_logs (Audit Trail Midtrans)
        $this->execute("
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL UNIQUE,
                snap_token VARCHAR(255) NULL,
                payment_type VARCHAR(50) NULL,
                transaction_status VARCHAR(50) NOT NULL DEFAULT 'pending',
                fraud_status VARCHAR(50) NULL,
                midtrans_transaction_id VARCHAR(100) NULL,
                gross_amount DECIMAL(12, 2) NOT NULL,
                settlement_time DATETIME NULL,
                raw_response_json TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_payments_midtrans_tx ON payments (midtrans_transaction_id);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_payments_status ON payments (transaction_status);", $pdo);

        $this->execute("
            CREATE TABLE IF NOT EXISTS payment_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(100) NOT NULL,
                event_type VARCHAR(50) NOT NULL DEFAULT 'webhook',
                status_code VARCHAR(20) NULL,
                raw_payload TEXT NOT NULL,
                ip_address VARCHAR(50) NULL,
                signature_valid TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_payment_logs_order ON payment_logs (order_number);", $pdo);

        // 12. Transaksional: tickets
        $this->execute("
            CREATE TABLE IF NOT EXISTS tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_item_id INT NOT NULL UNIQUE,
                ticket_code VARCHAR(50) NOT NULL UNIQUE,
                qr_token VARCHAR(255) NOT NULL UNIQUE,
                checkin_status VARCHAR(50) NOT NULL DEFAULT 'UNCLAIMED',
                checked_in_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_tickets_order_item FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE RESTRICT
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_tickets_code ON tickets (ticket_code);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_tickets_qr ON tickets (qr_token);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS tickets;", $pdo);
        $this->execute("DROP TABLE IF EXISTS payment_logs;", $pdo);
        $this->execute("DROP TABLE IF EXISTS payments;", $pdo);
        $this->execute("DROP TABLE IF EXISTS seat_holds;", $pdo);
        $this->execute("DROP TABLE IF EXISTS order_items;", $pdo);
        $this->execute("DROP TABLE IF EXISTS orders;", $pdo);
        $this->execute("DROP TABLE IF EXISTS showtimes;", $pdo);
        $this->execute("DROP TABLE IF EXISTS movies;", $pdo);
        $this->execute("DROP TABLE IF EXISTS seats;", $pdo);
        $this->execute("DROP TABLE IF EXISTS pricing_tiers;", $pdo);
        $this->execute("DROP TABLE IF EXISTS studios;", $pdo);
        $this->execute("DROP TABLE IF EXISTS cinemas;", $pdo);
    }
}
