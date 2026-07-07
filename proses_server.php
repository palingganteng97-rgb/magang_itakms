<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
require_login();

// SISIPKAN FILE UTAMA RBAC DI SINI
require_once __DIR__ . '/helper_rbac.php';

// Import konfigurasi database Anda
require_once __DIR__ . '/db.php'; 
// Catatan: Jika db.php belum menggunakan PDO, silakan sesuaikan variabel koneksinya. 
// Kode di bawah mengasumsikan koneksi menggunakan PDO dengan nama variabel $conn.

$action = $_REQUEST['action'] ?? '';

// =========================================================================
// PERBAIKAN LOGIKA RBAC ASLI: Mengunci aksi manipulasi data tabel servers
// =========================================================================
if (!empty($action)) {
    $table_name = 'servers'; // DIUBAH 1 PER 1: Dikunci langsung ke nama tabel database fisik Anda

    if ($action === 'tambah_server' || $action === 'create') {
        protect_page_by_table($table_name, 'C'); // Super Admin (1), Admin IT (2), & Teknisi (3) lolos
    } elseif ($action === 'edit_server' || $action === 'update') {
        protect_page_by_table($table_name, 'U'); // Super Admin (1), Admin IT (2), & Teknisi (3) lolos
    } elseif ($action === 'hapus_server' || $action === 'delete') {
        protect_page_by_table($table_name, 'D'); // Hanya Super Admin (1) & Admin IT (2) yang lolos
    } else {
        header('HTTP/1.1 403 Forbidden');
        exit("Aksi tidak valid atau dilarang.");
    }
}

try {
    // =========================================================================
    // A. PROSES TAMBAH SERVER
    // =========================================================================
    if ($action === 'tambah_server' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $asset_id = empty($_POST['asset_id']) ? null : (int)$_POST['asset_id'];
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
            $sukses_add = $stmt->execute([
                ':asset_id' => $asset_id,
                ':os'       => $os,
                ':cpu'      => $cpu,
                ':ram'      => $ram,
                ':storage'  => $storage,
                ':rack'     => $rack,
                ':fungsi'   => $fungsi,
                ':status'   => $status
            ]);

            // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
            if ($sukses_add) {
                $new_server_id = $conn->lastInsertId();
                write_log($conn, "Menambahkan data server baru (" . $fungsi . ") OS: " . $os, "servers", $new_server_id);
            }

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
            $sukses_edit = $stmt->execute([
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

            // TULIS LOG AKTIVITAS (UPDATE)
            if ($sukses_edit) {
                write_log($conn, "Mengubah informasi data server ID: " . $id . " (" . $fungsi . ")", "servers", $id);
            }

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
            // 1. Ambil informasi fungsi server terlebih dahulu untuk log sebelum datanya terhapus
            $get_info = $conn->prepare("SELECT fungsi, os FROM servers WHERE id = :id");
            $get_info->execute([':id' => $id]);
            $server_data = $get_info->fetch(PDO::FETCH_ASSOC);
            $fungsi_log = $server_data ? $server_data['fungsi'] . " [OS: " . $server_data['os'] . "]" : 'Unknown';

            // 2. Jalankan query hapus data
            $sql = "DELETE FROM servers WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $sukses_delete = $stmt->execute([':id' => $id]);

            // TULIS LOG AKTIVITAS (DELETE)
            if ($sukses_delete) {
                write_log($conn, "Menghapus data server: " . $fungsi_log, "servers", $id);
            }

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
