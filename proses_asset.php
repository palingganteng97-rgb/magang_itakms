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

// Menangkap nilai action, baik dari URL (GET) maupun dari Form Submit (POST)
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// 1. Ambil data role asli user dari session login
$userRole = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'Viewer';

// =========================================================================
// VALIDASI PROTEKSI BACKEND GLOBAL (FLEKSIBEL SEMUA OPSI ROLE)
// =========================================================================

// OPSI A: Jika role adalah 'Viewer', tolak semua aksi manipulasi data (C, U, D)
if ($userRole === 'Viewer' && in_array($action, ['insert', 'update', 'delete', 'add', 'edit'])) {
    // Tulis ke log aktivitas bahwa ada upaya bypass ilegal (opsional)
    if (function_exists('write_log')) {
        write_log($conn, $_SESSION['user']['id'] ?? 0, 'Upaya ilegal manipulasi data asset oleh Viewer', 'assets.php');
    }
    echo "<script>
            alert('Akses Ditolak! Akun Viewer tidak memiliki izin untuk menambah, mengubah, atau menghapus data asset.');
            window.location='assets.php';
          </script>";
    exit();
}

// OPSI B: Jika role adalah 'Teknisi', tolak aksi Tambah (C) dan Hapus (D)
// Teknisi hanya diizinkan melakukan Update ('update' / 'edit') Status Asset
if ($userRole === 'Teknisi' && in_array($action, ['insert', 'delete', 'add'])) {
    if (function_exists('write_log')) {
        write_log($conn, $_SESSION['user']['id'] ?? 0, 'Upaya ilegal menambah/menghapus data asset oleh Teknisi', 'assets.php');
    }
    echo "<script>
            alert('Akses Ditolak! Akun Teknisi hanya diizinkan untuk memperbarui status asset saja.');
            window.location='assets.php';
          </script>";
    exit();
}

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

    try {
        $sukses = $stmt->execute([
            ':kategori_id' => $kategori_id, ':brand_id' => $brand_id, ':room_id' => $room_id, ':status_id' => $status_id,
            ':kode_asset' => $kode_asset, ':nama' => $nama, ':serial_number' => $serial_number, ':hostname' => $hostname,
            ':ip_address' => $ip_address, ':mac_address' => $mac_address, ':tanggal_beli' => $tanggal_beli, ':garansi' => $garansi,
            ':foto' => $nama_foto, ':manual_book' => $nama_manual_book, ':spesifikasi' => $spesifikasi
        ]);

        if ($sukses) {
            $new_asset_id = $conn->lastInsertId();
            write_log($conn, "Menambahkan data asset baru: " . $nama, "assets", $new_asset_id);
        }

        header("Location: assets.php");
        exit();

    } catch (PDOException $e) {
        if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
            if (!empty($nama_foto) && file_exists('uploads/' . $nama_foto)) { unlink('uploads/' . $nama_foto); }
            if (!empty($nama_manual_book) && file_exists('uploads/' . $nama_manual_book)) { unlink('uploads/' . $nama_manual_book); }

            echo "<script>
                    alert('Gagal Simpan! Kode Asset \'$kode_asset\' sudah terdaftar di database. Silakan gunakan kode unik yang lain.');
                    window.history.back();
                  </script>";
            exit();
        } else {
            die("Kesalahan database: " . $e->getMessage());
        }
    }
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

    // Ambil data berkas lama dari database untuk cadangan
    $get_old = $conn->prepare("SELECT foto, manual_book FROM assets WHERE id = :id");
    $get_old->execute([':id' => $id]);
    $old_data = $get_old->fetch(PDO::FETCH_ASSOC);
    
    $nama_foto = $old_data['foto'] ?? null;
    $nama_manual_book = $old_data['manual_book'] ?? null;

    // Flag penanda apakah ada file baru yang berhasil diunggah
    $foto_terbaru_diupload = false;
    $manual_terbaru_diupload = false;

    // Proses update foto (Webcam atau File Upload)
    if (!empty($_POST['foto_webcam'])) {
        $raw_base64 = $_POST['foto_webcam'];
        list($type, $raw_data) = explode(';', $raw_base64);
        list(, $raw_data)      = explode(',', $raw_data);
        $nama_foto_baru = "CAM_" . time() . "_" . rand(100, 999) . ".png";
        if (file_put_contents('uploads/' . $nama_foto_baru, base64_decode($raw_data))) {
            $nama_foto = $nama_foto_baru;
            $foto_terbaru_diupload = true;
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext_foto  = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_foto_baru = "IMG_" . time() . "_" . rand(100, 999) . "." . $ext_foto;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $nama_foto_baru)) {
            $nama_foto = $nama_foto_baru;
            $foto_terbaru_diupload = true;
        }
    }

    // Proses update dokumen manual book
    if (isset($_FILES['manual_book']) && $_FILES['manual_book']['error'] == 0) {
        $ext_pdf          = pathinfo($_FILES['manual_book']['name'], PATHINFO_EXTENSION);
        $nama_manual_book_baru = "DOC_" . time() . "_" . rand(100, 999) . "." . $ext_pdf;
        if (move_uploaded_file($_FILES['manual_book']['tmp_name'], 'uploads/' . $nama_manual_book_baru)) {
            $nama_manual_book = $nama_manual_book_baru;
            $manual_terbaru_diupload = true;
        }
    }

    // Sambungan Query SQL Utama yang Lengkap dan Utuh
    $sql = "UPDATE assets SET 
                kategori_id = :kategori_id, brand_id = :brand_id, room_id = :room_id, status_id = :status_id, 
                kode_asset = :kode_asset, nama = :nama, serial_number = :serial_number, hostname = :hostname,
                ip_address = :ip_address, mac_address = :mac_address, tanggal_beli = :tanggal_beli, garansi = :garansi,
                foto = :foto, manual_book = :manual_book, spesifikasi = :spesifikasi, updated_at = NOW()
            WHERE id = :id";
            
    $stmt = $conn->prepare($sql);

    try {
        $sukses = $stmt->execute([
            ':kategori_id' => $kategori_id, ':brand_id' => $brand_id, ':room_id' => $room_id, ':status_id' => $status_id,
            ':kode_asset' => $kode_asset, ':nama' => $nama, ':serial_number' => $serial_number, ':hostname' => $hostname,
            ':ip_address' => $ip_address, ':mac_address' => $mac_address, ':tanggal_beli' => $tanggal_beli, ':garansi' => $garansi,
            ':foto' => $nama_foto, ':manual_book' => $nama_manual_book, ':spesifikasi' => $spesifikasi, ':id' => $id
        ]);

        if ($sukses) {
            // File fisik lama HANYA dihapus dari server jika database sukses diperbarui
            if ($foto_terbaru_diupload && !empty($old_data['foto']) && $old_data['foto'] != 'default.jpg' && file_exists('uploads/' . $old_data['foto'])) {
                unlink('uploads/' . $old_data['foto']);
            }
            if ($manual_terbaru_diupload && !empty($old_data['manual_book']) && file_exists('uploads/' . $old_data['manual_book'])) {
                unlink('uploads/' . $old_data['manual_book']);
            }
            // Catat ke log aktivitas admin
            write_log($conn, "Mengubah data asset: " . $nama, "assets", $id);
        }

        header("Location: assets.php");
        exit();

    } catch (PDOException $e) {
        // Mencegah crash halaman jika kode_asset hasil edit ternyata kembar/duplikat
        if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
            // Hapus file baru yang telanjur terupload agar tidak mengotori server
            if ($foto_terbaru_diupload && file_exists('uploads/' . $nama_foto)) { unlink('uploads/' . $nama_foto); }
            if ($manual_terbaru_diupload && file_exists('uploads/' . $nama_manual_book)) { unlink('uploads/' . $nama_manual_book); }

            echo "<script>
                    alert('Gagal Mengubah Data! Kode Asset \'$kode_asset\' sudah terdaftar di aset lain. Silakan gunakan kode unik.');
                    window.history.back();
                  </script>";
            exit();
        } else {
            die("Kesalahan database: " . $e->getMessage());
        }
    }
}

