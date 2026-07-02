<?php
// 1. LOGIKA PELACAK ERROR
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. LOGIKA AUTENTIKASI & KONEKSI DATABASE
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/db.php'; 

// 3. LOGIKA VALIDASI PARAMETER ID TIKET DI URL (?id=...)
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticket_id <= 0) {
    die("ID Tiket tidak valid.");
}

try {
    // Otomatis memilih variabel koneksi database ($conn atau $pdo) dari file db.php
    $db = isset($conn) ? $conn : (isset($pdo) ? $pdo : null);
    if (!$db) {
        die("Error: Variabel koneksi database tidak ditemukan. Periksa file db.php Anda.");
    }

    // 4. LOGIKA PENGAMBILAN DETAIL DATA TIKET UTAMA (UNTUK HEADER CHAT)
    $stmtTicket = $db->prepare("SELECT t.*, u.nama AS nama_pelapor FROM tickets t LEFT JOIN users u ON t.pelapor = u.id WHERE t.id = :ticket_id");
    $stmtTicket->execute([':ticket_id' => $ticket_id]);
    $ticket = $stmtTicket->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Tiket tidak ditemukan.");
    }

    // 5. KODE PENUH LOGIKA PROSES SIMPAN CHAT DAN UPLOAD FILE LAMPIRAN (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $current_user_id = $_SESSION['user_id'] ?? ($_SESSION['id'] ?? 1); 
        $nama_file_db = null; // Default awal kosong jika user tidak kirim file

        // A. Logika Pemeriksaan Berkas File Lampiran
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['lampiran']['tmp_name'];
            $fileName = $_FILES['lampiran']['name'];
            
            // Tentukan folder penyimpanan di server lokal Anda
            $uploadFileDir = __DIR__ . '/uploads/';
            
            // Otomatis buat folder 'uploads' jika belum tersedia di folder proyek
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            // Ambil ekstensi berkas asli (misal: jpg, png, pdf)
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Logika Pengacakan Nama File agar unik dan tidak menimpa file lama
            $newFileName = time() . '_' . md5(uniqid()) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            // Logika Pemindahan File Fisik ke Folder Proyek 'uploads'
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $nama_file_db = $newFileName; // Simpan nama acak ini ke database
            }
        }

        // B. Logika Menyimpan ke Database (Hanya jalan jika teks terisi ATAU ada file lampiran)
        if (!empty($message) || $nama_file_db !== null) {
            $stmtInsert = $db->prepare("INSERT INTO ticket_comments (ticket_id, user_id, komentar, lampiran) VALUES (:ticket_id, :user_id, :comment, :lampiran)");
            $stmtInsert->execute([
                ':ticket_id' => $ticket_id,
                ':user_id' => $current_user_id,
                ':comment' => $message,
                ':lampiran' => $nama_file_db
            ]);
            
            // Segarkan halaman agar pesan teks atau gambar yang baru dikirim langsung muncul di layar
            header("Location: ticket_comments.php?id=" . $ticket_id);
            exit;
        }
    }

    // 6. LOGIKA PENGAMBILAN SELURUH RIWAYAT PESAN (DARI TABEL TICKET_COMMENTS)
    $queryComments = "SELECT tc.id, tc.ticket_id, tc.user_id, tc.komentar AS isi_chat, tc.lampiran, u.nama AS nama_komentator 
                      FROM ticket_comments tc
                      LEFT JOIN users u ON tc.user_id = u.id
                      WHERE tc.ticket_id = :ticket_id
                      ORDER BY tc.id ASC";
    $stmtComments = $db->prepare($queryComments);
    $stmtComments->execute([':ticket_id' => $ticket_id]);
    $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

