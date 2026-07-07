<?php
// CRUD Roles (Create/Update/Delete) via AJAX
// Output selalu JSON

require_once __DIR__ . '/auth.php';

// Jika ingin proteksi ketat: pastikan hanya admin/login yang boleh akses.
if (function_exists('require_login')) {
    require_login();
}

require_once __DIR__ . '/db.php';
// MUAT HELPER RBAC UNTUK VALIDASI AJAX
require_once __DIR__ . '/helper_rbac.php';

header('Content-Type: application/json; charset=utf-8');

function respond(bool $ok, string $message = '', mixed $data = null): void {
    echo json_encode([
        'ok' => $ok,
        'message' => $message,
        'data' => $data,
    ]);
    exit;
}

// PROTEKSI KETAT RBAC KHUSUS API JSON: Hanya Super Admin (ID 1) yang diizinkan mengelola data Role
if (!isset($_SESSION['user_role_id']) || (int)$_SESSION['user_role_id'] !== 1) {
    respond(false, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola data peran.');
}

$action = $_POST['action'] ?? '';
if (!in_array($action, ['create', 'update', 'delete'], true)) {
    respond(false, 'Invalid action.');
}

try {
    if ($action === 'create') {
        $nama = trim($_POST['nama'] ?? '');
        $keterangan_raw = $_POST['keterangan'] ?? null;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($nama === '') {
            respond(false, 'Nama Peran wajib diisi.');
        }
        if (!in_array($status, [0, 1], true)) {
            respond(false, 'Status tidak valid.');
        }

        $keterangan = $keterangan_raw === null ? null : trim((string)$keterangan_raw);
        if ($keterangan === '') {
            $keterangan = null; // nullable
        }

        $stmt = $conn->prepare(
            "INSERT INTO roles (nama, keterangan, status) VALUES (:nama, :keterangan, :status)"
        );
        $sukses_create = $stmt->execute([
            ':nama' => $nama,
            ':keterangan' => $keterangan,
            ':status' => $status,
        ]);

        // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
        if ($sukses_create) {
            $new_role_id = (int)$conn->lastInsertId();
            write_log($conn, "Menambahkan data role baru: " . $nama, "roles", $new_role_id);
            respond(true, 'Role berhasil ditambahkan.', ['id' => $new_role_id]);
        }

        respond(false, 'Gagal menambahkan role.');
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $keterangan_raw = $_POST['keterangan'] ?? null;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($id <= 0) {
            respond(false, 'ID tidak valid.');
        }
        if ($nama === '') {
            respond(false, 'Nama Peran wajib diisi.');
        }
        if (!in_array($status, [0, 1], true)) {
            respond(false, 'Status tidak valid.');
        }

        $keterangan = $keterangan_raw === null ? null : trim((string)$keterangan_raw);
        if ($keterangan === '') {
            $keterangan = null; // nullable
        }

        $stmt = $conn->prepare(
            "UPDATE roles SET nama = :nama, keterangan = :keterangan, status = :status WHERE id = :id"
        );
        $sukses_update = $stmt->execute([
            ':nama' => $nama,
            ':keterangan' => $keterangan,
            ':status' => $status,
            ':id' => $id,
        ]);

        // TULIS LOG AKTIVITAS (UPDATE)
        if ($sukses_update) {
            write_log($conn, "Mengubah informasi data role menjadi: " . $nama, "roles", $id);
            respond(true, 'Role berhasil diupdate.');
        }

        respond(false, 'Gagal memperbarui role.');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            respond(false, 'ID tidak valid.');
        }

        // 1. Ambil nama role terlebih dahulu sebelum dihapus permanen untuk kebutuhan teks log
        $get_name = $conn->prepare("SELECT nama FROM roles WHERE id = :id");
        $get_name->execute([':id' => $id]);
        $nama_role = $get_name->fetchColumn() ?: 'Unknown';

        // 2. Jalankan query hapus data
        $stmt = $conn->prepare("DELETE FROM roles WHERE id = :id");
        $sukses_delete = $stmt->execute([':id' => $id]);

        // TULIS LOG AKTIVITAS (DELETE)
        if ($sukses_delete) {
            write_log($conn, "Menghapus data role: " . $nama_role, "roles", $id);
            respond(true, 'Role berhasil dihapus.');
        }

        respond(false, 'Gagal menghapus role.');
    }

    respond(false, 'Unhandled action.');

} catch (PDOException $e) {
    respond(false, 'Database error: ' . $e->getMessage());
}
?>
