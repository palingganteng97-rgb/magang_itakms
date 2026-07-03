<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================================================================
// FILE PROSES BACKEND: proses_asset.php (CREATE, UPDATE & DELETE MULTI-TABEL)
// =========================================================================
session_start();

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
    $stmt->execute([
        ':kategori_id' => $kategori_id, ':brand_id' => $brand_id, ':room_id' => $room_id, ':status_id' => $status_id,
        ':kode_asset' => $kode_asset, ':nama' => $nama, ':serial_number' => $serial_number, ':hostname' => $hostname,
        ':ip_address' => $ip_address, ':mac_address' => $mac_address, ':tanggal_beli' => $tanggal_beli, ':garansi' => $garansi,
        ':foto' => $nama_foto, ':manual_book' => $nama_manual_book, ':spesifikasi' => $spesifikasi
    ]);

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
    $stmt->execute([
        ':kategori_id' => $kategori_id, ':brand_id' => $brand_id, ':room_id' => $room_id, ':status_id' => $status_id,
        ':kode_asset' => $kode_asset, ':nama' => $nama, ':serial_number' => $serial_number, ':hostname' => $hostname,
        ':ip_address' => $ip_address, ':mac_address' => $mac_address, ':tanggal_beli' => $tanggal_beli, ':garansi' => $garansi,
        ':foto' => $nama_foto, ':manual_book' => $nama_manual_book, ':spesifikasi' => $spesifikasi, ':id' => $id
    ]);

    header("Location: assets.php");
    exit();
} // FIX: Tanda penutup kurung kurawal untuk blok update sudah terpasang rapi di sini

// -------------------------------------------------------------------------
// LOGIKA 3: HAPUS DATA TOTAL (DELETE DENGAN PEMBERSIHAN MULTI-TABEL RELASI)
// -------------------------------------------------------------------------
if ($action == 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) { die("ID Data tidak valid untuk dihapus."); }

    try {
        // 1. Mulai Database Transaction untuk mengunci integritas query multi-tabel
        $conn->beginTransaction();

        // 2. Ambil info file berkas media sebelum baris data dihapus
        $get_files = $conn->prepare("SELECT nama, foto, manual_book FROM assets WHERE id = :id");
        $get_files->execute([':id' => $id]);
        $files = $get_files->fetch(PDO::FETCH_ASSOC);

        if ($files) {
            $nama_asset_dihapus = $files['nama'] ?? 'Tidak Diketahui';

            // 3. Putus relasi data terkait di tabel anak 'asset_movements'
            $stmt_mov = $conn->prepare("DELETE FROM asset_movements WHERE asset_id = :id");
            $stmt_mov->execute([':id' => $id]);

            // 4. Putus relasi data terkait di tabel anak 'network_devices' 
            $stmt_net = $conn->prepare("UPDATE network_devices SET asset_id = NULL WHERE asset_id = :id");
            $stmt_net->execute([':id' => $id]);

            // 5. Putus relasi data terkait di tabel anak 'servers'
            $stmt_srv = $conn->prepare("UPDATE servers SET asset_id = NULL WHERE asset_id = :id");
            $stmt_srv->execute([':id' => $id]);

            // 6. Setelah semua relasi tabel anak dibersihkan, hapus baris data utama di tabel induk assets
            $stmt_asset = $conn->prepare("DELETE FROM assets WHERE id = :id");
            $stmt_asset->execute([':id' => $id]);

            // =========================================================================
            // KUNCI UTAMA: Tulis Log Aktivitas TEPAT DI SINI sebelum Transaction di-Commit
            // =========================================================================
            write_log($conn, "Menghapus total data aset: '" . $nama_asset_dihapus . "' beserta seluruh relasinya", "assets", $id);

            // 7. Commit seluruh rangkaian transaksi database jika tidak terjadi kendala query
            $conn->commit();

            // 8. Pembersihan Berkas Media (jika bukan gambar default)
            if (!empty($files['foto']) && $files['foto'] != 'default.jpg') {
                $path_foto = 'uploads/' . $files['foto'];
                if (file_exists($path_foto)) { unlink($path_foto); }
            }

            // 9. Hapus file dokumen PDF manual book dari folder uploads
            if (!empty($files['manual_book'])) {
                $path_pdf = 'uploads/' . $files['manual_book'];
                if (file_exists($path_pdf)) { unlink($path_pdf); }
            }

            $_SESSION['flash_message'] = "Aset beserta seluruh riwayat perpindahan dan relasi sistem terkait berhasil dihapus.";
        } else {
            $_SESSION['flash_error'] = "Data aset tidak ditemukan.";
        }

        // Alihkan halaman ke log aktivitas agar Anda langsung bisa melihat hasilnya tercatat
        header("Location: activity_logs.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Gagal menghapus data akibat kendala sistem: " . $e->getMessage());
    }
}

?>
