<?php
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
