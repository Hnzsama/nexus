<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Connection;
use PDO;

class CinemaSeeder
{
    public static function run(?PDO $pdo = null): void
    {
        $pdo ??= Connection::getPdo();

        // 1. Seed Cinemas
        $cinemas = [
            [
                'code' => 'CGV-GI',
                'name' => 'CGV Grand Indonesia',
                'city' => 'Jakarta Pusat',
                'address' => 'Jl. M.H. Thamrin No.1, Menteng, Jakarta Pusat',
            ],
            [
                'code' => 'XXI-SC',
                'name' => 'Cinema XXI Senayan City',
                'city' => 'Jakarta Selatan',
                'address' => 'Jl. Asia Afrika No.19, Gelora, Jakarta Selatan',
            ],
        ];

        foreach ($cinemas as $c) {
            $stmt = $pdo->prepare("SELECT id FROM cinemas WHERE code = ?");
            $stmt->execute([$c['code']]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare("
                    INSERT INTO cinemas (code, name, city, address, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 1, datetime('now'), datetime('now'))
                ");
                $ins->execute([$c['code'], $c['name'], $c['city'], $c['address']]);
            }
        }

        // Ambil cinema ID pertama
        $cinemaId = (int) $pdo->query("SELECT id FROM cinemas ORDER BY id ASC LIMIT 1")->fetchColumn();

        // 2. Seed Studios
        $studios = [
            ['name' => 'Studio 1 (IMAX)', 'studio_type' => 'IMAX', 'capacity' => 98],
            ['name' => 'Studio 2 (Deluxe)', 'studio_type' => 'DELUXE', 'capacity' => 98],
        ];

        foreach ($studios as $s) {
            $stmt = $pdo->prepare("SELECT id FROM studios WHERE cinema_id = ? AND name = ?");
            $stmt->execute([$cinemaId, $s['name']]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare("
                    INSERT INTO studios (cinema_id, name, studio_type, capacity, created_at, updated_at)
                    VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
                ");
                $ins->execute([$cinemaId, $s['name'], $s['studio_type'], $s['capacity']]);
            }
        }

        // Ambil studio ID
        $studioIds = $pdo->query("SELECT id FROM studios ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

        // 3. Seed Pricing Tiers
        $pricing = [
            ['code' => 'REG', 'name' => 'Regular Weekday', 'multiplier' => 1.00, 'surcharge' => 0.00],
            ['code' => 'WKD', 'name' => 'Weekend Special', 'multiplier' => 1.25, 'surcharge' => 10000.00],
            ['code' => 'IMX', 'name' => 'IMAX Experience', 'multiplier' => 1.50, 'surcharge' => 25000.00],
        ];
        foreach ($pricing as $p) {
            $stmt = $pdo->prepare("SELECT id FROM pricing_tiers WHERE code = ?");
            $stmt->execute([$p['code']]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare("
                    INSERT INTO pricing_tiers (code, name, multiplier, surcharge, description, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'Tier Tarif Resmi', 1, datetime('now'), datetime('now'))
                ");
                $ins->execute([$p['code'], $p['name'], $p['multiplier'], $p['surcharge']]);
            }
        }

        // 4. Seed Seats untuk Setiap Studio (Baris A-G, Nomor 1-14 = 98 Kursi)
        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($studioIds as $stId) {
            $cnt = (int) $pdo->query("SELECT count(*) FROM seats WHERE studio_id = {$stId}")->fetchColumn();
            if ($cnt === 0) {
                $seatIns = $pdo->prepare("
                    INSERT INTO seats (studio_id, row_label, seat_number, seat_type, is_active, created_at)
                    VALUES (?, ?, ?, ?, 1, datetime('now'))
                ");
                foreach ($rows as $row) {
                    for ($num = 1; $num <= 14; $num++) {
                        $type = in_array($row, ['F', 'G'], true) ? 'SWEETBOX' : 'STANDARD';
                        $seatIns->execute([$stId, $row, $num, $type]);
                    }
                }
            }
        }

        // 5. Seed Movies
        $movies = [
            [
                'title' => 'Avatar: The Way of Water',
                'slug' => 'avatar-the-way-of-water',
                'synopsis' => 'Jake Sully tinggal bersama keluarga barunya yang terbentuk di planet Pandora. Begitu ancaman yang akrab kembali untuk menyelesaikan apa yang dimulai sebelumnya, Jake harus bekerja sama dengan Neytiri dan tentara ras Na\'vi untuk melindungi planet mereka.',
                'duration_minutes' => 192,
                'age_rating' => '13+',
                'poster_url' => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=600&auto=format&fit=crop&q=80',
                'trailer_url' => 'https://www.youtube.com/watch?v=d9MyW72ELq0',
                'status' => 'NOW_PLAYING',
            ],
            [
                'title' => 'Oppenheimer',
                'slug' => 'oppenheimer',
                'synopsis' => 'Kisah ilmuwan Amerika J. Robert Oppenheimer dan perannya dalam pengembangan bom atom pada Proyek Manhattan.',
                'duration_minutes' => 180,
                'age_rating' => '17+',
                'poster_url' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=600&auto=format&fit=crop&q=80',
                'trailer_url' => 'https://www.youtube.com/watch?v=uYPbbksJxIg',
                'status' => 'NOW_PLAYING',
            ],
            [
                'title' => 'Spider-Man: Across the Spider-Verse',
                'slug' => 'spider-man-across-the-spider-verse',
                'synopsis' => 'Miles Morales terlempar melintasi Multiverse, di mana ia bertemu dengan tim Spider-People yang bertugas melindungi keberadaannya.',
                'duration_minutes' => 140,
                'age_rating' => 'SU',
                'poster_url' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=600&auto=format&fit=crop&q=80',
                'trailer_url' => 'https://www.youtube.com/watch?v=cqGjhVJWtEg',
                'status' => 'NOW_PLAYING',
            ],
        ];

        foreach ($movies as $m) {
            $stmt = $pdo->prepare("SELECT id FROM movies WHERE slug = ?");
            $stmt->execute([$m['slug']]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare("
                    INSERT INTO movies (title, slug, synopsis, duration_minutes, age_rating, poster_url, trailer_url, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
                ");
                $ins->execute([
                    $m['title'], $m['slug'], $m['synopsis'], $m['duration_minutes'],
                    $m['age_rating'], $m['poster_url'], $m['trailer_url'], $m['status']
                ]);
            }
        }

        // 6. Seed Showtimes
        $movieIds = $pdo->query("SELECT id FROM movies ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
        $firstStudio = (int) ($studioIds[0] ?? 1);
        $secondStudio = (int) ($studioIds[1] ?? $firstStudio);

        $slots = [
            ['time' => '13:00:00', 'price' => 50000.00],
            ['time' => '16:30:00', 'price' => 55000.00],
            ['time' => '20:00:00', 'price' => 60000.00],
        ];

        $today = date('Y-m-d');
        foreach ($movieIds as $idx => $mId) {
            $stTarget = ($idx % 2 === 0) ? $firstStudio : $secondStudio;
            foreach ($slots as $slot) {
                $startTime = $today . ' ' . $slot['time'];
                $endTime = date('Y-m-d H:i:s', strtotime($startTime) + 7200);

                $check = $pdo->prepare("SELECT id FROM showtimes WHERE movie_id = ? AND studio_id = ? AND start_time = ?");
                $check->execute([$mId, $stTarget, $startTime]);
                if (!$check->fetch()) {
                    $ins = $pdo->prepare("
                        INSERT INTO showtimes (movie_id, studio_id, start_time, end_time, base_price, status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, 'ACTIVE', datetime('now'), datetime('now'))
                    ");
                    $ins->execute([$mId, $stTarget, $startTime, $endTime, $slot['price']]);
                }
            }
        }
    }
}
