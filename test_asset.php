<?php
require_once __DIR__ . '/auth.php'; // Proteksi login jika diperlukan
require_once __DIR__ . '/db.php';   // Mengambil variabel $conn
require_once __DIR__ . '/asset.php'; // Mengambil fungsi getAllAssets()

// Ambil data asset dari database
$assets = getAllAssets($conn);

// Tampilkan data mentah ke layar browser untuk memastikan berhasil
echo "<pre>";
print_r($assets);
echo "</pre>";
