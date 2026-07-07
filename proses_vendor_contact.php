<?php
// 1. Inisialisasi Otentikasi dan Koneksi Database
require_once __DIR__ . '/auth.php';
require_login();

// SISIPKAN FILE UTAMA RBAC DI SINI
require_once __DIR__ . '/helper_rbac.php';

$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Menangkap nilai action, baik dari parameter POST maupun URL (GET)
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// =========================================================================
// PERBAIKAN LOGIKA RBAC ASLI: Mengunci aksi manipulasi data kontak vendor
// =========================================================================
if (!empty($action)) {
    // Dipetakan langsung ke modul vendor di database Anda
    $table_name = 'vendors'; 

    if ($action === 'add_contact' || $action === 'create') {
        protect_page_by_table($table_name, 'C'); // Hanya Super Admin (1) & Admin IT (2) yang lolos
    } elseif ($action === 'edit_contact' || $action === 'update') {
        protect_page_by_table($table_name, 'U'); // Hanya Super Admin (1) & Admin IT (2) yang lolos
    } elseif ($action === 'delete_contact' || $action === 'delete') {
        protect_page_by_table($table_name, 'D'); // Hanya Super Admin (1) & Admin IT (2) yang lolos
    }
}

try {
    // SINKRONISASI: Mengubah charset menjadi utf8mb4 agar kompatibel penuh dengan pembacaan karakter khusus
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// 2. LOGIKA TAMBAH DATA (CREATE CONTACT)
if (isset($_POST['action']) && $_POST['action'] == 'add_contact') {
    $vendor_id = (int)$_POST['vendor_id'];
    $nama      = trim($_POST['nama'] ?? '');
    $jabatan   = trim($_POST['jabatan'] ?? '');
    $telepon   = trim($_POST['telepon'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    // Validasi input wajib sesuai skema database (vendor_id dan nama tidak boleh kosong)
    if (empty($vendor_id) || empty($nama)) {
        header("Location: vendor_contacts.php?status=failed_empty");
        exit();
    }

    try {
        $sql = "INSERT INTO vendor_contacts (vendor_id, nama, jabatan, telepon, email) 
                VALUES (:vendor_id, :nama, :jabatan, :telepon, :email)";
        $stmt = $conn->prepare($sql);
        $sukses_add = $stmt->execute([
            ':vendor_id' => $vendor_id,
            ':nama'      => $nama,
            ':jabatan'   => $jabatan,
            ':telepon'   => $telepon,
            ':email'     => $email
        ]);

        // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
        if ($sukses_add) {
            $new_contact_id = $conn->lastInsertId();
            write_log($conn, "Menambahkan kontak vendor baru: " . $nama, "vendor_contacts", $new_contact_id);
        }

        header("Location: vendor_contacts.php?status=success_add");
        exit();
    } catch (PDOException $e) {
        die("Error saat menyimpan kontak: " . $e->getMessage());
    }
}

// 3. LOGIKA UBAH DATA (UPDATE CONTACT)
if (isset($_POST['action']) && $_POST['action'] == 'edit_contact') {
    $id        = (int)$_POST['id'];
    $vendor_id = (int)$_POST['vendor_id'];
    $nama      = trim($_POST['nama']);
    $jabatan   = trim($_POST['jabatan']);
    $telepon   = trim($_POST['telepon']);
    $email     = trim($_POST['email']);

    if (empty($id) || empty($vendor_id) || empty($nama)) {
        header("Location: vendor_contacts.php?status=failed_empty");
        exit();
    }

    try {
        $sql = "UPDATE vendor_contacts SET 
                    vendor_id = :vendor_id, 
                    nama = :nama, 
                    jabatan = :jabatan, 
                    telepon = :telepon, 
                    email = :email 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $sukses_edit = $stmt->execute([
            ':vendor_id' => $vendor_id,
            ':nama'      => $nama,
            ':jabatan'   => $jabatan,
            ':telepon'   => $telepon,
            ':email'     => $email,
            ':id'        => $id
        ]);

        // TULIS LOG AKTIVITAS (UPDATE)
        if ($sukses_edit) {
            write_log($conn, "Mengubah data kontak vendor menjadi: " . $nama, "vendor_contacts", $id);
        }

        header("Location: vendor_contacts.php?status=success_update");
        exit();
    } catch (PDOException $e) {
        die("Error saat memperbarui kontak: " . $e->getMessage());
    }
}

// 4. LOGIKA HAPUS DATA (DELETE CONTACT)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        // 1. Ambil nama kontak terlebih dahulu untuk log sebelum datanya terhapus
        $get_info = $conn->prepare("SELECT nama FROM vendor_contacts WHERE id = :id");
        $get_info->execute([':id' => $id]);
        $nama_kontak = $get_info->fetchColumn() ?: 'Unknown';

        // 2. Jalankan query hapus data
        $sql = "DELETE FROM vendor_contacts WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $sukses_delete = $stmt->execute([':id' => $id]);

        // TULIS LOG AKTIVITAS (DELETE)
        if ($sukses_delete) {
            write_log($conn, "Menghapus data kontak vendor: " . $nama_kontak, "vendor_contacts", $id);
        }

        header("Location: vendor_contacts.php?status=success_delete");
        exit();
    } catch (PDOException $e) {
        die("Error saat menghapus kontak: " . $e->getMessage());
    }
}

// Pengamanan: Jika berkas diakses langsung tanpa parameter aksi, kembalikan ke halaman tabel kontak
header("Location: vendor_contacts.php");
exit();
