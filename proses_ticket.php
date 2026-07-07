<?php
// 1. Ambil otentikasi login sesuai proyek Anda
require_once __DIR__ . '/auth.php';
require_login();

// SISIPKAN FILE UTAMA RBAC DI SINI
require_once __DIR__ . '/helper_rbac.php';

// ==================== PERBAIKAN TRANSLATOR ROLE ID UTAMA ====================
// Ambil angka role_id asli dari session login (Super Admin = 1)
$sessionRoleId = isset($_SESSION['user_role_id']) ? (int)$_SESSION['user_role_id'] : (isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : 4);

// Petakan angka ID menjadi string nama teks agar sinkron dengan matriks hak akses Anda
$roleMapping = [
    1 => 'Super Admin',
    2 => 'Admin IT',
    3 => 'Teknisi',
    4 => 'Viewer'
];

$userRole = isset($roleMapping[$sessionRoleId]) ? $roleMapping[$sessionRoleId] : 'Viewer';
// =========================================================================

// 2. Konfigurasi Database (Sesuaikan dengan data db.php Anda)
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Menangkap nilai action dari parameter request form (POST/GET)
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// =========================================================================
// PERBAIKAN LOGIKA RBAC ASLI: Mengunci aksi manipulasi data tabel tickets
// =========================================================================
if (!empty($action)) {
    $table_name = 'tickets'; // Dikunci langsung ke nama tabel database fisik Anda

    if ($action === 'tambah_ticket' || $action === 'create') {
        protect_page_by_table($table_name, 'C'); // Semua peran (1, 2, 3, 4) lolos untuk membuat tiket
    } elseif ($action === 'edit_ticket' || $action === 'update') {
        protect_page_by_table($table_name, 'U'); // Super Admin (1), Admin IT (2), & Teknisi (3) lolos
    } elseif ($action === 'hapus_ticket' || $action === 'delete') {
        protect_page_by_table($table_name, 'D'); // Hanya Super Admin (1), Admin IT (2), & Teknisi (3) lolos. Viewer (4) di-block!
    }
}

try {
    // Menggunakan koneksi PDO sesuai dashboard utama Anda
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil parameter aksi dari URL form
    $action = $_GET['action'] ?? '';

    // Skenario A: Viewer dilarang keras melakukan aksi UPDATE atau DELETE tiket secara global
    if ($userRole === 'Viewer' && in_array($action, ['update', 'delete', 'edit'])) {
        if (function_exists('write_log')) {
            write_log($conn, "Upaya ilegal memodifikasi/mengubah tiket oleh Viewer", "tickets", $_SESSION['user']['id'] ?? 0);
        }
        echo "<script>
                alert('Akses Ditolak! Akun Viewer tidak memiliki izin untuk mengubah atau menghapus data tiket.');
                window.location.href = 'tickets.php';
              </script>";
        exit();
    }

    // LOGIKA TAMBAH DATA (CREATE) - Semua Role Termasuk Viewer Diizinkan Melaporkan Tiket
    if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // Menyiapkan query insert ke tabel tickets
        $query = "INSERT INTO tickets (nomor, judul, deskripsi, room_id, prioritas, status, pelapor, created_at) 
                  VALUES (:nomor, :judul, :deskripsi, :room_id, :prioritas, 1, :pelapor, NOW())";
        
        $stmt = $conn->prepare($query);
        
        // Mengambil ID user dari array session $_SESSION['user']['id'] yang sudah kita perbaiki
        $id_pelapor = $_SESSION['user']['id'] ?? 1; 

        // Eksekusi pengikatan data dari form modal tambah
        $sukses_add = $stmt->execute([
            ':nomor'     => $_POST['nomor'],
            ':judul'     => $_POST['judul'],
            ':deskripsi' => $_POST['deskripsi'],
            ':room_id'   => $_POST['room_id'],
            ':prioritas' => $_POST['prioritas'],
            ':pelapor'   => $id_pelapor
        ]);

        // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
        if ($sukses_add) {
            $new_ticket_id = $conn->lastInsertId();
            write_log($conn, "Menambahkan tiket pengaduan baru #" . $_POST['nomor'] . ": " . $_POST['judul'], "tickets", $id_pelapor);
        }
    }
    
    // LOGIKA PERBARUI DATA (UPDATE) - Super Admin, Admin IT, dan Teknisi Diizinkan
    elseif ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = intval($_POST['id']);
        
        $query = "UPDATE tickets 
                  SET judul = :judul, status = :status, teknisi = :teknisi 
                  WHERE id = :id";
                  
        $stmt = $conn->prepare($query);
        
        $sukses_edit = $stmt->execute([
            ':judul'   => $_POST['judul'],
            ':status'  => $_POST['status'],
            ':teknisi' => !empty($_POST['teknisi']) ? $_POST['teknisi'] : null, // Set NULL jika teknisi belum dipilih
            ':id'      => $id
        ]);

        // TULIS LOG AKTIVITAS (UPDATE)
        if ($sukses_edit) {
            $id_petugas_aktif = $_SESSION['user']['id'] ?? 1;
            write_log($conn, "Memperbarui data tiket pengaduan: " . $_POST['judul'], "tickets", $id_petugas_aktif);
        }
    }

    // Alihkan halaman kembali ke daftar antrean tiket setelah proses database berhasil
    header("Location: tickets.php");
    exit;

} catch (PDOException $e) {
    // Tampilkan pesan error jika query database gagal dijalankan
    die("Gagal memproses data database: " . $e->getMessage());
}
?>
