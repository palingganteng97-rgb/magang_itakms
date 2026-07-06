<?php
require_once 'auth.php';
require_once 'db.php'; // Pastikan koneksi database Anda termuat

try {
    // Ambil data log terbaru (contoh mengambil 50 log terakhir)
    $stmt = $conn->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 50");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($logs as $log) {
        echo "<tr>";
        echo "<td>#" . htmlspecialchars($log['id']) . "</td>";
        echo "<td>" . htmlspecialchars($log['created_at'] ?? $log['waktu_kejadian']) . "</td>";
        echo "<td><span class='badge bg-primary'><i class='fa fa-user'></i> " . htmlspecialchars($log['petugas'] ?? 'admin') . "</span></td>";
        echo "<td>" . htmlspecialchars($log['aktivitas']) . "</td>";
        echo "<td><span class='text-muted'>" . htmlspecialchars($log['nama_tabel']) . "</span></td>";
        echo "<td>" . ($log['data_id'] ? htmlspecialchars($log['data_id']) : '-') . "</td>";
        echo "<td><code>" . htmlspecialchars($log['ip_address']) . "</code></td>";
        echo "<td>" . htmlspecialchars(substr($log['browser'] ?? $log['peran'], 0, 7)) . "...</td>";
        echo "</tr>";
    }
} catch (Exception $e) {
    echo "<tr><td colspan='8' class='text-danger text-center'>Gagal memuat data log.</td></tr>";
}
?>
