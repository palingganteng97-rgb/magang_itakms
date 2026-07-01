<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
require_login();

// Import konfigurasi database Anda
require_once __DIR__ . '/db.php'; 
// Catatan: Jika db.php belum menggunakan PDO, silakan sesuaikan variabel koneksinya. 
// Kode di bawah mengasumsikan koneksi menggunakan PDO dengan nama variabel $conn.

$action = $_REQUEST['action'] ?? '';

try {
    // ==========================================
    // A. PROSES TAMBAH SERVER
    // ==========================================
    if ($action === 'tambah_server' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $asset_id = !empty($_POST['asset_id']) ? (int)$_POST['asset_id'] : null;
        $os       = trim($_POST['os'] ?? '');
        $cpu      = trim($_POST['cpu'] ?? '');
        $ram      = trim($_POST['ram'] ?? '');
        $storage  = trim($_POST['storage'] ?? '');
        $rack     = trim($_POST['rack'] ?? '');
        $fungsi   = trim($_POST['fungsi'] ?? '');
        $status   = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($os !== '' && $cpu !== '') {
            $sql = "INSERT INTO servers (asset_id, os, cpu, ram, storage, rack, fungsi, status) 
                    VALUES (:asset_id, :os, :cpu, :ram, :storage, :rack, :fungsi, :status)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':asset_id' => $asset_id,
                ':os'       => $os,
                ':cpu'      => $cpu,
                ':ram'      => $ram,
                ':storage'  => $storage,
                ':rack'     => $rack,
                ':fungsi'   => $fungsi,
                ':status'   => $status
            ]);
            $_SESSION['msg_success'] = "Server baru berhasil ditambahkan!";
        } else {
            $_SESSION['msg_error'] = "Kolom OS dan CPU wajib diisi!";
        }
    }

    // ==========================================
    // B. PROSES EDIT SERVER
    // ==========================================
    elseif ($action === 'edit_server' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id       = (int)($_POST['id'] ?? 0);
        $asset_id = !empty($_POST['asset_id']) ? (int)$_POST['asset_id'] : null;
        $os       = trim($_POST['os'] ?? '');
        $cpu      = trim($_POST['cpu'] ?? '');
        $ram      = trim($_POST['ram'] ?? '');
        $storage  = trim($_POST['storage'] ?? '');
        $rack     = trim($_POST['rack'] ?? '');
        $fungsi   = trim($_POST['fungsi'] ?? '');
        $status   = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($id > 0 && $os !== '' && $cpu !== '') {
            $sql = "UPDATE servers SET 
                        asset_id = :asset_id, os = :os, cpu = :cpu, ram = :ram, 
                        storage = :storage, rack = :rack, fungsi = :fungsi, status = :status 
                    WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':asset_id' => $asset_id,
                ':os'       => $os,
                ':cpu'      => $cpu,
                ':ram'      => $ram,
                ':storage'  => $storage,
                ':rack'     => $rack,
                ':fungsi'   => $fungsi,
                ':status'   => $status,
                ':id'       => $id
            ]);
            $_SESSION['msg_success'] = "Data server berhasil diperbarui!";
        } else {
            $_SESSION['msg_error'] = "Gagal memperbarui! Data tidak valid.";
        }
    }

    // ==========================================
    // C. PROSES HAPUS SERVER
    // ==========================================
    elseif ($action === 'hapus_server') {
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $sql = "DELETE FROM servers WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            $_SESSION['msg_success'] = "Server berhasil dihapus!";
        } else {
            $_SESSION['msg_error'] = "ID Server tidak ditemukan.";
        }
    }

} catch (PDOException $e) {
    $_SESSION['msg_error'] = "Error Database: " . $e->getMessage();
}

// Kembalikan pengguna ke halaman utama server.php setelah proses selesai
header("Location: server.php");
exit();
