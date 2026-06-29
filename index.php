<?php
// 1. Konfigurasi Database
$host = "10.10.6.59"; // Sesuaikan dengan IP di HeidiSQL Anda
$username = "root_host";    // Sesuaikan username database Anda
$password = "password";        // Sesuaikan password database Anda
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Query untuk mengambil data users
    $stmt = $conn->prepare("SELECT id, nama, username, email, telepon, status FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
