<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================================================================
// FILE PROSES BACKEND: proses_asset.php (CREATE, UPDATE & DELETE MULTI-TABEL)
// =========================================================================
session_start();
require_once 'auth.php'; // HUBUNGKAN DENGAN AUTH.PHP UNTUK MENGAKSES FUNGSI WRITE_LOG()

$host     = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// -------------------------------------------------------------------------
// LOGIKA 1: TAMBAH DATA (CREATE)
// -------------------------------------------------------------------------
if ($action == 'create') {
    $kategori_id   = !empty($_POST['kategori_id']) ? $_POST['kategori_id'] : null;
    $brand_id      = !empty($_POST['brand_id']) ? $_POST['brand_id'] : null;
    $room_id       = !empty($_POST['room_id']) ? $_POST['room_id'] : null;
    $status_id     = !empty($_POST['status_id']) ? $_POST['status_id'] : null;
    $kode_asset    = trim($_POST['kode_asset'] ?? '');
    $nama          = trim($_POST['nama'] ?? '');
    $serial_number = !empty($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $hostname      = !empty($_POST['hostname']) ? trim($_POST['hostname']) : null;
    $ip_address    = !empty($_POST['ip_address']) ? trim($_POST['ip_address']) : null;
    $mac_address   = !empty($_POST['mac_address']) ? trim($_POST['mac_address']) : null;
    $tanggal_beli  = !empty($_POST['tanggal_beli']) ? $_POST['tanggal_beli'] : null;
    $garansi       = !empty($_POST['garansi']) ? $_POST['garansi'] : null;
    $spesifikasi   = !empty($_POST['spesifikasi']) ? trim($_POST['spesifikasi']) : null;

    if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }

    $nama_foto = null;
    if (!empty($_POST['foto_webcam'])) {
        $raw_base64 = $_POST['foto_webcam'];
        list($type, $raw_data) = explode(';', $raw_base64);
        list(, $raw_data)      = explode(',', $raw_data);
        $nama_foto = "CAM_" . time() . "_" . rand(100, 999) . ".png";
        file_put_contents('uploads/' . $nama_foto, base64_decode($raw_data));
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext_foto  = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_foto = "IMG_" . time() . "_" . rand(100, 999) . "." . $ext_foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $nama_foto);
    }

    $nama_manual_book = null;
    if (isset($_FILES['manual_book']) && $_FILES['manual_book']['error'] == 0) {
        $ext_pdf          = pathinfo($_FILES['manual_book']['name'], PATHINFO_EXTENSION);
        $nama_manual_book = "DOC_" . time() . "_" . rand(100, 999) . "." . $ext_pdf;
        move_uploaded_file($_FILES['manual_book']['tmp_name'], 'uploads/' . $nama_manual_book);
    }

    $sql = "INSERT INTO assets (kategori_id, brand_id, room_id, status_id, kode_asset, nama, serial_number, hostname, ip_address, mac_address, tanggal_beli, garansi, foto, manual_book, spesifikasi, created_at, updated_at) 
            VALUES (:kategori_id, :brand_id, :room_id, :status_id, :kode_asset, :nama, :serial_number, :hostname, :ip_address, :mac_address, :tanggal_beli, :garansi, :foto, :manual_book, :spesifikasi, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    $sukses = $stmt->execute([
        ':kategori_id' => $kategori_id, ':brand_id' => $brand_id, ':room_id' => $room_id, ':status_id' => $status_id,
        ':kode_asset' => $kode_asset, ':nama' => $nama, ':serial_number' => $serial_number, ':hostname' => $hostname,
        ':ip_address' => $ip_address, ':mac_address' => $mac_address, ':tanggal_beli' => $tanggal_beli, ':garansi' => $garansi,
        ':foto' => $nama_foto, ':manual_book' => $nama_manual_book, ':spesifikasi' => $spesifikasi
    ]);

    // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
    if ($sukses) {
        $new_asset_id = $conn->lastInsertId();
        write_log($conn, "Menambahkan data asset baru: " . $nama, "assets", $new_asset_id);
    }

    header("Location: assets.php");
    exit();
}

