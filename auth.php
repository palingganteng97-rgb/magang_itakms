<?php
// Shared authentication helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }

    // Sinkronisasi session global rbac (Memastikan $_SESSION['user_role_id'] selalu terisi dari data user)
    if (isset($_SESSION['user']['role_id'])) {
        $_SESSION['user_role_id'] = $_SESSION['user']['role_id'];
    }

    // =========================================================================
    // TRICK OTOMATIS: REKAM JEJAK KUNJUNGAN HALAMAN TANPA UBAH FILE LAIN
    // =========================================================================
    // 1. Ambil nama file yang sedang dibuka (contoh: dashboard.php, assets.php)
    $running_file = basename($_SERVER['PHP_SELF']);

    // 2. Cegah pencatatan ganda khusus saat membuka halaman log atau logout agar tidak spam data
    if ($running_file !== 'activity_logs.php' && $running_file !== 'logout.php') {
        
        // Buat koneksi database darurat di dalam fungsi agar tidak bergantung pada urutan require_once db.php
        try {
            $log_conn = new PDO("mysql:host=10.10.6.59;dbname=magang_itakms;charset=utf8mb4", "root_host", "password");
            $log_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Ubah nama file menjadi teks yang rapi (contoh: asset_movements.php -> Asset Movements)
            $nama_halaman = ucwords(str_replace(['_', '.php'], [' ', ''], $running_file));
            $aktivitas_teks = "Membuka halaman " . $nama_halaman;

            // Pastikan fungsi write_log tersedia sebelum dipanggil agar tidak fatal error
            if (function_exists('write_log')) {
                write_log($log_conn, $aktivitas_teks, $running_file, null);
            }
        } catch (Exception $e) {
            error_log("Auto Logger Gagal: " . $e->getMessage());
        }
    }
}

// =========================================================================
// DEKLARASI FUNGSI GLOBAL LOGGER UTAMA
// =========================================================================
function write_log($conn, $aktivitas, $nama_tabel = null, $data_id = null) {
    // Menangkap info session user sistem Anda (Mendukung array 'user' atau string)
    if (isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
    } elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        $user_id = 1; // Fallback default admin
    }
    
    // Deteksi IP Address secara akurat
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    // Deteksi informasi Browser / Perangkat
    $browser = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

    try {
        $sql = "INSERT INTO activity_logs (user_id, aktivitas, nama_tabel, data_id, ip_address, browser, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $aktivitas, $nama_tabel, $data_id, $ip_address, $browser]);
    } catch (Exception $e) {
        error_log("Gagal menulis activity log: " . $e->getMessage());
    }
}
?>
