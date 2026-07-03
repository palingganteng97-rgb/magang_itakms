<?php
require_once __DIR__ . '/auth.php';
require_login();

// =========================================================================
// 1. KONFIGURASI DATABASE & PROSES CRUD
// =========================================================================
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Paginasi
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- PROSES TAMBAH DATA (CREATE) ---
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $judul = trim($_POST['judul']);
        $isi = $_POST['isi'];
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $lampiran = '';

        // Handle upload lampiran
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
            $lampiran = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['lampiran']['tmp_name'], 'uploads/' . $lampiran);
        }

        $insSql = "INSERT INTO knowledge_articles (category_id, judul, isi, lampiran, status) VALUES (:cat, :judul, :isi, :lampiran, :status)";
        $insStmt = $conn->prepare($insSql);
        $insStmt->execute([
            ':cat' => $category_id,
            ':judul' => $judul,
            ':isi' => $isi,
            ':lampiran' => $lampiran,
            ':status' => $status
        ]);
        header("Location: knowledge_articles.php?msg=success_create");
        exit;
    }

    // --- PROSES EDIT DATA (UPDATE) ---
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id = (int)$_POST['id'];
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $judul = trim($_POST['judul']);
        $isi = $_POST['isi'];
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        
        // Ambil nama file lama
        $oldSql = "SELECT lampiran FROM knowledge_articles WHERE id = :id";
        $oldStmt = $conn->prepare($oldSql);
        $oldStmt->execute([':id' => $id]);
        $lampiran = $oldStmt->fetchColumn() ?: '';

        // Cek jika ada file lampiran baru
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            if (!empty($lampiran) && file_exists('uploads/' . $lampiran)) {
                @unlink('uploads/' . $lampiran);
            }
            $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
            $lampiran = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['lampiran']['tmp_name'], 'uploads/' . $lampiran);
        }

        $updSql = "UPDATE knowledge_articles SET category_id = :cat, judul = :judul, isi = :isi, lampiran = :lampiran, status = :status WHERE id = :id";
        $updStmt = $conn->prepare($updSql);
        $updStmt->execute([
            ':cat' => $category_id,
            ':judul' => $judul,
            ':isi' => $isi,
            ':lampiran' => $lampiran,
            ':status' => $status,
            ':id' => $id
        ]);
        header("Location: knowledge_articles.php?msg=success_update");
        exit;
    }

    // --- PROSES HAPUS DATA (DELETE) ---
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        
        $delFileSql = "SELECT lampiran FROM knowledge_articles WHERE id = :id";
        $delFileStmt = $conn->prepare($delFileSql);
        $delFileStmt->execute([':id' => $id]);
        $oldFile = $delFileStmt->fetchColumn();
        if (!empty($oldFile) && file_exists('uploads/' . $oldFile)) {
            @unlink('uploads/' . $oldFile);
        }

        $delSql = "DELETE FROM knowledge_articles WHERE id = :id";
        $delStmt = $conn->prepare($delSql);
        $delStmt->execute([':id' => $id]);
        header("Location: knowledge_articles.php?msg=success_delete");
        exit;
    }

    // =========================================================================
    // 2. AMBIL DATA KATEGORI UNTUK DROPDOWN MODAL
    // =========================================================================
    // Mengubah kc.judul menjadi kc.nama
    $catStmt = $conn->query("SELECT id, nama FROM knowledge_categories ORDER BY nama ASC");
    $all_categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================================
    // 3. AMBIL DATA ARTIKEL & PAGINASI (DENGAN LEFT JOIN)
    // =========================================================================
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $countSql = "SELECT COUNT(*) FROM knowledge_articles ka";
    if ($search !== '') {
        $countSql .= " WHERE ka.judul LIKE :search OR ka.isi LIKE :search";
    }
    
    $countStmt = $conn->prepare($countSql);
    if ($search !== '') {
        $countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // Mengubah kc.judul AS category_name menjadi kc.nama AS category_name
    $sql = "SELECT ka.*, kc.nama AS category_name 
            FROM knowledge_articles ka
            LEFT JOIN knowledge_categories kc ON ka.category_id = kc.id";
            
    if ($search !== '') {
        $sql .= " WHERE ka.judul LIKE :search OR ka.isi LIKE :search";
    }
    $sql .= " ORDER BY ka.id DESC LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);
    if ($search !== '') {
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Koneksi atau query bermasalah: " . $e->getMessage());
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
                overflow-x: hidden !hidden !important;
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
                display: block !important;
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
                display: block !important;
            }
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
            .main-content {
                width: 100% !important;
                margin-left: 0 !important;
                min-height: calc(100vh - 70px) !important;
                display: block !important;
                height: auto !important;
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

    <!-- FORCE CSS OVERRIDE UNTUK WARNA & GARIS PEMBATAS TABEL (ULTIMATE RESET) -->
    <style>
        /* 1. Pembuatan Garis Pembatas Vertikal & Horizontal */
        .custom-table, .custom-table th, .custom-table td {
            border: 1px solid #e3e6f0 !important;
            padding: 15px 20px !important;
            vertical-align: middle !important;
        }

        /* 2. Warna Judul Kolom Atas (Header) */
        .custom-table th, .custom-table th * {
            color: #4e73df !important;
            font-weight: 700 !important;
            background-color: #f8f9fc !important;
        }

        /* 3. Kolom 1 (ID): Dipaksa Hitam Gelap */
        .custom-table tr td:nth-child(1),
        .custom-table tr td:nth-child(1) * {
            color: #222222 !important;
            font-weight: 700 !important;
            text-decoration: none !important;
        }

        /* 4. Kolom 2 (Kategori): Dipaksa Biru Cerah Khas Badge */
        .custom-table tr td:nth-child(2),
        .custom-table tr td:nth-child(2) * {
            color: #1a73e8 !important; 
            font-weight: 700 !important;
            text-decoration: none !important;
        }

        /* 5. Kolom 3 (Judul Artikel): Dipaksa Biru Link Gelap/Navy */
        .custom-table tr td:nth-child(3),
        .custom-table tr td:nth-child(3) * {
            color: #2e59d9 !important; 
            font-weight: 600 !important;
            text-decoration: none !important;
        }

        /* 6. Kolom 4 (Isi Konten): Dipaksa Abu-Abu Netral (Bukan Biru) */
        .custom-table tr td:nth-child(4),
        .custom-table tr td:nth-child(4) * {
            color: #5a5c69 !important; 
            font-weight: 400 !important;
            text-decoration: none !important;
        }

        /* 7. Kolom 5 (Lampiran Berkas): Dipaksa Toska / Cyan */
        .custom-table tr td:nth-child(5),
        .custom-table tr td:nth-child(5) * {
            color: #029cbd !important;
            font-weight: 600 !important;
            text-decoration: none !important;
        }
        
        /* 8. Pewarnaan Mandiri Blok Tombol Kolom Aksi */
        .custom-table .btn-detail, .custom-table .btn-detail * { color: #36b9cc !important; text-decoration: none !important; }
        .custom-table .btn-edit, .custom-table .btn-edit * { color: #f6c23e !important; text-decoration: none !important; }
        .custom-table .btn-delete, .custom-table .btn-delete * { color: #e74a3b !important; background: none !important; border: none !important; padding: 0 !important; }
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
  
  <div class="flex-grow-1 menu-scroll-container w-100">
    <ul class="nav flex-column w-100">
      
      <li class="nav-item">
        <a href="dashboard.php" class="nav-link <?= checkActiveMenu('dashboard.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-house-door me-3"></i> Dashboard</a>
      </li>
      
      <li class="nav-item">
        <a href="roles.php" class="nav-link <?= checkActiveMenu('roles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-shield-lock me-3"></i> Manajemen Roles</a>
      </li>
      
      <li class="nav-item">
        <a href="relasi.php" class="nav-link <?= checkActiveMenu('relasi.php', $currentFile) ?> rounded-end d-flex align-items-center text-truncate" title="Manajemen Bangunan & Ruang">
          <i class="bi bi-diagram-3 me-3 flex-shrink-0"></i> <span class="text-truncate">Manajemen Bangunan & Ruang</span>
        </a>
      </li>
      
      <li class="nav-item">
        <a href="assets.php" class="nav-link <?= checkActiveMenu('assets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-folder2-open me-3"></i> Assets</a>
      </li>
      
      <li class="nav-item">
        <a href="manajemen_asset.php" class="nav-link <?= checkActiveMenu('manajemen_asset.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-boxes me-3"></i> Manajemen Asset</a>
      </li>
      
      <li class="nav-item">
        <a href="asset_movements.php" class="nav-link <?= checkActiveMenu('asset_movements.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-arrow-left-right me-3"></i> Log Perpindahan</a>
      </li>
      
      <li class="nav-item">
        <a href="server.php" class="nav-link <?= checkActiveMenu('server.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-hdd-network me-3"></i> Server</a>
      </li>
      
      <li class="nav-item">
        <a href="network_device.php" class="nav-link <?= checkActiveMenu('network_device.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-router me-3"></i> Network Device</a>
      </li>
      
      <li class="nav-item">
        <a href="network_port.php" class="nav-link <?= checkActiveMenu('network_port.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ethernet me-3"></i> Network Port</a>
      </li>
      
      <li class="nav-item">
        <a href="vendors.php" class="nav-link <?= checkActiveMenu('vendors.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-building me-3"></i> Vendors</a>
      </li>
      
      <li class="nav-item">
        <a href="password_categories.php" class="nav-link <?= checkActiveMenu('password_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-grid-fill me-3"></i> Password Categories</a>
      </li>
      
      <li class="nav-item">
        <a href="password_vault.php" class="nav-link <?= checkActiveMenu('password_vault.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-safe me-3"></i> Password Vault</a>
      </li>
      
      <li class="nav-item">
        <a href="tickets.php" class="nav-link <?= checkActiveMenu('tickets.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-ticket-perforated-fill me-3"></i> Tikets</a>
      </li>
      
      <li class="nav-item">
        <a href="maintenance.php" class="nav-link <?= checkActiveMenu('maintenance.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-wrench-adjustable-circle me-3"></i> Maintenance</a>
      </li>
      
      <li class="nav-item"> 
        <a href="knowledge_categories.php" class="nav-link <?= checkActiveMenu('knowledge_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags-fill me-3"></i> Knowledge Categories</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="knowledge_articles.php" class="nav-link <?= checkActiveMenu('knowledge_articles.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-file-earmark-text-fill me-3"></i> Knowledge Articles</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="sops.php" class="nav-link <?= checkActiveMenu('sops.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-journal-text me-3"></i> SOPS</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="sop_categories.php" class="nav-link <?= checkActiveMenu('sop_categories.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-tags me-3"></i> SOP Categories</a> 
      </li> 
      
      <li class="nav-item"> 
        <a href="software_licenses.php" class="nav-link <?= checkActiveMenu('software_licenses.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-key-fill me-3"></i> Software Licenses</a> 
      </li> 
      
      <li class="nav-item">
        <a href="user.php" class="nav-link <?= checkActiveMenu('user.php', $currentFile) ?> rounded-end d-flex align-items-center"><i class="bi bi-person-fill me-3"></i> User Profil</a>
      </li>

    </ul>
  </div> <!-- /penutup area menu tengah -->
  
  <!-- 4. Tombol Logout (Mengunci di Dasar Laci Menu) -->
  <div class="mt-auto pt-3 border-top border-secondary bg-dark w-100 px-3">
    <ul class="nav flex-column w-100">
      <li class="nav-item w-100">
        <a href="logout.php" 
           class="nav-link d-inline-flex align-items-center py-2 px-3 rounded w-100 logout-btn-merah" 
           style="color: #dc3545 !important; font-weight: 600 !important; transition: all 0.2s ease-in-out; box-sizing: border-box;">
          <i class="bi bi-box-arrow-right me-3 fs-5" style="color: #dc3545 !important;"></i> 
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </div>

</div> <!-- /penutup elemen offcanvas-md -->

<!-- PEMUTUS LINK GLOBAL (Mencegah kebocoran tag <a> dari file header eksternal) -->
</a>

<!-- AREA UTAMA KONTEN -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">
    <!-- Header Konten -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0; color: #333; font-size: 28px; font-weight: 600;">Knowledge Articles</h2>
        <!-- FIX: Mengarah langsung ke berkas halaman mandiri tambah_artikel.php -->
        <a href="tambah_artikel.php" class="btn btn-primary" style="color: #ffffff !important;">+ Tambah Artikel Baru</a> 
    </div>

    <!-- Kotak Filter & Pencarian -->
    <div style="background-color: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e3e6f0; margin-bottom: 20px;">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" placeholder="Cari berdasarkan judul atau isi artikel..." value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                   style="flex: 1; padding: 10px 15px; border: 1px solid #d1d3e2; border-radius: 6px; font-size: 14px; outline: none; color: #333333 !important;">
            <button type="submit" style="background-color: #4e73df; color: white !important; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                Cari
            </button>
            <?php if (!empty($search)): ?>
                <a href="?" style="color: #ea4335 !important; text-decoration: none; font-size: 14px; padding: 0 5px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABEL RESPONSIF DENGAN KELAS KUSTOM -->
    <div style="background-color: #fff; border-radius: 8px; border: 1px solid #e3e6f0; overflow-x: auto; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05); margin-bottom: 20px;"> 
        <table class="custom-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; min-width: 800px;">
            <thead>
                <tr style="background-color: #f8f9fc; border-bottom: 2px solid #e3e6f0;">
                    <th style="width: 60px; text-align: center;">ID</th>
                    <th style="width: 150px; white-space: nowrap;">Kategori</th>
                    <th style="width: 200px;">Judul</th>
                    <th style="width: 250px;">Isi Konten</th>
                    <th style="width: 130px;">Lampiran</th>
                    <th style="width: 100px; text-align: center;">Status</th>
                    <th style="width: 180px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($articles) && count($articles) > 0): ?>
                    <?php foreach ($articles as $row): ?>
                        <tr style="border-bottom: 1px solid #e3e6f0;">
                            
                            <!-- 1. ID -->
                            <td style="text-align: center;">
                                <?php echo $row['id']; ?>
                            </td>
                            
                            <!-- 2. Kategori -->
                            <td>
                                <?php if (!empty($row['category_name'])): ?>
                                    <span style="background-color: #e8f0fe; padding: 4px 10px; border-radius: 4px; font-size: 12px; display: inline-block; border: 1px solid #c2dbf7;">
                                        <?php echo htmlspecialchars($row['category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="font-style: italic; font-size: 13px;">Tanpa Kategori</span>
                                <?php endif; ?>
                            </td>

                            <!-- 3. Judul -->
                            <td>
                                <?php echo htmlspecialchars($row['judul'] ?? ''); ?>
                            </td>

                            <!-- 4. Isi Konten -->
                            <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars(strip_tags($row['isi'] ?? '')); ?>
                            </td>
                            
                            <!-- 5. Lampiran -->
                            <td>
                                <?php if (!empty($row['lampiran'])): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($row['lampiran']); ?>" target="_blank" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="color: initial;">📁</span> Lihat Berkas
                                    </a>
                                <?php else: ?>
                                    <span style="font-style: italic;">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- 6. Status -->
                            <td style="text-align: center;">
                                <?php if (($row['status'] ?? 1) == 1): ?>
                                    <span style="background-color: #1cc88a !important; color: white !important; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">Aktif</span>
                                <?php else: ?>
                                    <span style="background-color: #858796 !important; color: white !important; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">Draf</span>
                                <?php endif; ?>
                            </td>

                            <!-- 7. Aksi -->
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="detail_artikel.php?id=<?php echo $row['id']; ?>" class="btn-detail" style="text-decoration: none; margin-right: 12px; display: inline-block;">Detail</a>
                                <a href="edit_artikel.php?id=<?php echo $row['id']; ?>" class="btn-edit" style="text-decoration: none; margin-right: 12px; display: inline-block;">Edit</a>
                                <button type="button" class="btn-delete" style="cursor: pointer;" onclick="openDeleteModal(<?php echo $row['id']; ?>)">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 30px; text-align: center; font-style: italic;">
                            Tidak ada data artikel yang ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

    <!-- Paginasi di Bagian Bawah Tabel -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div style="background-color: #f8f9fc; padding: 15px 20px; border-top: 1px solid #e3e6f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #858796; font-size: 13px;">
                Menampilkan halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>
            </div>
            <div style="display: flex; gap: 5px;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>" 
                       style="padding: 6px 12px; border: 1px solid #d1d3e2; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s;
                              <?php echo ($page == $i) ? 'background-color: #4e73df; color: white; border-color: #4e73df;' : 'background-color: #fff; color: #4e73df;'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</main>

<!-- AREA MODAL HAPUS (ID SINKRON DENGAN JAVASCRIPT: modalDelete) -->
<div id="modalDelete" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center;">
    <div style="background-color: #fff; padding: 30px; border-radius: 8px; width: 400px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative; margin: auto;">
        
        <!-- Simbol Peringatan / Icon Bahaya -->
        <div style="background-color: #fff5f5; color: #e74a3b; width: 70px; height: 70px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 35px; margin: 0 auto 20px auto; border: 2px solid #ffe3e3;">
            ⚠️
        </div>
        
        <!-- Judul Modal -->
        <h3 style="margin: 0 0 10px 0; color: #333; font-size: 22px; font-weight: 600; font-family: sans-serif;">Hapus Artikel?</h3>
        
        <!-- Deskripsi Konten -->
        <p style="color: #858796; font-size: 14px; margin-bottom: 25px; line-height: 1.5; font-family: sans-serif;">
            Apakah Anda yakin ingin menghapus data artikel ini secara permanen? Tindakan ini tidak dapat dibatalkan.
        </p>
        
        <!-- Tombol Aksi Kontrol -->
        <div style="display: flex; gap: 15px; justify-content: center;">
            <!-- Tombol Batal Pemutus Modal -->
            <button type="button" onclick="closeDeleteModal()" style="background-color: #fff; color: #4e73df; border: 1px solid #d1d3e2; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: 600; min-width: 100px; font-size: 14px; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fc'" onmouseout="this.style.backgroundColor='#fff'">
                Batal
            </button>
            <!-- Tombol Konfirmasi Hapus Data -->
            <button type="button" onclick="executeDelete()" style="background-color: #e74a3b; color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: 600; min-width: 100px; font-size: 14px; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#be3827'" onmouseout="this.style.backgroundColor='#e74a3b'">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

<script>
    // ----------------------------------------------------
    // VARIABEL GLOBAL & KONTROL MODAL HAPUS
    // ----------------------------------------------------
    let activeDeleteId = null;

    function openDeleteModal(id) {
        activeDeleteId = id;
        document.getElementById('modalDelete').style.display = 'block';
    }
    
    function closeDeleteModal() {
        document.getElementById('modalDelete').style.display = 'none';
        activeDeleteId = null;
    }
    
    function executeDelete() {
        if (activeDeleteId) {
            window.location.href = "knowledge_articles.php?delete=" + activeDeleteId;
        }
    }

    // ----------------------------------------------------
    // KONTROL MODAL DETAIL ARTIKEL BARU
    // ----------------------------------------------------
    function openDetailModal(data) {
        // Isi data teks judul ke elemen modal
        document.getElementById('modalJudul').innerText = data.judul || '-';
        
        // Render Badge Kategori
        const kategoriEl = document.getElementById('modalKategori');
        if (data.category_name) {
            kategoriEl.innerHTML = `<span style="background-color: #eaecf4; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; color: #4e73df;">${data.category_name}</span>`;
        } else {
            kategoriEl.innerHTML = `<span style="color: #b7b9cc; font-style: italic;">Tanpa Kategori</span>`;
        }
        
        // Render Badge Status
        const statusEl = document.getElementById('modalStatus');
        if (data.status == 1) {
            statusEl.innerHTML = `<span style="background-color: #1cc88a; color: white; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;">Aktif</span>`;
        } else {
            statusEl.innerHTML = `<span style="background-color: #858796; color: white; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;">Draf</span>`;
        }
        
        // Isi Konten Utama Artikel
        document.getElementById('modalIsi').innerHTML = data.isi || '-';
        
        // Render Tautan Lampiran Berkas
        const lampiranEl = document.getElementById('modalLampiran');
        if (data.lampiran) {
            lampiranEl.innerHTML = `<a href="uploads/${data.lampiran}" target="_blank" style="color: #36b9cc; text-decoration: none; font-weight: 500;">📁 Lihat Berkas (${data.lampiran})</a>`;
        } else {
            lampiranEl.innerHTML = `<span style="color: #b7b9cc; font-style: italic;">Tidak ada lampiran</span>`;
        }
        
        // Tampilkan Modal Detail menggunakan flex centering
        document.getElementById('detailModal').style.display = 'flex';
    }

    function closeDetailModal() {
        document.getElementById('detailModal').style.display = 'none';
    }

    // ----------------------------------------------------
    // GLOBAL EVENT LISTENER (TUTUP SAAT KLIK LUAR AREA)
    // ----------------------------------------------------
    window.addEventListener('click', function(event) {
        // Deteksi penutupan Modal Hapus
        var modalDelete = document.getElementById('modalDelete');
        if (event.target == modalDelete) { 
            closeDeleteModal(); 
        }

        // Deteksi penutupan Modal Detail
        var modalDetail = document.getElementById('detailModal');
        if (event.target == modalDetail) {
            closeDetailModal();
        }
    });
</script>

<!-- INTERAKSI DOM DAN SINKRONISASI POSISI SCROLL SIDEBAR UTAMA (KUNCI MUTLAK) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuContainer = document.querySelector('.menu-scroll-container');
    const activeMenu = document.querySelector('.menu-scroll-container .active-style');
    
    // FIX MUTLAK: Selalu menetap mengunci fokus menu aktif saat halaman selesai dimuat (klik / reload)
    if (menuContainer && activeMenu) {
        const activeOffsetTop = activeMenu.offsetTop;
        menuContainer.scrollTop = activeOffsetTop - 20;
    }
});

// MODUL PENUTUP OTOMATIS ALERT & PEMBERSIH PARAMETER URL
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); 
            window.location.href = window.location.pathname; 
        }
    }
});
</script>

<?php include 'footer-admin.php'; ?>
