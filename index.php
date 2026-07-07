<?php
// MUAT PENGAMAN AUTH & RBAC TERLEBIH DAHULU
require_once __DIR__ . '/auth.php';
require_login(); 
require_once __DIR__ . '/helper_rbac.php';

// PROTEKSI KETAT: Berdasarkan matriks, modul User Management hanya boleh diakses oleh Super Admin (ID 1)
if (!isset($_SESSION['user_role_id']) || (int)$_SESSION['user_role_id'] !== 1) {
    header('HTTP/1.1 403 Forbidden');
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2>403 - Akses Ditolak</h2>";
    echo "<p>Halaman User Management ini hanya dapat diakses oleh Super Admin.</p>";
    echo "<a href='dashboard.php'>Kembali ke Dashboard</a>";
    echo "</div>";
    exit;
}

// 1. Konfigurasi Database
$host = "10.10.6.59"; 
$username = "root_host";    
$password = "password";        
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // 2. Query untuk mengambil data users
    $stmt = $conn->prepare("SELECT id, nama, username, email, telepon, status FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Users</title>
    <!-- Menggunakan Bootstrap agar tampilan tabel rapi -->
    <link href="https://jsdelivr.net" rel="stylesheet">
</head>
<body class="container mt-5">

    <h2 class="mb-4">Daftar Pengguna (Users)</h2>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Email</th>
                <th>Telepon</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['nama']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['telepon']) ?></td>
                        <td>
                            <span class="badge <?= $user['status'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                                <?= $user['status'] == 1 ? 'Aktif' : 'Non-Aktif' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
