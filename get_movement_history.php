<?php
$host     = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $asset_id = isset($_GET['asset_id']) ? intval($_GET['asset_id']) : 0;
    
    if ($asset_id > 0) {
        // QUERY: Mengambil data nama ruangan sekaligus kolom alasan
        $query = "SELECT r1.nama AS dari_ruang, r2.nama AS ke_ruang, am.tanggal, am.alasan 
                  FROM asset_movements am
                  LEFT JOIN rooms r1 ON am.room_from = r1.id
                  LEFT JOIN rooms r2 ON am.room_to = r2.id
                  WHERE am.asset_id = :asset_id 
                  ORDER BY am.id DESC"; // DESC agar riwayat terbaru muncul paling atas
                  
        $stmt = $conn->prepare($query);
        $stmt->execute([':asset_id' => $asset_id]);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($movements)) {
            $output = [];
            foreach ($movements as $mv) {
                $dari   = !empty($mv['dari_ruang']) ? htmlspecialchars($mv['dari_ruang']) : 'Awal';
                $ke     = !empty($mv['ke_ruang']) ? htmlspecialchars($mv['ke_ruang']) : '-';
                $txt_alasan = !empty($mv['alasan']) ? htmlspecialchars($mv['alasan']) : 'Tidak ada alasan spesifik.';
                
                // Menyusun layout html list agar rapi berjejer ke bawah
                $output[] = "
                <div class='p-2 mb-2 bg-white rounded border border-light shadow-sm'>
                    <div class='d-flex align-items-center justify-content-between mb-1'>
                        <span class='badge bg-light text-dark border small'><i class='bi bi-calendar3 me-1'></i> {$mv['tanggal']}</span>
                    </div>
                    <div class='fw-bold text-dark small mb-1'>
                        <span class='text-secondary'>{$dari}</span> 
                        <i class='bi bi-arrow-right text-primary mx-2'></i> 
                        <span class='text-success'>{$ke}</span>
                    </div>
                    <div class='text-muted' style='font-size: 0.75rem; line-height: 1.2;'>
                        <span class='fw-semibold text-secondary'>Alasan:</span> {$txt_alasan}
                    </div>
                </div>";
            }
            echo "<div class='pe-1' style='max-height: 180px; overflow-y: auto;'>" . implode("", $output) . "</div>";
        } else {
            echo "<span class='text-muted small italic'>Belum pernah berpindah ruangan.</span>";
        }
    } else {
        echo "<span class='text-danger small'>ID Asset tidak valid.</span>";
    }
} catch (PDOException $e) {
    echo "<span class='text-danger small'>Gagal memuat data log.</span>";
}
?>
