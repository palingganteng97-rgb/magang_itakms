<?php
// 1. Ambil otentikasi login sesuai proyek Anda
require_once __DIR__ . '/auth.php';
require_login();

// 2. Konfigurasi Database (Sesuaikan dengan data db.php Anda)
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    // Menggunakan koneksi PDO sesuai dashboard utama Anda
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil parameter aksi dari URL form
    $action = $_GET['action'] ?? '';

    // LOGIKA TAMBAH DATA (CREATE)
    if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // Menyiapkan query insert ke tabel tickets
        $query = "INSERT INTO tickets (nomor, judul, deskripsi, room_id, prioritas, status, pelapor, created_at) 
                  VALUES (:nomor, :judul, :deskripsi, :room_id, :prioritas, 1, :pelapor, NOW())";
        
        $stmt = $conn->prepare($query);
        
        // Eksekusi pengikatan data dari form modal tambah
        $stmt->execute([
            ':nomor'     => $_POST['nomor'],
            ':judul'     => $_POST['judul'],
            ':deskripsi' => $_POST['deskripsi'],
            ':room_id'   => $_POST['room_id'],
            ':prioritas' => $_POST['prioritas'],
            ':pelapor'   => $_SESSION['user_id'] ?? 1 // Mengambil ID dari session login, jika kosong default ke 1
        ]);
    }
    
    // LOGIKA PERBARUI DATA (UPDATE)
    elseif ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $query = "UPDATE tickets 
                  SET judul = :judul, status = :status, teknisi = :teknisi 
                  WHERE id = :id";
                  
        $stmt = $conn->prepare($query);
        
        $stmt->execute([
            ':judul'   => $_POST['judul'],
            ':status'  => $_POST['status'],
            ':teknisi' => !empty($_POST['teknisi']) ? $_POST['teknisi'] : null, // Set NULL jika teknisi belum dipilih
            ':id'      => $_POST['id']
        ]);
    }

    // Alihkan halaman kembali ke daftar antrean tiket setelah proses database berhasil
    header("Location: tickets.php");
    exit;

} catch (PDOException $e) {
    // Tampilkan pesan error jika query database gagal dijalankan
    die("Gagal memproses data database: " . $e->getMessage());
}
