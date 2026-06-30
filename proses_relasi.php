<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. KONFIGURASI KONEKSI DATABASE
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Koneksi database gagal: " . $e->getMessage();
    exit;
}

// 2. CEK APAKAH ADA PERINTAH AKSES (POST ACTION)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        // --- BLOK LOGIKA GABUNGAN: BUILDINGS (GEDUNG) ---
        if ($action === 'add_building') {
            $nama = trim($_POST['nama'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO buildings (nama, alamat, status) VALUES (?, ?, 1)");
                $stmt->execute([$nama, $alamat]);
            }
        }
        if ($action === 'edit_building') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("UPDATE buildings SET nama = ?, alamat = ? WHERE id = ?");
                $stmt->execute([$nama, $alamat, $id]);
            }
        }
        if ($action === 'delete_building') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM buildings WHERE id = ?")->execute([$id]);
        }

        // --- BLOK LOGIKA GABUNGAN: FLOORS (LANTAI) ---
        if ($action === 'add_floor') {
            $nama = trim($_POST['nama'] ?? '');
            $building_id = (int)$_POST['building_id'];
            if ($nama !== '' && $building_id > 0) {
                $stmt = $conn->prepare("INSERT INTO floors (nama, building_id, status) VALUES (?, ?, 1)");
                $stmt->execute([$nama, $building_id]);
            }
        }
        if ($action === 'edit_floor') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $building_id = (int)$_POST['building_id'];
            if ($nama !== '' && $building_id > 0) {
                $stmt = $conn->prepare("UPDATE floors SET nama = ?, building_id = ? WHERE id = ?");
                $stmt->execute([$nama, $building_id, $id]);
            }
        }
        if ($action === 'delete_floor') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM floors WHERE id = ?")->execute([$id]);
        }

        // --- BLOK LOGIKA GABUNGAN: ROOMS (RUANGAN) ---
        if ($action === 'add_room') {
            $nama = trim($_POST['nama'] ?? '');
            $floor_id = (int)$_POST['floor_id'];
            $kode = trim($_POST['kode'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            
            if ($nama !== '' && $floor_id > 0) {
                $stmt = $conn->prepare("INSERT INTO rooms (nama, floor_id, kode, telepon, status) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$nama, $floor_id, $kode, $telepon]);
            }
        }
        if ($action === 'edit_room') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $floor_id = (int)$_POST['floor_id'];
            $kode = trim($_POST['kode'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            
            if ($nama !== '' && $floor_id > 0) {
                $stmt = $conn->prepare("UPDATE rooms SET nama = ?, floor_id = ?, kode = ?, telepon = ? WHERE id = ?");
                $stmt->execute([$nama, $floor_id, $kode, $telepon, $id]);
            }
        }
        if ($action === 'delete_room') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
        }

        // KABARKAN KE BROWSER JIKA PROSES BERHASIL
        http_response_code(200);
        echo "Sukses";
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database Error: " . $e->getMessage();
        exit;
    }
}
