<?php
// ==========================================
// get_comments.php (Wajib di folder yang sama)
// ==========================================
header('Content-Type: application/json');

// Matikan display error bawaan PHP agar tidak merusak format JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // 1. Ambil Autentikasi & Database
    require_once __DIR__ . '/auth.php';
    if (function_exists('require_login')) {
        // Jika fungsi login Anda menggunakan session_start internal, pastikan tidak crash
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
    }
    
    require_once __DIR__ . '/db.php';

    // 2. Tangkap Parameter ID Tiket
    $ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($ticket_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID Tiket tidak valid.']);
        exit;
    }

    // 3. Deteksi Variabel Koneksi Database ($conn atau $pdo)
    $db = isset($conn) ? $conn : (isset($pdo) ? $pdo : null);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal dideteksi.']);
        exit;
    }

    // 4. Deteksi Session User Aktif (Sama persis seperti file utama Anda)
    $current_user_id = 1; 
    if (isset($_SESSION['user_id'])) { $current_user_id = $_SESSION['user_id']; }
    elseif (isset($_SESSION['id'])) { $current_user_id = $_SESSION['id']; }
    elseif (isset($_SESSION['id_user'])) { $current_user_id = $_SESSION['id_user']; }
    elseif (isset($_SESSION['user']['id'])) { $current_user_id = $_SESSION['user']['id']; }
    elseif (isset($_SESSION['login_id'])) { $current_user_id = $_SESSION['login_id']; }

    // 5. Query Ambil Riwayat Chat dari Database
    $queryComments = "SELECT tc.id, tc.user_id, tc.komentar AS isi_chat, tc.lampiran, u.nama AS nama_komentator 
                      FROM ticket_comments tc
                      LEFT JOIN users u ON tc.user_id = u.id
                      WHERE tc.ticket_id = :ticket_id
                      ORDER BY tc.id ASC";
    
    $stmtComments = $db->prepare($queryComments);
    $stmtComments->execute([':ticket_id' => $ticket_id]);
    $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

    // 6. Kembalikan Output Berupa JSON Berhasil
    echo json_encode([
        'status' => 'success',
        'current_user_id' => $current_user_id,
        'data' => $comments
    ]);

} catch (Exception $e) {
    // Jika ada eror program, kirim pesan eror ke JSON
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