// -------------------------------------------------------------------------
// LOGIKA 3: PROSES PERPINDAHAN RUANGAN ASSET (UPDATE_ROOM)
// -------------------------------------------------------------------------
if ($action == 'update_room') {
    $asset_id  = isset($_POST['asset_id']) ? intval($_POST['asset_id']) : 0;
    $room_id   = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
    $alasan    = !empty($_POST['alasan']) ? trim($_POST['alasan']) : 'Tidak ada alasan yang dicantumkan';

    if ($asset_id <= 0 || empty($room_id)) {
        die("Data mutasi aset tidak valid.");
    }

    try {
        // Ambil nama aset dan nama ruangan lama untuk kebutuhan log riwayat
        $stmtOld = $conn->prepare("
            SELECT a.nama, r.nama AS nama_ruangan_lama 
            FROM assets a 
            LEFT JOIN rooms r ON a.room_id = r.id 
            WHERE a.id = :id
        ");
        $stmtOld->execute([':id' => $asset_id]);
        $oldAsset = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if (!$oldAsset) { 
            die("Aset tidak ditemukan."); 
        }

        $nama_asset        = $oldAsset['nama'];
        $ruangan_lama_nama = $oldAsset['nama_ruangan_lama'] ?? 'Tanpa Ruangan';

        // Ambil nama ruangan baru yang dituju
        $stmtNewRoom = $conn->prepare("SELECT nama FROM rooms WHERE id = :id");
        $stmtNewRoom->execute([':id' => $room_id]);
        $ruangan_baru_nama = $stmtNewRoom->fetchColumn() ?: 'Ruangan Tidak Diketahui';

        // Update record data ruangan aset di database
        $sqlMutasi = "UPDATE assets SET room_id = :room_id, updated_at = NOW() WHERE id = :id";
        $stmtMutasi = $conn->prepare($sqlMutasi);
        $sukses = $stmtMutasi->execute([':room_id' => $room_id, ':id' => $asset_id]);

        if ($sukses) {
            // Tulis narasi mutasi log perpindahan ke log aktivitas
            $pesan_log = "Memindahkan asset '" . $nama_asset . "' dari ruangan [" . $ruangan_lama_nama . "] ke [" . $ruangan_baru_nama . "]. Alasan: " . $alasan;
            write_log($conn, $pesan_log, "assets", $asset_id);
        }

        header("Location: assets.php?status=moved");
        exit();

    } catch (PDOException $e) {
        die("Gagal memproses mutasi ruangan aset: " . $e->getMessage());
    }
}

// -------------------------------------------------------------------------
// LOGIKA 4: HAPUS DATA ASSET (DELETE)
// -------------------------------------------------------------------------
if ($action == 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) { 
        die("ID Data tidak valid."); 
    }

    try {
        // 1. Ambil nama dan nama file (foto & manual book) sebelum datanya dihapus
        $stmtGet = $conn->prepare("SELECT nama, foto, manual_book FROM assets WHERE id = :id");
        $stmtGet->execute([':id' => $id]);
        $asset = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if (!$asset) {
            die("Data aset tidak ditemukan.");
        }

        $nama_asset = $asset['nama'];

        // 2. Jalankan query hapus data dari database
        $stmtDel = $conn->prepare("DELETE FROM assets WHERE id = :id");
        $sukses = $stmtDel->execute([':id' => $id]);

        if ($sukses) {
            // 3. Jika sukses hapus dari DB, bersihkan file fisik di folder uploads agar hemat memori
            if (!empty($asset['foto']) && $asset['foto'] != 'default.jpg' && file_exists('uploads/' . $asset['foto'])) {
                unlink('uploads/' . $asset['foto']);
            }
            if (!empty($asset['manual_book']) && file_exists('uploads/' . $asset['manual_book'])) {
                unlink('uploads/' . $asset['manual_book']);
            }

            // 4. Catat aktivitas hapus ke log admin
            write_log($conn, "Menghapus data asset: " . $nama_asset, "assets", $id);
        }

        header("Location: assets.php?status=deleted");
        exit();

    } catch (PDOException $e) {
        die("Gagal menghapus data asset: " . $e->getMessage());
    }
}

// Jika parameter action tidak ada yang cocok
header("Location: assets.php");
exit();
?>
