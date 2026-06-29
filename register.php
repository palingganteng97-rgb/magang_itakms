<?php
require_once __DIR__ . '/db.php';

session_start();

header('Content-Type: text/html; charset=utf-8');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '') $errors[] = 'Nama wajib diisi.';
    if ($username === '') $errors[] = 'Username wajib diisi.';
    if ($email === '') $errors[] = 'Email wajib diisi.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (!$errors) {
        // cek username/email unik
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([':username' => $username, ':email' => $email]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            $errors[] = 'Username atau email sudah terdaftar.';
        } else {
            // NOTE: password column harus ada di tabel users
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $status = 1;

            $stmtIns = $conn->prepare(
                'INSERT INTO users (nama, username, email, telepon, status, password_hash)
                 VALUES (:nama, :username, :email, :telepon, :status, :password_hash)'
            );

            // telepon belum ada di form register => kosong
            $stmtIns->execute([
                ':nama' => $nama,
                ':username' => $username,
                ':email' => $email,
                ':telepon' => '',
                ':status' => $status,
                ':password_hash' => $hash
            ]);

            $success = 'Registrasi berhasil. Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ITAKMS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        .auth-card { border: 0; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08); }
        .brand { color: #ffc107; }
        .form-control:focus { box-shadow: none; border-color: #212529; }
        .btn-dark { background-color: #212529; border-color: #212529; }
    </style>
</head>
<body>
<div class="container px-2 d-flex align-items-center" style="min-height: 100vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-center mb-4">
                <h3 class="brand mb-0"><i class="bi bi-speedometer2"></i> ITAKMS</h3>
                <div class="text-muted">Buat akun untuk masuk</div>
            </div>

            <div class="card auth-card">
                <div class="card-body p-4">
                    <h5 class="mb-3">Register</h5>

                    <?php if ($success): ?>
                        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input class="form-control" name="nama" type="text" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input class="form-control" name="username" type="text" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input class="form-control" name="password" type="password" required>
                            <div class="form-text">Minimal 6 karakter.</div>
                        </div>

                        <button class="btn btn-dark w-100" type="submit">
                            <i class="bi bi-person-plus me-1"></i> Daftar
                        </button>

                        <div class="text-center mt-3">
                            <a href="login.php" class="link-dark text-decoration-none">Sudah punya akun? Login</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center text-muted small mt-3">
                Dengan mendaftar, Anda menyetujui penggunaan sistem.
            </div>
        </div>
    </div>
</div>
</body>
</html>

