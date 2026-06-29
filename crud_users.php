<?php
// CRUD Users (Create/Update/Delete) via POST
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

header('Content-Type: application/json; charset=utf-8');

function respond($ok, $message = '', $data = null) {
    echo json_encode([
        'ok' => $ok,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_POST['action'] ?? '';

    if (!in_array($action, ['create','update','delete'], true)) {
        respond(false, 'Invalid action.');
    }

    // Common inputs
    $nama = trim($_POST['nama'] ?? '');
    $usernameInput = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

    // password default untuk user baru (123456)
    $passwordDefaultPlain = '123456';
    $passwordHash = password_hash($passwordDefaultPlain, PASSWORD_DEFAULT);


    if ($action === 'create' || $action === 'update') {

        if ($nama === '' || $usernameInput === '' || $email === '' || $telepon === '') {
            respond(false, 'Semua field harus diisi: nama, username, email, telepon.');
        }
        if (!in_array($status, [0,1], true)) {
            respond(false, 'Status tidak valid.');
        }
        if ($role_id <= 0) {
            respond(false, 'role_id tidak valid.');
        }
    }


    if ($action === 'create') {
        $stmt = $conn->prepare("
            INSERT INTO users (nama, username, email, telepon, status, role_id, password)
            VALUES (:nama, :username, :email, :telepon, :status, :role_id, :password)
        ");
        $stmt->execute([
            ':nama' => $nama,
            ':username' => $usernameInput,
            ':email' => $email,
            ':telepon' => $telepon,
            ':status' => $status,
            ':role_id' => $role_id,
            ':password' => $passwordHash
        ]);


        respond(true, 'User berhasil ditambahkan.', [
            'id' => (int)$conn->lastInsertId()
        ]);
    }

    if ($action === 'update') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            respond(false, 'ID tidak valid.');
        }

        $stmt = $conn->prepare("
            UPDATE users
            SET nama = :nama,
                username = :username,
                email = :email,
                telepon = :telepon,
                status = :status,
                role_id = :role_id
            WHERE id = :id

        ");
        $stmt->execute([
            ':nama' => $nama,
            ':username' => $usernameInput,
            ':email' => $email,
            ':telepon' => $telepon,
            ':status' => $status,
            ':role_id' => $role_id,
            ':id' => $id
        ]);


        respond(true, 'User berhasil diupdate.');
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            respond(false, 'ID tidak valid.');
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        respond(true, 'User berhasil dihapus.');
    }

} catch (PDOException $e) {
    respond(false, 'Database error: ' . $e->getMessage());
}

?>