// Digunakan di bagian HTML untuk mencocokkan balon chat (kanan/kiri)
$my_user_id = $_SESSION['user_id'] ?? ($_SESSION['id'] ?? 1); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Itakms</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f0f2f5; }
        .chat-container {
            background-color: #efeae2;
            height: 55vh;
            overflow-y: auto;
            padding: 20px;
            border-radius: 8px;
        }
        .chat-bubble-left {
            background-color: #ffffff; color: #000000; padding: 8px 12px;
            border-radius: 0px 12px 12px 12px; max-width: 75%;
            box-shadow: 0 1px 0.5px rgba(0,0,0,0.13); margin-bottom: 12px; align-self: flex-start;
        }
        .chat-bubble-right {
            background-color: #d9fdd3; color: #000000; padding: 8px 12px;
            border-radius: 12px 0px 12px 12px; max-width: 75%;
            box-shadow: 0 1px 0.5px rgba(0,0,0,0.13); margin-bottom: 12px; align-self: flex-end;
        }
        .chat-time { font-size: 0.72rem; color: #667781; text-align: right; margin-top: 4px; display: block; }
        .chat-sender { font-size: 0.82rem; font-weight: 700; color: #111b21; margin-bottom: 2px; display: block; }
        /* Style Gambar Miniatur Lampiran */
        .chat-img-preview { max-width: 200px; max-height: 200px; border-radius: 8px; margin-bottom: 5px; display: block; }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 800px;">
    <!-- Bagian Tombol Navigasi Atas -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="tickets.php" class="btn btn-sm btn-outline-secondary fw-bold shadow-sm bg-white text-dark">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Tiket
        </a>
        <span class="badge bg-dark px-3 py-2 fw-bold text-white fs-6">No. Tiket: <?= htmlspecialchars($ticket['nomor'] ?? ''); ?></span>
    </div>

    <!-- Informasi Detail Masalah -->
    <div class="card border border-secondary-subtle shadow-sm mb-3">
        <div class="card-body bg-light py-3">
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-exclamation-circle-fill text-primary me-2"></i><?= htmlspecialchars($ticket['judul'] ?? ''); ?>
            </h5>
            <p class="text-muted mb-0 small">
                Pelapor: <span class="text-dark fw-bold"><?= htmlspecialchars($ticket['nama_pelapor'] ?? 'Tidak Diketahui'); ?></span> 
            </p>
        </div>
    </div>

    <!-- Jendela Utama Room Chat WhatsApp -->
    <div class="card border border-secondary-subtle shadow-sm">
        <div class="card-body p-0">
            <div class="chat-container d-flex flex-column" id="chatWindow">
                
                <?php if (count($comments) === 0): ?>
                    <div class="text-center my-auto text-muted py-5">
                        <i class="bi bi-chat-left-text fs-1 d-block mb-2 text-secondary-subtle"></i>
                        Belum ada riwayat percakapan pada tiket ini.
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $msg): ?>
                        <?php $is_me = ($msg['user_id'] == $my_user_id); ?>
                        
                        <div class="<?= $is_me ? 'chat-bubble-right' : 'chat-bubble-left'; ?>">
                            <?php if (!$is_me): ?>
                                <span class="chat-sender text-success"><?= htmlspecialchars($msg['nama_komentator'] ?? 'User'); ?></span>
                            <?php endif; ?>
                            
                            <!-- LOGIKA TAMPILAN FILE LAMPIRAN DI DALAM BALON CHAT -->
                            <?php if (!empty($msg['lampiran'])): ?>
                                <?php 
                                $ext = strtolower(pathinfo($msg['lampiran'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                    <!-- Jika file berupa gambar, tampilkan gambarnya langsung -->
                                    <a href="uploads/<?= $msg['lampiran']; ?>" target="_blank">
                                        <img src="uploads/<?= $msg['lampiran']; ?>" class="chat-img-preview border shadow-sm" alt="Lampiran">
                                    </a>
                                <?php else: ?>
                                    <!-- Jika file dokumen/lainnya, tampilkan link download -->
                                    <div class="mb-2">
                                        <a href="uploads/<?= $msg['lampiran']; ?>" target="_blank" class="btn btn-sm btn-light border text-dark fw-semibold">
                                            <i class="bi bi-file-earmark-arrow-down-fill text-primary"></i> Dokumen Lampiran (.<?= $ext; ?>)
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($msg['isi_chat'])): ?>
                                <div class="chat-text text-wrap"><?= nl2br(htmlspecialchars($msg['isi_chat'])); ?></div>
                            <?php endif; ?>
                            
                            <span class="chat-time">
                                Terkirim <?php if ($is_me): ?><i class="bi bi-check2-all text-primary ms-1"></i><?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
        
        <!-- Form Kolom Mengetik Balasan Pesan + Mengaktifkan Fitur Kirim File -->
        <div class="card-footer bg-white border-top py-3">
            <form action="" method="POST" enctype="multipart/form-data" class="d-flex align-items-center">
                
                <!-- Tombol Clip Kertas Cantik untuk Input File -->
                <label class="btn btn-outline-secondary rounded-circle me-2 p-2 flex-shrink-0" style="width: 42px; height: 42px; cursor: pointer;" title="Pilih File/Gambar">
                    <i class="bi bi-paperclip fs-5"></i>
                    <input type="file" name="lampiran" id="fileInput" class="d-none" onchange="updateFileIndicator()">
                </label>
                
                <div class="w-100 position-relative">
                    <input type="text" name="message" id="messageInput" class="form-control border-secondary-subtle rounded-pill py-2 px-3" placeholder="Ketik pesan balasan di sini..." autocomplete="off">
                </div>

                <button type="submit" class="btn btn-success px-4 rounded-pill shadow-sm fw-bold ms-2 flex-shrink-0">
                    Kirim <i class="bi bi-send-fill ms-1"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Otomatis scroll ke bawah
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow) {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    // Fungsi mengganti teks input sebagai penanda jika file sukses dipilih
    function updateFileIndicator() {
        const fileInput = document.getElementById('fileInput');
        const messageInput = document.getElementById('messageInput');
        if (fileInput.files.length > 0) {
            messageInput.placeholder = "📎 File terpilih: " + fileInput.files[0].name;
            messageInput.focus();
        }
    }
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
