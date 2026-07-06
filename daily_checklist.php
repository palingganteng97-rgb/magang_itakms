<?php
// ====================================================================
// LOGIKA BACKEND UTAMA - DAILY CHECKLISTS WITH USER OPTION SELECT
// ====================================================================

// FIX: Menghapus session_start() ganda agar tidak bertabrakan dengan auth.php
require_once __DIR__ . '/auth.php';
require_login(); 
require_once __DIR__ . '/db.php'; 

$currentFile = 'daily_checklist.php';
$message = '';
$message_type = '';

// Tangkap parameter tanggal dari form pencarian di tabel (Gunakan metode GET)
$filter_tanggal = isset($_GET['search_date']) ? $_GET['search_date'] : '';

// Mengambil ID user dari session, jika kosong otomatis menggunakan ID default 1 (untuk development/uji coba)
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // === PROSES TAMBAH DATA (CREATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $tanggal = $_POST['tanggal'];
        $item    = $_POST['item'];
        // KUNCI BARU: Menangkap user_id hasil pilihan dari elemen select modal
        $chosen_user_id = $_POST['user_id']; 
        // Status otomatis dikunci 0 (Pending) saat pertama kali ditambah lewat modal
        $status  = 0; 

        try {
            // Menggunakan variabel $chosen_user_id hasil kiriman form
            $sql = "INSERT INTO daily_checklists (tanggal, item, status, user_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $sukses = $stmt->execute([$tanggal, $item, $status, $chosen_user_id]);
            
            // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
            if ($sukses) {
                $new_checklist_id = $conn->lastInsertId();
                write_log($conn, "Menambahkan item daily checklist baru: " . $item, "daily_checklists", $new_checklist_id);
            }

            // Pertahankan parameter filter tanggal jika sedang melakukan pencarian saat tambah data
            $redirect_url = !empty($filter_tanggal) ? "daily_checklist.php?search_date=" . urlencode($filter_tanggal) : "daily_checklist.php";
            echo "<script>window.location.href = '$redirect_url';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal menambah Checklist: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES UBAH DATA (UPDATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id      = $_POST['id'];
        $tanggal = $_POST['tanggal'];
        $item    = $_POST['item'];
        // KUNCI BARU: Menangkap perubahan user_id hasil pilihan dari elemen select modal edit
        $chosen_user_id = $_POST['user_id']; 

        try {
            // Menyisipkan kolom user_id ke dalam pembaharuan query update database
            $sql = "UPDATE daily_checklists SET tanggal = ?, item = ?, user_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $sukses_update = $stmt->execute([$tanggal, $item, $chosen_user_id, $id]);
            
            // TULIS LOG AKTIVITAS (UPDATE)
            if ($sukses_update) {
                write_log($conn, "Memperbarui item daily checklist: " . $item, "daily_checklists", $id);
            }

            // Pertahankan parameter filter tanggal jika sedang melakukan pencarian saat update data
            $redirect_url = !empty($filter_tanggal) ? "daily_checklist.php?search_date=" . urlencode($filter_tanggal) : "daily_checklist.php";
            echo "<script>window.location.href = '$redirect_url';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal memperbarui Checklist: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES TOGGLE STATUS INSTAN (CHECKBOX KLIK DI TABEL) ===
    if (isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
        $id = $_POST['id'];
        // Membalikkan status: jika 1 jadi 0, jika 0 jadi 1
        $new_status = $_POST['current_status'] == 1 ? 0 : 1;
        $status_label = ($new_status == 1) ? 'Selesai' : 'Pending';

        try {
            $sql = "UPDATE daily_checklists SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $sukses_toggle = $stmt->execute([$new_status, $id]);
            
            // TULIS LOG AKTIVITAS (TOGGLE STATUS)
            if ($sukses_toggle) {
                write_log($conn, "Mengubah status item daily checklist menjadi " . $status_label, "daily_checklists", $id);
            }

            // Pertahankan parameter filter tanggal jika sedang melakukan pencarian saat klik checkbox
            $redirect_url = !empty($filter_tanggal) ? "daily_checklist.php?search_date=" . urlencode($filter_tanggal) : "daily_checklist.php";
            echo "<script>window.location.href = '$redirect_url';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal memperbarui status: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES HAPUS DATA (DELETE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = $_POST['id'];
        
        try {
            // 1. Ambil nama item terlebih dahulu untuk kebutuhan log sebelum datanya dihapus permanen
            $get_item = $conn->prepare("SELECT item FROM daily_checklists WHERE id = ?");
            $get_item->execute([$id]);
            $checklist_data = $get_item->fetch(PDO::FETCH_ASSOC);
            $nama_item = $checklist_data ? $checklist_data['item'] : 'Unknown';

            // 2. Jalankan query hapus data
            $sql = "DELETE FROM daily_checklists WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $sukses_delete = $stmt->execute([$id]);
            
            // TULIS LOG AKTIVITAS (DELETE)
            if ($sukses_delete) {
                write_log($conn, "Menghapus item daily checklist: " . $nama_item, "daily_checklists", $id);
            }

            // Pertahankan parameter filter tanggal jika sedang melakukan pencarian saat hapus data
            $redirect_url = !empty($filter_tanggal) ? "daily_checklist.php?search_date=" . urlencode($filter_tanggal) : "daily_checklist.php";
            echo "<script>window.location.href = '$redirect_url';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal menghapus Checklist: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// ====================================================================
// PEMBACAAN DATA UTAMA (MENGGUNAKAN LEFT JOIN KE TABEL USERS)
// ====================================================================
try {
    if (!empty($filter_tanggal)) {
        // Jika ada filter tanggal, jalankan query WHERE dengan Prepared Statements agar aman dari SQL Injection
        $sql_select = "SELECT dc.*, u.username FROM daily_checklists dc 
                       LEFT JOIN users u ON dc.user_id = u.id 
                       WHERE dc.tanggal = ?
                       ORDER BY dc.id DESC";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->execute([$filter_tanggal]);
        $daily_checklists = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Jika tidak ada filter tanggal, tampilkan seluruh rekam data kegiatan secara normal
        $sql_select = "SELECT dc.*, u.username FROM daily_checklists dc 
                       LEFT JOIN users u ON dc.user_id = u.id 
                       ORDER BY dc.tanggal DESC, dc.id DESC";
        $daily_checklists = $conn->query($sql_select)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $daily_checklists = [];
    $message = "Gagal memuat data database: " . $e->getMessage();
    $message_type = "danger";
}
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
    
    <link href="sidebar-admin.css" rel="stylesheet">

    <style>
        /* ==================================================== */
        /* CONFIG LAYOUT DESKTOP / LAPTOP (Lebar Layar >= 768px)*/
        /* ==================================================== */
        @media (min-width: 768px) {
            body {
                display: flex !important;
                overflow: auto !important;
            }
            .offcanvas-md.sidebar-fixed {
                position: fixed !important;
                top: 0;
                left: 0;
                min-height: 100vh !important;
                max-height: 100vh !important;
                width: 280px !important;
                z-index: 1040;
                transform: none !important;
                visibility: visible !important;
                overflow: hidden !important;
            }
            .menu-scroll-container {
                display: block !important;
                max-height: calc(100vh - 160px) !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            .menu-scroll-container::-webkit-scrollbar {
                display: none !important;
            }
            /* Menghitung lebar konten di desktop */
            .main-content {
                margin-left: 280px !important;
                width: calc(100% - 280px) !important;
                min-height: 100vh !important;
                display: block !important; /* Kembalikan sifat block di laptop */
            }
            .offcanvas-backdrop, .offcanvas-backdrop.show {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
        }

        /* ==================================================== */
        /* CONFIG LAYOUT MOBILE / HP (Lebar Layar < 768px)     */
        /* ==================================================== */
        @media (max-width: 767.98px) {
            body {
                overflow: auto !important;
                position: relative !important;
                display: block !important; /* Hancurkan flexbox desktop di HP */
            }
            /* Kunci container induk luar agar di HP melepas kuncian flex */
            .d-md-flex {
                display: block !important; 
            }
            .sidebar-fixed {
                position: fixed !important;
                width: 280px !important;
                height: 100vh !important;
            }
            .menu-scroll-container {
                max-height: none !important;
                overflow-y: visible !important;
            }
            /* SOLUSI UTAMA: Hancurkan pembatasan tinggi pendek di HP */
            .main-content {
                width: 100% !important;
                margin-left: 0 !important;
                min-height: calc(100vh - 70px) !important; /* Ambil sisa tinggi layar HP */
                display: block !important;
                height: auto !important; /* Biarkan memanjang alami mengikuti jumlah data tabel */
            }
            .offcanvas-backdrop.show {
                display: block !important;
                opacity: 0 !important;
                background-color: transparent !important;
                visibility: visible !important;
            }
        }

        /* ==================================================== */
        /* KUSTOMISASI MODEREN MENU STYLE                      */
        /* ==================================================== */
        .sidebar-fixed .nav-link {
            color: #adb5bd !important;
            font-weight: 500;
            padding: 10px 16px !important;
            margin: 2px 0;
            transition: all 0.25s ease-in-out;
            border-left: 4px solid transparent;
        }
        .sidebar-fixed .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-left-color: rgba(255, 255, 255, 0.3);
            padding-left: 20px !important;
        }
        .sidebar-fixed .nav-link.active-style {
            color: #ffffff !important;
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.25) 0%, rgba(13, 110, 253, 0.05) 100%) !important;
            border-left: 4px solid #0d6efd !important;
            box-shadow: inset 0 0 8px rgba(13, 110, 253, 0.15);
            font-weight: 600;
        }
        .sidebar-fixed .nav-link i {
            font-size: 1.1rem;
            transition: transform 0.25s ease;
        }
        .sidebar-fixed .nav-link:hover i {
            transform: scale(1.15);
        }
        .sidebar-fixed .nav-link.active-style i {
            color: #0d6efd !important;
        }
    </style>

</head>
<body>

<!-- ==================================================================== -->
<!-- BAGIAN A: TOMBOL PEMICU SIDEBAR (HANYA MUNCUL DI MOBILE / HP)        -->
<!-- ==================================================================== -->
<div class="d-md-none p-3 bg-dark d-flex justify-content-between align-items-center w-100 position-sticky top-0 shadow-sm" style="z-index: 1050;">
    <h5 class="text-warning mb-0 fw-bold"><i class="bi bi-speedometer2 me-2"></i> ITAKMS</h5>
    <button class="btn btn-warning btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarFlexible" aria-controls="sidebarFlexible">
        <i class="bi bi-list fs-5"></i>
    </button>
</div>

<!-- ==================================================================== -->
<!-- BAGIAN B: INDUK KONTEN SIDEBAR RESPONSIVE (FLEKSIBEL HP & DESKTOP)   -->
<!-- ==================================================================== -->
<div class="offcanvas-md offcanvas-start d-flex flex-column sidebar-fixed p-3 text-bg-dark border-end border-secondary" 
     tabindex="-1" id="sidebarFlexible" aria-labelledby="sidebarFlexibleLabel">

  <!-- 1. Header Laci Menu (Hanya Muncul di Layar HP saat Laci Terbuka) -->
  <div class="offcanvas-header border-bottom border-secondary d-md-none">
    <h5 class="offcanvas-title text-warning fw-bold" id="sidebarFlexibleLabel">
        <i class="bi bi-speedometer2 me-2"></i> ITAKMS
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarFlexible" aria-label="Close"></button>
  </div>
  
  <!-- 2. Judul Utama Navigasi (Hanya Muncul di Desktop/Laptop) -->
  <h4 class="text-center mb-4 text-warning fw-bold pt-2 d-none d-md-block">
      <i class="bi bi-speedometer2 me-2"></i> ITAKMS
  </h4>
  
  <!-- 3. AREA MENU TENGAH (Otomatis & Fleksibel untuk Semua Opsi Berbasis Fungsi PHP) -->
  <?php
  // Ambil nama file yang sedang aktif saat ini secara real-time
  $currentFile = basename($_SERVER['PHP_SELF']);

  // Fungsi pintar untuk otomatis mencetak class 'active-style' jika nama file cocok
  if (!function_exists('checkActiveMenu')) {
      function checkActiveMenu($targetFile, $currentFile) {
          return ($currentFile === $targetFile) ? 'active-style' : '';
      }
  }
  ?>
  
    <?php include __DIR__ . '/sidebar.php'; ?>

<!-- AREA UTAMA KONTEN -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-3 px-md-4 pt-4 offset-md-4 offset-lg-3">
    <!-- KARTU DAN TABEL UTAMA DAILY CHECKLIST -->
    <div class="card shadow-sm border-0 rounded-3">
        <!-- Header Konten Utama -->
        <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 border-bottom border-light">
            <h5 class="mb-0 text-dark fw-bold">
                <i class="bi bi-card-checklist text-primary me-2"></i> Daily Checklist
            </h5>
            
            <!-- FORM PENCARIAN MENURUT TANGGAL (SEJAJAR HORIZONTAL) -->
            <form action="daily_checklist.php" method="GET" class="d-flex align-items-center gap-2 m-0 flex-grow-1 flex-sm-grow-0" style="max-width: 400px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="date" name="search_date" class="form-control bg-light border-start-0 text-dark" value="<?= htmlspecialchars($filter_tanggal); ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-secondary fw-semibold px-3">Cari</button>
                <?php if (!empty($filter_tanggal)): ?>
                    <a href="daily_checklist.php" class="btn btn-sm btn-light border text-danger" title="Reset Pencarian">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Tombol Tambah yang Adaptif -->
            <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
            <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
            <button class="btn btn-primary btn-sm px-2 px-md-3 fw-semibold shadow-sm rounded-2" data-bs-toggle="modal" data-bs-target="#modalAddChecklist">
                <i class="bi bi-plus-lg me-md-1"></i><span class="d-none d-md-inline"> Tambah Item</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Body Utama Tempat Tabel Data -->
        <div class="card-body p-0">
            <!-- Notifikasi Status Berhasil/Gagal -->
            <?php if(!empty($message)): ?>
                <div class="m-3 alert alert-<?= $message_type; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi <?= $message_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                    <?= $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

<!-- Tabel Responsif yang Sejajar -->
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0" style="width: 100%;">
                    <thead class="table-light text-muted small text-uppercase border-bottom">
                        <tr>
                            <th style="width: 7%;" class="text-center ps-3 d-none d-md-table-cell">ID</th>
                            <th style="width: 15%; min-width: 90px;" class="ps-3 ps-md-0">Tanggal</th>
                            <th style="width: 44%;">Item Kegiatan</th>
                            <th style="width: 12%;" class="text-center">Status</th>
                            <th style="width: 12%;" class="d-none d-lg-table-cell">Petugas</th>
                            
                            <!-- Fleksibel Otomatis: Hanya tampilkan kolom header Aksi jika user bukan Teknisi & Viewer -->
                            <?php if (!in_array($userRole, ['Teknisi', 'Viewer'])): ?>
                            <th style="width: 10%; min-width: 80px;" class="text-center">Aksi</th>
                            <?php endif; ?>
                            
                            <!-- PINDAH KE SINI: Kolom Check berada di paling kanan -->
                            <th style="width: 7%;" class="text-center pe-3">Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($daily_checklists) == 0): ?>
                            <tr>
                                <td colspan="<?= (!in_array($userRole, ['Teknisi', 'Viewer'])) ? '7' : '6'; ?>" class="text-center text-muted py-5">
                                    <i class="bi bi-clipboard-x display-6 d-block mb-2 text-secondary"></i> 
                                    <?= !empty($filter_tanggal) ? 'Tidak ada aktivitas pada tanggal tersebut.' : 'Belum ada aktivitas hari ini.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($daily_checklists as $row): ?>
                            <tr>
                                <!-- ID -->
                                <td class="text-center ps-3 d-none d-md-table-cell">
                                    <span class="text-muted small fw-bold">#<?= $row['id']; ?></span>
                                </td>
                                <!-- Tanggal -->
                                <td class="ps-3 ps-md-0">
                                    <div class="text-dark small lh-sm">
                                        <i class="bi bi-calendar3 d-none d-md-inline me-1 text-muted"></i>
                                        <span class="d-md-none fw-semibold"><?= date('d/m/y', strtotime($row['tanggal'])); ?></span>
                                        <span class="d-none d-md-inline"><?= date('d M Y', strtotime($row['tanggal'])); ?></span>
                                    </div>
                                </td>
                                <!-- Item Kegiatan -->
                                <td>
                                    <div class="text-wrap fw-semibold small text-break text-dark" style="max-width: 100%;">
                                        <?= htmlspecialchars($row['item']); ?>
                                    </div>
                                </td>
                                <!-- Status Badge -->
                                <td class="text-center">
                                    <?= $row['status'] == 1 
                                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small-badge"><i class="bi bi-check2 me-1"></i>Selesai</span>' 
                                        : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 small-badge"><i class="bi bi-hourglass-split me-1"></i>Pending</span>'; 
                                    ?>
                                </td>
                                <!-- Nama Petugas -->
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-light text-secondary border px-2 py-1.5 small">
                                        <i class="bi bi-person-fill me-1 text-primary"></i>
                                        <?= htmlspecialchars($row['username'] ?? 'ID: ' . $row['user_id']); ?>
                                    </span>
                                </td>
                                
                                <!-- Kolom Aksi (Edit & Hapus): Disembunyikan total untuk Teknisi & Viewer (X) -->
                                <?php if (!in_array($userRole, ['Teknisi', 'Viewer'])): ?>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1 justify-content-center w-100">
                                        
                                        <!-- Fleksibel Otomatis: Cek Akses Update ('U') -->
                                        <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'U', $userRole)): ?>
                                        <button class="btn btn-sm btn-light text-warning border-0 p-1 p-md-2" data-bs-toggle="modal" data-bs-target="#modalEditChecklist<?= $row['id']; ?>" title="Ubah Data">
                                            <i class="bi bi-pencil-square fs-6"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Fleksibel Otomatis: Cek Akses Delete ('D') -->
                                        <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'D', $userRole)): ?>
                                        <button class="btn btn-sm btn-light text-danger border-0 p-1 p-md-2" data-bs-toggle="modal" data-bs-target="#modalDeleteChecklist<?= $row['id']; ?>" title="Hapus Data">
                                            <i class="bi bi-trash3 fs-6"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                    </div>
                                </td>
                                <?php endif; ?>
                                
                                <!-- PINDAH KE SINI: Opsi Tombol Cek Status Instan (Isi Checklist) -->
                                <td class="text-center pe-3">
                                    <form action="daily_checklist.php" method="POST" class="m-0">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?= $row['status']; ?>">
                                        
                                        <?php if($row['status'] == 1): ?>
                                            <!-- Fleksibel: Kunci tombol submit jika role adalah Viewer -->
                                            <button type="submit" class="btn btn-link p-0 text-success border-0 shadow-none" title="Tandai Pending" <?= ($userRole === 'Viewer') ? 'disabled' : ''; ?>>
                                                <i class="bi bi-check-square-fill fs-5"></i>
                                            </button>
                                        <?php else: ?>
                                            <!-- Fleksibel: Kunci tombol submit jika role adalah Viewer -->
                                            <button type="submit" class="btn btn-link p-0 text-secondary border-0 shadow-none" title="Tandai Selesai" <?= ($userRole === 'Viewer') ? 'disabled' : ''; ?>>
                                                <i class="bi bi-square fs-5"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- ==================================================================== -->
