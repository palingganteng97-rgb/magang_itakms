<?php
// 1. Inisialisasi Auth dan Koneksi Database
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

// 2. LOGIKA TAMBAH DATA (CREATE)
if (isset($_POST['action']) && $_POST['action'] == 'add_vendor') {
    $nama    = trim($_POST['nama']);
    $pic     = trim($_POST['pic']);
    $telepon = trim($_POST['telepon']);
    $email   = trim($_POST['email']);
    $website = trim($_POST['website']);
    $status  = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    if (empty($nama)) {
        header("Location: vendors.php?status=failed_empty");
        exit();
    }

    try {
        $sql = "INSERT INTO vendors (nama, pic, telepon, email, website, status) 
                VALUES (:nama, :pic, :telepon, :email, :website, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nama'    => $nama,
            ':pic'     => $pic,
            ':telepon' => $telepon,
            ':email'   => $email,
            ':website' => $website,
            ':status'  => $status
        ]);

        header("Location: vendors.php?status=success_add");
        exit();
    } catch (PDOException $e) {
        die("Error simpan data: " . $e->getMessage());
    }
}

// 3. LOGIKA UBAH DATA (UPDATE)
if (isset($_POST['action']) && $_POST['action'] == 'edit_vendor') {
    $id      = (int)$_POST['id'];
    $nama    = trim($_POST['nama']);
    $pic     = trim($_POST['pic']);
    $telepon = trim($_POST['telepon']);
    $email   = trim($_POST['email']);
    $website = trim($_POST['website']);
    $status  = (int)$_POST['status'];

    if (empty($id) || empty($nama)) {
        header("Location: vendors.php?status=failed_empty");
        exit();
    }

    try {
        $sql = "UPDATE vendors SET 
                    nama = :nama, 
                    pic = :pic, 
                    telepon = :telepon, 
                    email = :email, 
                    website = :website, 
                    status = :status 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nama'    => $nama,
            ':pic'     => $pic,
            ':telepon' => $telepon,
            ':email'   => $email,
            ':website' => $website,
            ':status'  => $status,
            ':id'      => $id
        ]);

        header("Location: vendors.php?status=success_update");
        exit();
    } catch (PDOException $e) {
        die("Error ubah data: " . $e->getMessage());
    }
}

// 4. LOGIKA HAPUS DATA (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $sql = "DELETE FROM vendors WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);

        header("Location: vendors.php?status=success_delete");
        exit();
    } catch (PDOException $e) {
        die("Error hapus data: " . $e->getMessage());
    }
}

// Jika tidak ada aksi valid, kembalikan ke halaman utama vendor
header("Location: vendors.php");
exit();
