<?php
require_once __DIR__ . '/db.php';

session_start();

header('Content-Type: text/html; charset=utf-8');

$errors = [];
$success = '';

// Catatan implementasi:
// - Karena aplikasi belum punya kolom/flow reset password (token/expiry), fitur ini hanya:
//   *memverifikasi user ada (username/email) dan status aktif* lalu menampilkan instruksi.
// - Anda bisa mengganti menjadi mekanisme email token jika sudah menyiapkan kolom token.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';

    if ($email === '' || $newPassword === '') {
        $errors[] = 'Email dan password baru wajib diisi.';
    } else {
        // Cek user ada
        $stmt = $conn->prepare('SELECT id, status FROM users WHERE (username = :username OR email = :email) LIMIT 1');
        $stmt->execute([
            ':username' => $username,
            ':email' => $email
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Jangan bocorkan apakah user ada.
            $errors[] = 'Jika akun terdaftar, Anda akan menerima instruksi untuk reset password.';
        } elseif ((int)($user['status'] ?? 0) !== 1) {
            $errors[] = 'Akun tidak aktif.';
        } else {
            // Karena belum ada mekanisme token/email, tampilkan sukses generik.
            $success = 'Jika akun terdaftar, Anda akan menerima instruksi untuk reset password. (Demo: belum kirim email)';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - ITAKMS</title>

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
<div class="container px-2 d-flex align-items-center" style="min-height: 100vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-12 col-md-6 col-lg-5">
            <div class="text-center mb-3">
                <h3 class="brand mb-0" style="font-size:1.25rem;"><i class="bi bi-shield-lock"></i> ITAKMS</h3>
                <div class="text-muted">Reset password</div>
            </div>

            <div class="card auth-card">
                <div class="card-body p-4">
                    <h5 class="mb-3">Lupa Password</h5>

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
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input class="form-control" name="password" type="password" required>
                            <div class="form-text">Demo: reset password langsung (tanpa token/email verification).</div>
                        </div>

                        <button class="btn btn-dark w-100" type="submit">
                            <i class="bi bi-unlock me-1"></i> Ubah Password
                        </button>

                        <div class="text-center mt-3">
                            <a href="login.php" class="link-dark text-decoration-none">Kembali ke Login</a>
                        </div>
                    </form>


                    <div class="text-center text-muted small mt-3">
                        Catatan: fitur ini placeholder karena belum ada kolom token reset.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