<!-- TAHAP 2: MODAL TAMBAH DATA CHECKLIST (SEJAJAR HORIZONTAL)           -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalAddChecklist" tabindex="-1" aria-labelledby="modalAddChecklistLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="daily_checklist.php" method="POST">
          <!-- Hidden Input Action untuk Handler CRUD PHP -->
          <input type="hidden" name="action" value="create">
          
          <!-- Header Modal -->
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-primary" id="modalAddChecklistLabel">
              <i class="bi bi-plus-circle-fill me-2"></i>Registrasi Item Checklist Baru
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <!-- Body Modal -->
          <div class="modal-body pt-3">
            
            <!-- Baris Tanggal Kegiatan -->
            <div class="row mb-3 align-items-center">
                <label for="tanggal" class="col-4 col-form-label small fw-bold text-secondary text-end">Tanggal Kegiatan</label>
                <div class="col-8">
                    <input type="date" id="tanggal" name="tanggal" class="form-control form-control-sm text-dark" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <!-- Baris Pilihan Petugas/User Dinamis -->
            <div class="row mb-3 align-items-center">
                <label for="user_id" class="col-4 col-form-label small fw-bold text-secondary text-end">Pilih Petugas (User)</label>
                <div class="col-8">
                    <select id="user_id" name="user_id" class="form-select form-select-sm text-dark" required>
                        <option value="" disabled selected>-- Pilih Petugas --</option>
                        <?php
                        try {
                            $query_user = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
                            $users_list = $query_user->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users_list as $user) {
                                // Otomatis menandai user yang sedang login saat ini sebagai pilihan awal
                                $selected = ($user['id'] == $current_user_id) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($user['id']) . "' $selected>";
                                echo htmlspecialchars($user['username']);
                                echo "</option>";
                            }
                        } catch (Exception $e) {
                            echo "<option value='' disabled>Error: " . htmlspecialchars($e->getMessage()) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Baris Item Kegiatan / Deskripsi Tugas -->
            <div class="row mb-2 align-items-start">
                <label for="item" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Item Kegiatan</label>
                <div class="col-8">
                    <textarea id="item" name="item" class="form-control form-control-sm text-dark" rows="3" placeholder="Contoh: Periksa kapasitas storage server utama dan log backup..." required></textarea>
                </div>
            </div>
            
          </div>
          
          <!-- Tombol Aksi Mandiri -->
          <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm btn-primary fw-bold px-4 shadow-sm">Simpan Sistem</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- KUNCI MUTLAK: Awal Perulangan Modal (Harus sama dengan perulangan di tabel main Anda) -->
<?php foreach($daily_checklists as $row): ?>

    <!-- ==================================================================== -->
    <!-- 1. MODAL EDIT DATA CHECKLIST                                         -->
    <!-- ==================================================================== -->
    <div class="modal fade" id="modalEditChecklist<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalEditChecklistLabel<?= $row['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form action="daily_checklist.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                
                <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="modalEditChecklistLabel<?= $row['id']; ?>">
                    <i class="bi bi-pencil-square text-warning me-2"></i> Ubah Item Checklist #<?= $row['id']; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body pt-3">
                    <!-- Baris Tanggal Kegiatan -->
                    <div class="row mb-3 align-items-center">
                        <label for="tanggal_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Tanggal Kegiatan</label>
                        <div class="col-8">
                            <input type="date" id="tanggal_<?= $row['id']; ?>" name="tanggal" class="form-control form-control-sm text-dark" value="<?= $row['tanggal']; ?>" required>
                        </div>
                    </div>

                    <!-- Baris Pilihan Ubah Petugas/User -->
                    <div class="row mb-3 align-items-center">
                        <label for="user_id_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Petugas (User)</label>
                        <div class="col-8">
                            <select id="user_id_<?= $row['id']; ?>" name="user_id" class="form-select form-select-sm text-dark" required>
                                <?php
                                try {
                                    $query_user = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
                                    $users_list = $query_user->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($users_list as $user) {
                                        $selected = ($user['id'] == $row['user_id']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($user['id']) . "' $selected>";
                                        echo htmlspecialchars($user['username']);
                                        echo "</option>";
                                    }
                                } catch (Exception $e) {
                                    echo "<option value='' disabled>Error: " . htmlspecialchars($e->getMessage()) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Baris Item Kegiatan -->
                    <div class="row mb-3 align-items-start">
                        <label for="item_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Item Kegiatan</label>
                        <div class="col-8">
                            <textarea id="item_<?= $row['id']; ?>" name="item" class="form-control form-control-sm text-dark" rows="3" required><?= htmlspecialchars($row['item']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-toggle="modal">Tutup</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-bold text-white px-4 shadow-sm">Update Tugas</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <!-- ==================================================================== -->
    <!-- 2. MODAL HAPUS DATA CHECKLIST (POST METHOD - AMAN DAN SEJAJAR)        -->
    <!-- ==================================================================== -->
    <div class="modal fade" id="modalDeleteChecklist<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalDeleteChecklistLabel<?= $row['id']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <form action="daily_checklist.php" method="POST">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $row['id']; ?>">
              
              <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="modalDeleteChecklistLabel<?= $row['id']; ?>">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              
              <div class="modal-body pt-3">
                <div class="row align-items-center">
                    <div class="col-sm-2 text-center text-sm-end mb-3 mb-sm-0">
                        <i class="bi bi-trash3 text-danger display-6"></i>
                    </div>
                    <div class="col-sm-10">
                        <p class="mb-1 text-secondary small fw-bold">Anda akan menghapus item checklist berikut:</p>
                        <h6 class="fw-bold text-dark mb-0">"Item #<?= $row['id']; ?> - <?= htmlspecialchars($row['item']); ?>"</h6>
                        <p class="text-muted small mt-2 mb-0">Tindakan ini bersifat permanen. Rekam aktivitas checklist harian ini akan dihapus sepenuhnya dari database sistem.</p>
                    </div>
                </div>
              </div>
              
              <div class="modal-footer border-top-0 pt-2">
                <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-danger fw-bold px-4 shadow-sm">
                    <i class="bi bi-trash me-1"></i>Ya, Hapus Data
                </button>
              </div>
          </form>
        </div>
      </div>
    </div>

<!-- KUNCI MUTLAK: Akhir Perulangan Modal (Pastikan baris ini ada dan tidak terhapus) -->
<?php endforeach; ?>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
