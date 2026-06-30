<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. KONFIGURASI KONEKSI DATABASE UTAMA
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
        // --- LOGIKA: ASSET BRANDS ---
        if ($action === 'add_brand') {
            $nama = trim($_POST['nama'] ?? '');
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO asset_brands (nama, status) VALUES (?, ?)");
                $stmt->execute([$nama, $status]);
            }
        }
        if ($action === 'edit_brand') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $status = (int)$_POST['status'];
            if ($nama !== '') {
                $stmt = $conn->prepare("UPDATE asset_brands SET nama = ?, status = ? WHERE id = ?");
                $stmt->execute([$nama, $status, $id]);
            }
        }
        if ($action === 'delete_brand') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM asset_brands WHERE id = ?")->execute([$id]);
        }

        // --- LOGIKA: ASSET CATEGORIES ---
        if ($action === 'add_category') {
            $nama = trim($_POST['nama'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $warna = trim($_POST['warna'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO asset_categories (nama, icon, warna, status) VALUES (?, ?, ?, 1)");
                $stmt->execute([$nama, $icon, $warna]);
            }
        }
        if ($action === 'edit_category') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $warna = trim($_POST['warna'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("UPDATE asset_categories SET nama = ?, icon = ?, warna = ? WHERE id = ?");
                $stmt->execute([$nama, $icon, $warna, $id]);
            }
        }
        if ($action === 'delete_category') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM asset_categories WHERE id = ?")->execute([$id]);
        }

        // --- LOGIKA: ASSET STATUSES ---
        if ($action === 'add_status') {
            $nama = trim($_POST['nama'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO asset_statuses (nama) VALUES (?)");
                $stmt->execute([$nama]);
            }
        }
        if ($action === 'edit_status') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("UPDATE asset_statuses SET nama = ? WHERE id = ?");
                $stmt->execute([$nama, $id]);
            }
        }
        if ($action === 'delete_status') {
            $id = (int)$_POST['id'];
            $conn->prepare("DELETE FROM asset_statuses WHERE id = ?")->execute([$id]);
        }

        // KIRIM STATUS SUKSES KE AJAX JAVASCRIPT
        http_response_code(200);
        echo "Sukses";
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database Error: " . $e->getMessage();
        exit;
    }
}
