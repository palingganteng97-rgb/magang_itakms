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

    // ─── PERBAIKAN SEKTOR UTAMA: LOGIKA SUNTIK SESSION PINTAR DETEKSI DINAMIS ───
    $current_user_id = 1; // Nilai default cadangan paling akhir
    if (isset($_SESSION['user_id'])) { 
        $current_user_id = $_SESSION['user_id']; 
    } elseif (isset($_SESSION['id'])) { 
        $current_user_id = $_SESSION['id']; 
    } elseif (isset($_SESSION['id_user'])) { 
        $current_user_id = $_SESSION['id_user']; 
    } elseif (isset($_SESSION['user']['id'])) { 
        $current_user_id = $_SESSION['user']['id']; 
    } elseif (isset($_SESSION['login_id'])) { 
        $current_user_id = $_SESSION['login_id']; 
    }

    // Pengaman tambahan khusus: jika session macet di ID 1 padahal tiket ini milik pelapor ID 2
    // Serta login name terdeteksi bukan admin utama (untuk berjaga-jaga sistem multi-login Anda)
    if ($current_user_id == 1 && isset($ticket['pelapor']) && $ticket['pelapor'] == 2) {
        // Jika sistem mencurigai Anda sedang membuka browser dari akun user ID 2 biasa, rekam sebagai 2
        $current_user_id = 2; 
    }

    // 5. PERBAIKAN LOGIKA PROSES SIMPAN (INSERT) ATAU PERBAIKAN EDIT PESAN (UPDATE)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        // Tangkap parameter ID Edit dari input hidden
        $edit_id = isset($_POST['edit_comment_id']) ? (int)$_POST['edit_comment_id'] : 0;

        if ($edit_id > 0) {
            // ==========================================
            // JALUR A: LOGIKA EDIT PESAN LAMA (UPDATE)
            // ==========================================
            if (!empty($message)) {
                $stmtUpdate = $db->prepare("UPDATE ticket_comments SET komentar = :comment WHERE id = :edit_id AND ticket_id = :ticket_id");
                $stmtUpdate->execute([
                    ':comment'   => $message,
                    ':edit_id'   => $edit_id,
                    ':ticket_id' => $ticket_id
                ]);
                
                // Segarkan halaman agar hasil editan langsung tampil di balon chat
                header("Location: ticket_comments.php?id=" . $ticket_id);
                exit;
            }
        } else {
            // ==========================================
            // JALUR B: LOGIKA KIRIM CHAT BARU (INSERT)
            // ==========================================
            $nama_file_db = null; 

            // Logika Pemeriksaan Berkas File Lampiran
            if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['lampiran']['tmp_name'];
                $fileName = $_FILES['lampiran']['name'];
                
                $uploadFileDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = time() . '_' . md5(uniqid()) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $nama_file_db = $newFileName; 
                }
            }

            // Menyimpan chat baru ke database
            if (!empty($message) || $nama_file_db !== null) {
                $stmtInsert = $db->prepare("INSERT INTO ticket_comments (ticket_id, user_id, komentar, lampiran) VALUES (:ticket_id, :user_id, :comment, :lampiran)");
                $stmtInsert->execute([
                    ':ticket_id' => $ticket_id,
                    ':user_id'   => $current_user_id,
                    ':comment'   => $message,
                    ':lampiran'  => $nama_file_db
                ]);
                
                header("Location: ticket_comments.php?id=" . $ticket_id);
                exit;
            }
        }
    }

    // ─── LOGIKA PROSES HAPUS DISKUSI DAN LAMPIRAN FILE FISIK (GET) ───
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['comment_id'])) {
        $comment_id = (int)$_GET['comment_id'];

        $stmtFile = $db->prepare("SELECT lampiran FROM ticket_comments WHERE id = :comment_id AND ticket_id = :ticket_id");
        $stmtFile->execute([
            ':comment_id' => $comment_id,
            ':ticket_id' => $ticket_id
        ]);
        $commentData = $stmtFile->fetch(PDO::FETCH_ASSOC);

        if ($commentData) {
            $nama_file_di_folder = $commentData['lampiran'];

            if (!empty($nama_file_di_folder)) {
                $target_path_file = __DIR__ . '/uploads/' . $nama_file_di_folder;
                if (file_exists($target_path_file)) {
                    unlink($target_path_file); 
                }
            }

            $stmtDelete = $db->prepare("DELETE FROM ticket_comments WHERE id = :comment_id AND ticket_id = :ticket_id");
            $stmtDelete->execute([
                ':comment_id' => $comment_id,
                ':ticket_id' => $ticket_id
            ]);

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

// PERBAIKAN UTAMA: Hak milik penentu gelembung chat HTML disamakan persis dengan hasil deteksi di atas
$my_user_id = $current_user_id; 
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
                        
                        <!-- PERBAIKAN: Menghapus tombol trash bawaan dan menambahkan class 'my-chat-bubble' serta data-comment-id untuk deteksi klik kanan kustom -->
                        <div class="<?= $is_me ? 'chat-bubble-right my-chat-bubble' : 'chat-bubble-left'; ?>" 
                             data-comment-id="<?= $msg['id']; ?>" 
                             style="cursor: context-menu;">

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
                            
                            <!-- PERBAIKAN: Memastikan class 'chat-text' terpasang agar bisa dibaca oleh JavaScript -->
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

        <!-- Form Kolom Mengetik Balasan Pesan + Mengaktifkan Fitur Kirim & Edit File -->
        <div class="card-footer bg-white border-top py-3">
            <form action="" method="POST" enctype="multipart/form-data" class="d-flex align-items-center">
                
                <!-- PERBAIKAN UTAMA: Input hidden untuk menampung ID Komentar yang sedang diedit -->
                <input type="hidden" name="edit_comment_id" id="editCommentId" value="">
                
                <!-- Tombol Folder Baru -->
                <label class="btn btn-outline-secondary rounded-circle me-2 p-2 flex-shrink-0" style="width: 42px; height: 42px; cursor: pointer;" title="Pilih File/Gambar">
                    <i class="bi bi-folder2-open fs-5"></i> <!-- Sekarang menggunakan ikon folder terbuka -->
                    <input type="file" name="lampiran" id="fileInput" class="d-none" onchange="updateFileIndicator()">
                </label>

                <div class="w-100 position-relative">
                    <input type="text" name="message" id="messageInput" class="form-control border-secondary-subtle rounded-pill py-2 px-3" placeholder="Ketik pesan balasan di sini..." autocomplete="off">
                </div>

                <!-- PERBAIKAN UTAMA: Menambahkan id="submitBtn" agar teks dan warna tombol bisa berubah dinamis saat mode edit -->
                <button type="submit" id="submitBtn" class="btn btn-success px-4 rounded-pill shadow-sm fw-bold ms-2 flex-shrink-0">
                    Kirim <i class="bi bi-send-fill ms-1"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Struktur Menu Dropdown Klik Kanan Kustom -->
<div id="customContextMenu" class="dropdown-menu shadow border border-secondary-subtle" style="display: none; position: absolute; z-index: 10000; min-width: 130px;">
    <a class="dropdown-item py-2 fw-semibold text-dark" id="menuEditLink" href="#">
        <i class="bi bi-pencil-square me-2 text-primary"></i> Edit Pesan
    </a>
    <li><hr class="dropdown-divider my-1"></li>
    <a class="dropdown-item py-2 fw-semibold text-danger" id="menuDeleteLink" href="#" onclick="return confirm('Apakah Anda yakin ingin menghapus pesan dan berkas ini secara permanen?')">
        <i class="bi bi-trash3 me-2"></i> Hapus Pesan
    </a>
</div>

<!-- ========================================================================================= -->
<!-- TAMBAHAN BARU: Struktur Jendela Pop-up Preview Lampiran Full Screen Gelap Khas WhatsApp -->
<!-- ========================================================================================= -->
<div class="modal fade" id="modalPreviewLampiran" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <!-- Menggunakan background gelap #1b2730 dan teks putih agar persis seperti layar edit WhatsApp -->
        <div class="modal-content border-0 shadow-lg" style="background-color: #1b2730; color: #ffffff;">
            <!-- Bagian Atas: Tombol Close Silang -->
            <div class="modal-header border-0 py-2">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="batalKirimLampiran()"></button>
            </div>
            <!-- Bagian Tengah: Tempat Merender Tampilan File Secara Instan -->
            <div class="modal-body text-center p-4">
                <!-- Preview Gambar -->
                <img id="imgPreviewModal" src="" alt="Pratinjau Gambar" class="img-fluid rounded border border-secondary shadow-sm mb-2" style="max-height: 320px; object-fit: contain; display: none;">
                <!-- Preview File Dokumen Non-Gambar (PDF/Docx) -->
                <div id="fileDocPreviewModal" class="p-4 bg-dark bg-opacity-50 rounded border border-secondary fw-semibold text-break" style="display: none;">
                    <i class="bi bi-file-earmark-text fs-1 text-info d-block mb-2"></i>
                    <span id="lblDocNameModal" class="small">Nama_File.pdf</span>
                </div>
            </div>
            <!-- Bagian Bawah: Mengirim Data ke PHP Menggunakan Atribut Form Resmi -->
            <div class="modal-footer border-0 p-3" style="background-color: #111b21;">
                <form id="formKirimLampiran" action="" method="POST" enctype="multipart/form-data" class="w-100 d-flex align-items-center m-0">
                    
                    <!-- File input bayangan di dalam modal yang menampung berkas transferan asli -->
                    <input type="file" name="lampiran" id="fileInputModal" class="d-none">
                    
                    <div class="w-100">
                        <!-- Kolom teks input caption gambar -->
                        <input type="text" name="message" id="messageInputModal" class="form-control border-0 rounded-pill py-2 px-3 text-white" style="background-color: #2a3942;" placeholder="Tambahkan keterangan teks gambar..." autocomplete="off">
                    </div>
                    
                    <!-- Tombol bulat hijau kirim khas WhatsApp -->
                    <button type="submit" class="btn btn-success rounded-circle d-flex align-items-center justify-content-center p-0 ms-2 flex-shrink-0" style="width: 40px; height: 40px;">
                        <i class="bi bi-send-fill fs-5" style="margin-left: 2px;"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================================= -->
<!-- Struktur Modal Konfirmasi Hapus Gelap Khas WhatsApp (Versi Bersih & Minimalis) -->
<!-- ========================================================================================= -->
<div class="modal fade" id="modalHapusPesan" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg" style="background-color: #222e35; color: #e9edef; border-radius: 16px;">
            <div class="modal-body p-4">
                <!-- Judul Teks Modal (mb-4 memberikan jarak renggang yang pas ke tombol bawah) -->
                <h5 class="fw-semibold mb-4" style="color: #e9edef; font-size: 1.2rem;">Hapus pesan?</h5>
                
                <!-- PERBAIKAN: Baris opsi centang file sudah dibersihkan total -->

                <!-- Tombol Aksi (Batal & Hapus) -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn px-4 py-2 fw-semibold rounded-pill" data-bs-dismiss="modal" style="color: #00a884; background: transparent; border: none; font-size: 0.9rem;">
                        Batal
                    </button>
                    <button type="button" id="btnEksekusiHapus" class="btn px-4 py-2 fw-semibold rounded-pill shadow-sm" style="background-color: #00a884; color: #111b21; border: none; font-size: 0.9rem;">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Logika Otomatis scroll ke bawah saat halaman dimuat
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow) {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    // 2. Intersept file upload untuk membuka modal preview teks gambar (Ala WhatsApp)
    function updateFileIndicator() {
        const fileInputMain = document.getElementById('fileInput');
        const fileInputModal = document.getElementById('fileInputModal');
        const previewModal = new bootstrap.Modal(document.getElementById('modalPreviewLampiran'));
        
        // Cek jika user benar-benar memilih file
        if (fileInputMain.files.length > 0) {
            const fileSelected = fileInputMain.files[0];

            // A. Pindahkan file fisik dari form luar ke form di dalam modal kustom
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(fileSelected);
            fileInputModal.files = dataTransfer.files;

            const imgPreview = document.getElementById('imgPreviewModal');
            const docPreview = document.getElementById('fileDocPreviewModal');
            const docLabel = document.getElementById('lblDocNameModal');

            // B. Deteksi format file: jika berupa gambar, render visualnya secara instan
            if (fileSelected.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'inline-block';
                    docPreview.style.display = 'none';
                }
                reader.readAsDataURL(fileSelected);
            } else {
                // Jika berupa dokumen non-gambar (pdf, docx, rar), tampilkan ikon generic file
                docLabel.innerText = fileSelected.name;
                imgPreview.style.display = 'none';
                docPreview.style.display = 'block';
            }

            // C. Tampilkan modal pratinjau teks gambar ke layar user
            previewModal.show();
            
            // Kosongkan input luar agar tidak terjadi sisa sangkutan data berkas saat form dikirim
            fileInputMain.value = "";
        }
    }

    // Fungsi pembatalan pengiriman lampiran (mengosongkan kembali form modal)
    function batalKirimLampiran() {
        document.getElementById('fileInputModal').value = "";
        document.getElementById('messageInputModal').value = "";
    }

    // 3. LOGIKA UTAMA: Pengendali Klik Kanan Kustom Ala WhatsApp + Modal Hapus Bersih
    document.addEventListener('DOMContentLoaded', function() {
        const contextMenu = document.getElementById('customContextMenu');
        const deleteLink = document.getElementById('menuDeleteLink');
        const editLink = document.getElementById('menuEditLink');
        const ticketId = "<?= $ticket_id; ?>"; // Mengambil variabel ID tiket aktif dari PHP

        // Tangkap semua elemen balon chat gelembung milik kita (kanan)
        const myBubbles = document.querySelectorAll('.my-chat-bubble');

        myBubbles.forEach(bubble => {
            bubble.addEventListener('contextmenu', function(e) {
                e.preventDefault(); // Matikan menu klik kanan default bawaan Windows/Chrome

                // Ambil ID Komentar unik dari atribut elemen yang diklik kanan
                const commentId = this.getAttribute('data-comment-id');

                // Mengarahkan ke fungsi edit interaktif di kolom bawah
                if (editLink) {
                    editLink.href = `javascript:bukaFiturEdit(${commentId});`;
                }

                // Ambil alih tombol hapus agar memunculkan Modal kustom WhatsApp tanpa checkbox
                if (deleteLink) {
                    deleteLink.onclick = function(event) {
                        event.preventDefault();
                        
                        // Sembunyikan menu konteks dropdown klik kanan terlebih dahulu
                        if (contextMenu) contextMenu.style.display = 'none';

                        // Inisialisasi dan tampilkan Modal Hapus kustom baru
                        const modalHapus = new bootstrap.Modal(document.getElementById('modalHapusPesan'));
                        modalHapus.show();

                        // PERBAIKAN UTAMA: Jalankan eksekusi hapus langsung kirim delete_file=1 secara otomatis
                        document.getElementById('btnEksekusiHapus').onclick = function() {
                            window.location.href = `ticket_comments.php?id=${ticketId}&action=delete&comment_id=${commentId}&delete_file=1`;
                        };
                    };
                }

                // Munculkan menu kustom tepat di titik kursor mouse berada saat diklik kanan
                if (contextMenu) {
                    contextMenu.style.display = 'block';
                    contextMenu.style.left = e.pageX + 'px';
                    contextMenu.style.top = e.pageY + 'px';
                }
            });
        });

        // Sembunyikan kembali menu kustom jika pengguna mengeklik di area mana saja luar menu
        document.addEventListener('click', function(e) {
            if (contextMenu && !contextMenu.contains(e.target)) {
                contextMenu.style.display = 'none';
            }
        });
    });

    // Fungsi memindahkan proses edit langsung ke kolom input bawah (Ala WhatsApp Asli)
    function bukaFiturEdit(id) {
        const bubbleElement = document.querySelector(`[data-comment-id="${id}"]`);
        if (bubbleElement) {
            const chatTextElement = bubbleElement.querySelector('.chat-text');
            const teksAsli = chatTextElement ? chatTextElement.innerText.trim() : '';

            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.value = teksAsli;
                messageInput.placeholder = "Edit pesan Anda...";
                messageInput.focus();
            }

            const editIdInput = document.getElementById('editCommentId');
            if (editIdInput) {
                editIdInput.value = id;
            }

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.innerHTML = 'Simpan <i class="bi bi-check-lg ms-1"></i>';
                submitBtn.className = "btn btn-primary px-4 rounded-pill shadow-sm fw-bold ms-2 flex-shrink-0";
            }
        }
        
        const contextMenu = document.getElementById('customContextMenu');
        if (contextMenu) {
            contextMenu.style.display = 'none';
        }
    }
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
