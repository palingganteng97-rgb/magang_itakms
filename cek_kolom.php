<?php
// MUAT PENGAMAN AUTH & RBAC
require_once __DIR__ . '/auth.php';
require_login(); 
require_once __DIR__ . '/helper_rbac.php';

// PROTEKSI KETAT: Hanya Super Admin (ID 1) yang diizinkan mengintip struktur database internal
if (!isset($_SESSION['user_role_id']) || (int)$_SESSION['user_role_id'] !== 1) {
    header('HTTP/1.1 403 Forbidden');
    echo "<h1>403 Forbidden</h1>Akses ditolak. Halaman ini hanya untuk Super Admin.";
    exit;
}

require_once __DIR__ . '/db.php';

try {
    // Mengintip nama kolom asli dari tabel activity_logs
    $stmtCheck = $conn->prepare("DESCRIBE activity_logs");
    $stmtCheck->execute();
    $columns = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background:black; color:lime; padding:20px; font-family:monospace; min-height:100vh;'>";
    echo "<h3>Struktur Kolom activity_logs Anda:</h3><pre>";
    print_r($columns);
    echo "</pre></div>";
} catch (PDOException $e) {
    echo "Gagal mengecek struktur tabel: " . $e->getMessage();
}
?>
