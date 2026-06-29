<?php
// CRUD Roles (Create/Update/Delete) via AJAX
// Output selalu JSON

require_once __DIR__ . '/auth.php';

// Jika ingin proteksi ketat: pastikan hanya admin/login yang boleh akses.
// Di project ini, auth.php biasanya berisi helper require_login().
if (function_exists('require_login')) {
    require_login();
}

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(bool $ok, string $message = '', mixed $data = null): void {
    echo json_encode([
        'ok' => $ok,
        'message' => $message,
        'data' => $data,
    ]);
    exit;
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
        $stmt->execute([
            ':nama' => $nama,
            ':keterangan' => $keterangan,
            ':status' => $status,
        ]);

        respond(true, 'Role berhasil ditambahkan.', ['id' => (int)$conn->lastInsertId()]);
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
        $stmt->execute([
            ':nama' => $nama,
            ':keterangan' => $keterangan,
            ':status' => $status,
            ':id' => $id,
        ]);

        respond(true, 'Role berhasil diupdate.');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            respond(false, 'ID tidak valid.');
        }

        $stmt = $conn->prepare("DELETE FROM roles WHERE id = :id");
        $stmt->execute([':id' => $id]);

        respond(true, 'Role berhasil dihapus.');
    }

    respond(false, 'Unhandled action.');

} catch (PDOException $e) {
    respond(false, 'Database error: ' . $e->getMessage());
}

