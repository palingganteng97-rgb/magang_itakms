<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php'; // HUBUNGKAN DENGAN AUTH.PHP UNTUK MENGAKSES FUNGSI WRITE_LOG()

try {
    // 1. Ambil ID User dan Username dari session sebelum dihancurkan demi kebutuhan teks log
    // Menyesuaikan dengan format $_SESSION['user'] pada login.php Anda
    $user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
    $username_log = isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : 'Unknown';

    // 2. TULIS LOG AKTIVITAS (LOGOUT BERHASIL)
    if ($user_id !== null) {
        write_log($conn, "Berhasil logout / keluar dari sistem ITAKMS", "users", $user_id);
    }
} catch (Exception $e) {
    // Abaikan jika ada kegagalan query log agar proses logout ke halaman login tidak macet
}

// 3. PROSES HANCURKAN SESSION SEPERTI SEMULA
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: login.php');
exit;
