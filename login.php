<?php
require_once __DIR__ . '/db.php';

session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Username dan password wajib diisi.';
    } else {
        // Kolom password & status menyesuaikan struktur tabel users.
        // Pastikan di DB: nama kolom password adalah `password` (bukan password_hash)
        // dan status aktif bernilai 1.
        $stmt = $conn->prepare('SELECT id, nama, username, status, password FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = 'Username tidak ditemukan.';
        } elseif (!password_verify($password, $user['password'] ?? '')) {
            // Jika field password di tabel masih plaintext (belum hash), ganti menjadi: $password !== ($user['password'] ?? '')
            $errors[] = 'Password salah.';
        } elseif ((int)($user['status'] ?? 0) !== 1) {
            $errors[] = 'Akun tidak aktif.';
        } else {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'nama' => $user['nama'],
                'username' => $user['username']
            ];
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ITAKMS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        .auth-card { border: 0; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08); }
        .brand { color: #ffc107; }
        .btn-dark { background-color: #212529; border-color: #212529; }
        .form-control:focus { box-shadow: none; border-color: #212529; }
    </style>
</head>
<body>
<div class="container d-flex align-items-center" style="min-height: 100vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-center mb-4">
                <h3 class="brand mb-0"><i class="bi bi-speedometer2"></i> ITAKMS</h3>
                <div class="text-muted">Silakan login untuk masuk ke dashboard</div>
            </div>

            <div class="card auth-card">
                <div class="card-body p-4">
                    <h5 class="mb-3">Login</h5>

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
                            <label class="form-label">Username</label>
                            <input class="form-control" name="username" type="text" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input class="form-control" name="password" type="password" required>
                        </div>

                        <button class="btn btn-dark w-100" type="submit">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                        </button>

                        <div class="text-center mt-3">
                            <a href="register.php" class="link-dark text-decoration-none">Belum punya akun? Register</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center text-muted small mt-3">
                Akun harus berstatus Aktif.
            </div>
        </div>
    </div>
</div>
</body>
</html>

