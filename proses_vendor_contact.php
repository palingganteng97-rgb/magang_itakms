<?php
// 1. Inisialisasi Otentikasi dan Koneksi Database
require_once __DIR__ . '/auth.php';
require_login();

$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// 2. LOGIKA TAMBAH DATA (CREATE CONTACT)
if (isset($_POST['action']) && $_POST['action'] == 'add_contact') {
    $vendor_id = (int)$_POST['vendor_id'];
    $nama      = trim($_POST['nama']);
    $jabatan   = trim($_POST['jabatan']);
    $telepon   = trim($_POST['telepon']);
    $email     = trim($_POST['email']);

    // Validasi input wajib sesuai skema database (vendor_id dan nama tidak boleh kosong)
    if (empty($vendor_id) || empty($nama)) {
        header("Location: vendor_contacts.php?status=failed_empty");
        exit();
    }

    try {
        $sql = "INSERT INTO vendor_contacts (vendor_id, nama, jabatan, telepon, email) 
                VALUES (:vendor_id, :nama, :jabatan, :telepon, :email)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':vendor_id' => $vendor_id,
            ':nama'      => $nama,
            ':jabatan'   => $jabatan,
            ':telepon'   => $telepon,
            ':email'     => $email
        ]);

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
        $stmt->execute([
            ':vendor_id' => $vendor_id,
            ':nama'      => $nama,
            ':jabatan'   => $jabatan,
            ':telepon'   => $telepon,
            ':email'     => $email,
            ':id'        => $id
        ]);

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
        $sql = "DELETE FROM vendor_contacts WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);

        header("Location: vendor_contacts.php?status=success_delete");
        exit();
    } catch (PDOException $e) {
        die("Error saat menghapus kontak: " . $e->getMessage());
    }
}

// Pengamanan: Jika berkas diakses langsung tanpa parameter aksi, kembalikan ke halaman tabel kontak
header("Location: vendor_contacts.php");
exit();
