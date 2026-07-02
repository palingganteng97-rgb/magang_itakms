<?php
require_once __DIR__ . '/auth.php';
require_login();

$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil daftar kategori dari database
    $catStmt = $conn->query("SELECT id, nama FROM knowledge_categories ORDER BY nama ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    // Eksekusi Simpan Data POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $judul = trim($_POST['judul']);
        $isi = $_POST['isi']; // Menampung hasil format teks HTML dari editor area
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $lampiran = null;

        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
            $lampiran = time() . '_' . uniqid() . '.' . $ext;
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            move_uploaded_file($_FILES['lampiran']['tmp_name'], 'uploads/' . $lampiran);
        }

        $sql = "INSERT INTO knowledge_articles (category_id, judul, isi, lampiran, status) VALUES (:cat, :judul, :isi, :lampiran, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':cat' => $category_id,
            ':judul' => $judul,
            ':isi' => $isi,
            ':lampiran' => $lampiran,
            ':status' => $status
        ]);

        header("Location: knowledge_articles.php?msg=success_create");
        exit;
    }
} catch (PDOException $e) {
    die("Koneksi atau query bermasalah: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Artikel Baru</title>
    
    <!-- STYLING VISUAL OFFLINE MANDIRI (MENGAMANKAN LAYOUT MODERN TANPA LINK INTERNET KELUAR) -->
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; color: #334155; }
        .container-box { max-width: 950px; margin: 2% auto; background: #ffffff; border-radius: 8px; border: 1px solid #e3e6f0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .card-header-box { padding: 15px 25px; border-bottom: 1px solid #e3e6f0; background: #ffffff; }
        .card-header-box h5 { margin: 0; font-size: 18px; font-weight: 700; color: #1e293b; }
        .card-body-box { padding: 25px; }
        
        /* Layout Flexbox 2 Kolom Sejajar */
        .flex-row-box { display: flex; gap: 25px; flex-wrap: wrap; }
        .col-kiri { flex: 1; min-width: 320px; display: flex; flex-direction: column; gap: 15px; }
        .col-kanan { flex: 1.4; min-width: 380px; display: flex; flex-direction: column; }
        
        /* Pengaturan Elemen Formulir */
        .form-group-box { display: flex; flex-direction: column; gap: 6px; }
        .form-group-box label { font-weight: 600; color: #475569; font-size: 13px; }
        .form-control-box { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; outline: none; color: #334155; background-color: #fff; transition: border-color 0.15s; }
        .form-control-box:focus { border-color: #4e73df; }
        
        /* Toolbar Format Gaya Microsoft Word */
        .word-toolbar { background: #f8f9fc; border: 1px solid #cbd5e1; border-bottom: none; padding: 6px 10px; border-top-left-radius: 6px; border-top-right-radius: 6px; display: flex; gap: 6px; align-items: center; }
        .btn-w { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 5px 14px; font-size: 13px; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.15s; outline: none; }
        .btn-w:hover { background-color: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
        
        /* Area Mengetik Utama */
        #editorArea { height: 195px; min-height: 195px; max-height: 195px; border: 1px solid #cbd5e1; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; background: #fff; padding: 12px; overflow: auto; outline: none; box-sizing: border-box; font-size: 14px; line-height: 1.5; color: #212529; }
        #editorArea:focus { border-color: #4e73df; box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1); }
        
        /* KEMBALIKAN KE INLINE: Menyelaraskan CSS agar patuh pada kontrol script pemutus spasi */
        #editorArea u {
            display: inline !important;
            text-decoration: underline !important;
            text-decoration-color: #000000 !important;
            text-underline-offset: 3px !important;
            text-decoration-skip-ink: none !important;
            color: #000000 !important;
        }

        #editorArea s, #editorArea del {
            display: inline !important;
            text-decoration: line-through !important;
            color: #000000 !important;
        }

        /* Bagian Tombol Aksi Bawah */
        .footer-box { text-align: right; border-top: 1px solid #e3e6f0; padding-top: 15px; margin-top: 25px; }
        .btn-action { border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-block; box-sizing: border-box; }
        .btn-batal { background-color: #64748b; color: white; margin-right: 8px; }
        .btn-batal:hover { background-color: #475569; }
        .btn-simpan { background-color: #1cc88a; color: white; }
        .btn-simpan:hover { background-color: #17a673; }
    </style>
</head>

<body>
    <div class="container-box">
        <div class="card-header-box">
            <h5>Tambah Artikel Baru</h5>
        </div>
        <div class="card-body-box">
            <form id="articleForm" method="POST" action="" enctype="multipart/form-data">
                <div class="flex-row-box">
                    
                    <!-- KOLOM KIRI (Informasi Utama) -->
                    <div class="col-kiri">
                        <div class="form-group-box">
                            <label>Pilih Kategori</label>
                            <select name="category_id" class="form-control-box">
                                <option value="">-- Tanpa Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group-box">
                            <label>Judul Artikel *</label>
                            <input type="text" name="judul" class="form-control-box" required placeholder="Masukkan judul artikel...">
                        </div>
                        <div class="form-group-box">
                            <label>File Lampiran</label>
                            <input type="file" name="lampiran" class="form-control-box" style="padding: 7px 10px;">
                        </div>
                        <div class="form-group-box">
                            <label>Status Publikasi</label>
                            <select name="status" class="form-control-box">
                                <option value="1">1 (Publish)</option>
                                <option value="0">0 (Draft)</option>
                            </select>
                        </div>
                    </div>

                    <!-- KOLOM KANAN (Word Editor dengan Status Deteksi Tombol Aktif) -->
                    <div class="col-kanan">
                        <div class="form-group-box" style="height: 100%; display: flex; flex-direction: column;">
                            <label>Isi Artikel *</label>
                            
                            <!-- Toolbar Format Word Dengan Desain Huruf Sesuai Fungsi -->
                            <div class="word-toolbar">
                                <!-- Ditambahkan font-weight: bold agar huruf B menjadi tebal -->
                                <button type="button" id="btn-bold" class="btn-w" onclick="formatText('bold')" style="font-weight: bold;">B</button>
                                
                                <!-- Ditambahkan font-style: italic agar huruf I menjadi miring -->
                                <button type="button" id="btn-italic" class="btn-w" onclick="formatText('italic')" style="font-style: italic;">I</button>
                                
                                <!-- Ditambahkan text-decoration: underline agar huruf U bergaris bawah -->
                                <button type="button" id="btn-underline" class="btn-w" onclick="formatText('underline')" style="text-decoration: underline;">U</button>
                                
                                <!-- Ditambahkan text-decoration: line-through agar huruf S dicoret -->
                                <button type="button" id="btn-strike" class="btn-w" onclick="formatText('strikeThrough')" style="text-decoration: line-through;">S</button>
                                
                                <span style="border-left: 1px solid #cbd5e1; margin: 0 4px; height: 20px;"></span>
                                <button type="button" id="btn-ul" class="btn-w" onclick="formatText('insertUnorderedList')">• List</button>
                                <button type="button" id="btn-ol" class="btn-w" onclick="formatText('insertOrderedList')">1. List</button>
                            </div>

                            <!-- Area Tempat Mengetik Konten Utama -->
                            <div id="editorArea" contenteditable="true"></div>
                            
                            <!-- Input tersembunyi penampung hasil ketikan HTML untuk dikirim ke PHP -->
                            <input type="hidden" name="isi" id="hiddenIsi">
                        </div>
                    </div>

                </div>

                <!-- Tombol Aksi Formulir -->
                <div class="footer-box">
                    <a href="knowledge_articles.php" class="btn-action btn-batal">Batal</a>
                    <button type="submit" class="btn-action btn-simpan">Simpan Artikel</button>
                </div>
            </form>
        </div>
    </div>

<!-- =========================================================================
     JAVASCRIPT KONTROL EDITOR WORD & SENSOR INDIKATOR AKTIF DI LOCALHOST
     ========================================================================= -->
<script>
    // 1. Fungsi Utama Penyetel Format Gaya Teks (Bebas Internet & Ringan)
    function formatText(command) {
        document.execCommand(command, false, null);
        document.getElementById('editorArea').focus();
        checkButtonStates();
    }

    // 2. Fungsi Detektor Otomatis Status Kursor / Format Gaya yang Sedang Berjalan
    function checkButtonStates() {
        const buttons = {
            'btn-bold': 'bold',
            'btn-italic': 'italic',
            'btn-underline': 'underline',
            'btn-strike': 'strikeThrough',
            'btn-ul': 'insertUnorderedList',
            'btn-ol': 'insertOrderedList'
        };

        for (let id in buttons) {
            let btn = document.getElementById(id);
            if (btn) {
                if (document.queryCommandState(buttons[id])) {
                    btn.style.backgroundColor = '#e2e8f0'; 
                    btn.style.color = '#2563eb';           
                    btn.style.borderColor = '#94a3b8';     
                } else {
                    btn.style.backgroundColor = '#ffffff'; 
                    btn.style.color = '#475569';
                    btn.style.borderColor = '#cbd5e1';
                }
            }
        }
    }

    // 3. Pasang Event Listener untuk Memantau Gerakan Kursor / Ketikan Secara Real-time
    document.getElementById('editorArea').addEventListener('keyup', checkButtonStates);
    document.getElementById('editorArea').addEventListener('click', checkButtonStates);

    // 4. Sinkronisasi Data Sebelum Dikirim ke Engine PHP Database
    document.getElementById('articleForm').addEventListener('submit', function() {
        var content = document.getElementById('editorArea').innerHTML;
        document.getElementById('hiddenIsi').value = content;
    });

    // 5. PERBAIKAN TOTAL: Memaksa spasi ikut kereset keluar dari SEMUA tag format (U, S, DEL, STRIKE)
    document.getElementById('editorArea').addEventListener('keydown', function(e) {
        if (e.keyCode === 32) { // Jika mendeteksi tombol Spasi
            let selection = window.getSelection();
            if (!selection.rangeCount) return;
            
            let range = selection.getRangeAt(0);
            let currentNode = range.startContainer;
            let parentNode = currentNode.parentNode;
            
            // Daftar semua tag dekorasi yang dipicu browser untuk Underline dan Strikethrough
            const targetTags = ['U', 'S', 'DEL', 'STRIKE', 'FONT'];
            
            // Lacak ke atas apakah kursor berada di dalam salah satu tag dekorasi tersebut
            while (parentNode && parentNode.id !== 'editorArea') {
                if (targetTags.includes(parentNode.tagName)) {
                    e.preventDefault(); // Stop spasi bawaan browser yang merusak garis

                    // Masukkan karakter spasi murni di luar tag dekorasi
                    let cleanSpace = document.createTextNode('\u00A0');
                    range.setEndAfter(parentNode);
                    range.collapse(false);
                    range.insertNode(cleanSpace);
                    
                    // Pindahkan kursor ke posisi setelah spasi agar ketikan normal kembali
                    range.setStartAfter(cleanSpace);
                    range.setEndAfter(cleanSpace);
                    selection.removeAllRanges();
                    selection.addRange(range);
                    
                    checkButtonStates();
                    return;
                }
                parentNode = parentNode.parentNode;
            }
        }
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