// -------------------------------------------------------------------------
// LOGIKA 2: UBAH DATA / EDIT (UPDATE)
// -------------------------------------------------------------------------
if ($action == 'update') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) { die("ID Data tidak valid."); }

    $kategori_id   = !empty($_POST['kategori_id']) ? $_POST['kategori_id'] : null;
    $brand_id      = !empty($_POST['brand_id']) ? $_POST['brand_id'] : null;
    $room_id       = !empty($_POST['room_id']) ? $_POST['room_id'] : null;
    $status_id     = !empty($_POST['status_id']) ? $_POST['status_id'] : null;
    $kode_asset    = trim($_POST['kode_asset'] ?? '');
    $nama          = trim($_POST['nama'] ?? '');
    $serial_number = !empty($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $hostname      = !empty($_POST['hostname']) ? trim($_POST['hostname']) : null;
    $ip_address    = !empty($_POST['ip_address']) ? trim($_POST['ip_address']) : null;
    $mac_address   = !empty($_POST['mac_address']) ? trim($_POST['mac_address']) : null;
    $tanggal_beli  = !empty($_POST['tanggal_beli']) ? $_POST['tanggal_beli'] : null;
    $garansi       = !empty($_POST['garansi']) ? $_POST['garansi'] : null;
    $spesifikasi   = !empty($_POST['spesifikasi']) ? trim($_POST['spesifikasi']) : null;

    // Ambil berkas lama
    $get_old = $conn->prepare("SELECT foto, manual_book FROM assets WHERE id = :id");
    $get_old->execute([':id' => $id]);
    $old_data = $get_old->fetch(PDO::FETCH_ASSOC);
    
    $nama_foto = $old_data['foto'] ?? null;
    $nama_manual_book = $old_data['manual_book'] ?? null;

    // Update foto
    if (!empty($_POST['foto_webcam'])) {
        if (!empty($nama_foto) && $nama_foto != 'default.jpg') {
            $path_foto_lama = 'uploads/' . $nama_foto;
            if (file_exists($path_foto_lama)) { unlink($path_foto_lama); }
        }
        $raw_base64 = $_POST['foto_webcam'];
        list($type, $raw_data) = explode(';', $raw_base64);
        list(, $raw_data)      = explode(',', $raw_data);
        $nama_foto = "CAM_" . time() . "_" . rand(100, 999) . ".png";
        file_put_contents('uploads/' . $nama_foto, base64_decode($raw_data));
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        if (!empty($nama_foto) && $nama_foto != 'default.jpg') {
            $path_foto_lama = 'uploads/' . $nama_foto;
            if (file_exists($path_foto_lama)) { unlink($path_foto_lama); }
        }
        $ext_foto  = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_foto = "IMG_" . time() . "_" . rand(100, 999) . "." . $ext_foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $nama_foto);
    }

    // Update manual book
    if (isset($_FILES['manual_book']) && $_FILES['manual_book']['error'] == 0) {
        if (!empty($nama_manual_book)) {
            $path_pdf_lama = 'uploads/' . $nama_manual_book;
            if (file_exists($path_pdf_lama)) { unlink($path_pdf_lama); }
        }
        $ext_pdf          = pathinfo($_FILES['manual_book']['name'], PATHINFO_EXTENSION);
        $nama_manual_book = "DOC_" . time() . "_" . rand(100, 999) . "." . $ext_pdf;
        move_uploaded_file($_FILES['manual_book']['tmp_name'], 'uploads/' . $nama_manual_book);
    }

    // Jalankan query update database
    $sql = "UPDATE assets SET 
                kategori_id = :kategori_id, brand_id = :brand_id, room_id = :room_id, status_id = :status_id, 
                kode_asset = :kode_asset, nama = :nama, serial_number = :serial_number, hostname = :hostname,
                ip_address = :ip_address, mac_address = :mac_address, tanggal_beli = :tanggal_beli, 
                garansi = :garansi, foto = :foto, manual_book = :manual_book, spesifikasi = :spesifikasi, 
                updated_at = NOW() 
            WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $sukses_update = $stmt->execute([
        ':kategori_id' => $kategori_id, ':brand_id' => $brand_id, ':room_id' => $room_id, ':status_id' => $status_id,
        ':kode_asset' => $kode_asset, ':nama' => $nama, ':serial_number' => $serial_number, ':hostname' => $hostname,
        ':ip_address' => $ip_address, ':mac_address' => $mac_address, ':tanggal_beli' => $tanggal_beli, 
        ':garansi' => $garansi, ':foto' => $nama_foto, ':manual_book' => $nama_manual_book, ':spesifikasi' => $spesifikasi, 
        ':id' => $id
    ]);

    // TULIS LOG AKTIVITAS (UPDATE)
    if ($sukses_update) {
        write_log($conn, "Mengubah data asset: " . $nama, "assets", $id);
    }

    header("Location: assets.php");
    exit();
}

// -------------------------------------------------------------------------
// LOGIKA 3: HAPUS DATA (DELETE)
// -------------------------------------------------------------------------
if ($action == 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) { die("ID Data tidak valid."); }

    try {
        // 1. Ambil nama asset & berkas lama sebelum datanya terhapus permanen
        $get_name = $conn->prepare("SELECT nama, foto, manual_book FROM assets WHERE id = :id");
        $get_name->execute([':id' => $id]);
        $asset_data = $get_name->fetch(PDO::FETCH_ASSOC);

        if ($asset_data) {
            $nama_asset = $asset_data['nama'];
            
            // 2. Hapus berkas file fisik di direktori uploads jika ada
            if (!empty($asset_data['foto']) && $asset_data['foto'] != 'default.jpg') {
                if (file_exists('uploads/' . $asset_data['foto'])) { 
                    unlink('uploads/' . $asset_data['foto']); 
                }
            }
            if (!empty($asset_data['manual_book'])) {
                if (file_exists('uploads/' . $asset_data['manual_book'])) { 
                    unlink('uploads/' . $asset_data['manual_book']); 
                }
            }

            // 3. Jalankan query hapus baris tabel
            $sql_delete = "DELETE FROM assets WHERE id = :id";
            $stmt_delete = $conn->prepare($sql_delete);
            $sukses_delete = $stmt_delete->execute([':id' => $id]);

            // 4. Catat ke log aktivitas sistem beserta Data ID-nya
            if ($sukses_delete) {
                write_log($conn, "Menghapus data asset: " . $nama_asset, "assets", $id);
            }
        }
    } catch (PDOException $e) {
        die("Gagal menghapus data: " . $e->getMessage());
    }

    // 5. Kembalikan secara otomatis ke halaman daftar asset (Mencegah Layar Putih)
    header("Location: assets.php");
    exit();
}
?>
