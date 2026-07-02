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

    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #343a40; }

        /* KODE FIX: Menyembunyikan batang scrollbar untuk Chrome, Safari, dan Opera */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        /* Menyembunyikan batang scrollbar untuk Firefox dan IE/Edge */
        .hide-scrollbar {
            -ms-overflow-style: none;  /* IE dan Edge */
            scrollbar-width: none;  /* Firefox */
        }

        /* =========================================================================
             DESAIN STYLE CSS MODAL (JARAK & TINGGI DIOPTIMALKAN)
             ========================================================================= */
        /* Latar Belakang Gelap Transparan */
        .modal-custom {
            display: none; 
            position: fixed; 
            z-index: 9999; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            overflow: hidden; /* Mengunci scrollbar halaman belakang */
            background-color: rgba(0, 0, 0, 0.5); 
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        /* Kotak Putih Tengah */
        .modal-content-custom {
            background-color: #ffffff;
            margin: 2.5% auto; /* Jarak atas diperkecil agar posisi modal naik ke atas */
            padding: 20px 25px; /* Padding vertikal sedikit diringkas */
            border-radius: 8px;
            width: 90%;
            max-width: 850px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid #cbd5e1;
        }
        /* Bagian Atas Modal */
        .modal-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .close-btn-custom {
            color: #94a3b8;
            font-size: 26px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .close-btn-custom:hover {
            color: #475569;
        }
        /* Tata Letak Kolom Input */
        .form-group-custom {
            margin-bottom: 0px; /* Diatur nol karena jarak diatur lewat gap flexbox parent */
        }
        .form-group-custom label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
        }
        .form-control-custom {
            width: 100%;
            padding: 8px 12px; /* Diringkas sedikit dari 10px ke 8px */
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
            outline: none;
            color: #334155;
            height: 38px;
        }
        .form-control-custom:focus {
            border-color: #2563eb;
        }
        /* Bagian Bawah Modal */
        .modal-footer-custom {
            text-align: right;
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .btn-secondary-custom, .btn-success-custom, .btn-primary-custom {
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-secondary-custom {
            background-color: #64748b;
            color: white;
            margin-right: 8px;
        }
        .btn-success-custom {
            background-color: #16a34a;
            color: white;
        }
        .btn-primary-custom {
            background-color: #2563eb;
            color: white;
        }
        .btn-secondary-custom:hover { background-color: #475569; }
        .btn-success-custom:hover { background-color: #15803d; }
        .btn-primary-custom:hover { background-color: #1d4ed8; }
        <!-- Tambahan Style Warna Tombol Hapus Merah -->

        .btn-danger-custom {
            background-color: #dc2626;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-danger-custom:hover { background-color: #b91c1c; }

    </style>
</head>
<body>

<!-- TOPBAR MOBILE -->
<nav class="navbar navbar-dark bg-dark d-md-none px-3 shadow">
    <div class="d-flex align-items-center justify-content-between w-100">
        <span class="navbar-brand text-warning fw-bold"><i class="bi bi-speedometer2"></i> ITAKMS</span>
        <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
            <i class="bi bi-list"></i>
        </button>
    </div>
</nav>

<!-- ========================================== -->
<!-- 1. SIDEBAR MOBILE (OFFCANVAS)              -->
<!-- ========================================== -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <!-- Header Mobile (Tetap Diam di Atas) -->
  <div class="offcanvas-header border-bottom border-secondary">
    <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2 text-warning me-2"></i> ITAKMS</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <!-- Body Offcanvas (Pembagian Layout Flex Vertikal) -->
  <div class="offcanvas-body p-0 d-flex flex-column" style="height: calc(100vh - 56px);">
    <!-- Area Menu Tengah Mobile (Ditambahkan class hide-scrollbar) -->
    <div class="flex-grow-1 overflow-y-auto p-3 hide-scrollbar">
      <ul class="nav flex-column gap-2">
        <!-- Dashboard Aktif di Mobile -->
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a href="roles.php" class="nav-link text-white p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
        </li>
        <li class="nav-item">
          <a href="relasi.php" class="nav-link text-white p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;">
            <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
          </a>
        </li>
        <li class="nav-item">
          <a href="assets.php" class="nav-link text-white p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
        </li>
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link text-white p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
        </li>
        <li class="nav-item">
          <a href="server.php" class="nav-link text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
        </li>
        <li class="nav-item">
          <a href="network_device.php" class="nav-link text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
        </li>
        <li class="nav-item">
          <a href="network_port.php" class="nav-link text-white p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
        </li>
        <!-- VENDORS (Mobile) -->
        <li class="nav-item">
          <a href="vendors.php" class="nav-link <?= ($currentPage == 'vendors.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-building me-2"></i> Vendors <!-- Tetap ikon gedung mitra bisnis -->
          </a>
        </li>
        <!-- Tambahkan di bawah menu Network Port atau di posisi yang Anda inginkan -->
        <li class="nav-item">
          <a href="password_categories.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-grid-fill me-2"></i> Password Categories
          </a>
        </li>
        <li class="nav-item">
          <a href="password_vault.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-safe me-2"></i> Password Vault
          </a>
        </li>
        <!-- MENU TIKETS -->
        <li class="nav-item">
          <a href="tickets.php" class="nav-link <?= ($currentPage == 'tickets.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-ticket-perforated-fill me-2"></i> Tikets
          </a>
        </li>
        <!-- MAINTENANCE -->
        <li class="nav-item">
        <a href="maintenance.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-wrench-adjustable-circle me-2"></i> Maintenance
        </a>
        </li>
        <!-- KNOWLEDGE CATEGORIES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_categories.php" class="nav-link <?= ($currentPage == 'knowledge_categories.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-tags-fill me-2"></i> <span>Knowledge Categories</span>
            </a> 
        </li> 

        <!-- KNOWLEDGE ARTICLES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_articles.php" class="nav-link active bg-primary <?= ($currentPage == 'knowledge_articles.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-file-earmark-text-fill me-2"></i> <span>Knowledge Articles</span>
            </a> 
        </li> 
        <!-- USER PROFIL (Mobile) -->
        <li class="nav-item">
          <a href="user.php" class="nav-link <?= ($currentPage == 'user.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-person-fill me-2"></i> User Profil <!-- PERBAIKAN: Menggunakan ikon orang murni sesuai keinginan Anda -->
          </a>
        </li>
      </ul>
    </div>
    
    <!-- Tombol Logout Mobile (Mengunci di Posisi Dasar Bawah) -->
    <div class="mt-auto p-3 border-top border-secondary bg-dark w-100">
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
            <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 2. SIDEBAR DESKTOP                         -->
<!-- ========================================== -->
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar Desktop dengan Tinggi Layar Terkunci Permanen 100vh & Posisi Statis Fixed -->
    <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3 text-bg-dark" style="min-height: 100vh; max-height: 100vh; position: fixed; z-index: 1000;">
      <!-- Judul Utama Dashboard Desktop (Tetap Diam) -->
      <h4 class="text-center mb-4 text-warning fw-bold pt-2"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
      
      <!-- Area Menu Tengah Desktop (Ditambahkan class hide-scrollbar dan menghapus padding kanan pr-1) -->
      <div class="flex-grow-1 overflow-y-auto hide-scrollbar" style="max-height: calc(100vh - 160px);">
        <ul class="nav flex-column gap-2">
          <!-- Dashboard Aktif di Desktop -->
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a href="roles.php" class="nav-link text-white p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
          </li>
          <li class="nav-item">
            <a href="relasi.php" class="nav-link text-white p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;" title="Manajemen Bangunan & Ruang">
              <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
            </a>
          </li>
          <li class="nav-item">
            <a href="assets.php" class="nav-link text-white p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
          </li>
          <li class="nav-item">
            <a href="manajemen_asset.php" class="nav-link text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
          </li>
          <li class="nav-item">
            <a href="asset_movements.php" class="nav-link text-white p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
          </li>
          <li class="nav-item">
            <a href="server.php" class="nav-link text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
          </li>
          <li class="nav-item">
            <a href="network_device.php" class="nav-link text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
          </li>
          <li class="nav-item">
            <a href="network_port.php" class="nav-link text-white p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
          </li>
          <!-- VENDORS (Desktop) -->
          <li class="nav-item">
            <a href="vendors.php" class="nav-link <?= ($currentPage == 'vendors.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
              <i class="bi bi-building me-2"></i> Vendors
            </a>
          </li>
          <!-- Tambahkan di bawah menu Network Port atau di posisi yang Anda inginkan -->
        <li class="nav-item">
          <a href="password_categories.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-grid-fill me-2"></i> Password Categories
          </a>
        </li>
        <li class="nav-item">
          <a href="password_vault.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-safe me-2"></i> Password Vault
          </a>
        </li>
        <!-- MENU TIKETS -->
        <li class="nav-item">
          <a href="tickets.php" class="nav-link <?= ($currentPage == 'tickets.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-ticket-perforated-fill me-2"></i> Tikets
          </a>
        </li>
        <!-- MAINTENANCE -->
        <li class="nav-item">
          <a href="maintenance.php" class="nav-link <?= ($currentPage == 'maintenance.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-wrench-adjustable-circle me-2"></i> Maintenance
          </a>
        </li>
        <!-- KNOWLEDGE CATEGORIES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_categories.php" class="nav-link <?= ($currentPage == 'knowledge_categories.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-tags-fill me-2"></i> <span>Knowledge Categories</span>
            </a> 
        </li> 
        <!-- KNOWLEDGE ARTICLES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_articles.php" class="nav-link active bg-primary <?= ($currentPage == 'knowledge_articles.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-file-earmark-text-fill me-2"></i> <span>Knowledge Articles</span>
            </a> 
        </li> 
          <!-- USER PROFIL (Desktop) -->
          <li class="nav-item">
            <a href="user.php" class="nav-link <?= ($currentPage == 'user.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
              <i class="bi bi-person-fill me-2"></i> User Profil
            </a>
          </li>
        </ul>
      </div>
      
      <!-- Tombol Logout Desktop (Mengunci Mengikuti Batas Layar Bawah) -->
      <div class="mt-auto pt-3 border-top border-secondary w-100 bg-dark">
        <ul class="nav flex-column gap-2">
          <li class="nav-item">
            <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
              <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </nav>

<!-- AREA UTAMA KONTEN -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">
    <!-- Header Konten -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0; color: #333; font-size: 28px; font-weight: 600;">Knowledge Articles</h2>
        <!-- FIX: Mengarah langsung ke berkas halaman mandiri tambah_artikel.php -->
        <a href="tambah_artikel.php" class="btn btn-primary">+ Tambah Artikel Baru</a> 
    </div>

    <!-- Kotak Filter & Pencarian -->
    <div style="background-color: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e3e6f0; margin-bottom: 20px;">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" placeholder="Cari berdasarkan judul atau isi artikel..." value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                   style="flex: 1; padding: 10px 15px; border: 1px solid #d1d3e2; border-radius: 6px; font-size: 14px; outline: none;">
            <button type="submit" style="background-color: #4e73df; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                Cari
            </button>
            <?php if (!empty($search)): ?>
                <a href="?" style="color: #ea4335; text-decoration: none; font-size: 14px; padding: 0 5px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

<!-- UBAH DI BARIS PERTAMA: Tambahkan overflow-x: auto; -->
<div style="background-color: #fff; border-radius: 8px; border: 1px solid #e3e6f0; overflow: hidden; overflow-x: auto; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);"> 
    <!-- Elemen <table> tetap sama seperti sebelumnya -->
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
        <thead>
            <tr style="background-color: #f8f9fc; border-bottom: 2px solid #e3e6f0;">
                <th style="padding: 15px 20px; color: #4e73df; font-weight: 700; width: 60px;">ID</th>
                <!-- Lebar Kategori disesuaikan agar teks tidak turun -->
                <th style="padding: 15px 20px; color: #4e73df; font-weight: 700; width: 180px; white-space: nowrap;">Kategori</th>
                <!-- Lebar Judul disesuaikan agar lebih luas -->
                <th style="padding: 15px 20px; color: #4e73df; font-weight: 700; width: 300px;">Judul</th>
                <th style="padding: 15px 20px; color: #4e73df; font-weight: 700;">Isi Konten</th>
                <th style="padding: 15px 20px; color: #4e73df; font-weight: 700; width: 150px;">Lampiran</th>
                <th style="padding: 15px 20px; color: #4e73df; font-weight: 700; width: 100px;">Status</th>
                <th style="padding: 15px 20px; color: #4e73df; font-weight: 700; width: 200px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($articles) && count($articles) > 0): ?>
                <?php foreach ($articles as $row): ?>
                    <tr style="border-bottom: 1px solid #e3e6f0; transition: background 0.15s;" onmouseover="this.style.backgroundColor='#f8f9fc'" onmouseout="this.style.backgroundColor='transparent'">
                        <td style="padding: 15px 20px; color: #6e707e;"><?php echo $row['id']; ?></td>
                        
                        <!-- PERBAIKAN 1: Tambah white-space: nowrap agar kategori memanjang ke kanan -->
                        <td style="padding: 15px 20px; color: #6e707e; white-space: nowrap;">
                            <?php if (!empty($row['category_name'])): ?>
                                <span style="background-color: #eaecf4; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; color: #4e73df; display: inline-block;">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #b7b9cc; font-style: italic;">Tanpa Kategori</span>
                            <?php endif; ?>
                        </td>

                        <!-- PERBAIKAN 2: Tambah white-space: nowrap agar judul memanjang ke kanan -->
                        <td style="padding: 15px 20px; font-weight: 600; color: #2e59d9; white-space: nowrap;">
                            <?php echo htmlspecialchars($row['judul'] ?? ''); ?>
                        </td>
                        <td style="padding: 15px 20px; color: #6e707e; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars(strip_tags($row['isi'] ?? '')); ?>
                        </td>
                        
                        <!-- PERBAIKAN LAMPIRAN: Dikunci barisnya dan disejajarkan secara vertikal -->
                        <td style="padding: 15px 20px; white-space: nowrap;">
                            <?php if (!empty($row['lampiran'])): ?>
                                <a href="uploads/<?php echo htmlspecialchars($row['lampiran']); ?>" target="_blank" style="color: #36b9cc; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; vertical-align: middle;">
                                    <span style="font-size: 16px; line-height: 1;">📁</span> Lihat Berkas
                                </a>
                            <?php else: ?>
                                <span style="color: #b7b9cc; font-style: italic;">Tidak ada</span>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 15px 20px;">
                            <?php if (($row['status'] ?? 1) == 1): ?>
                                <span style="background-color: #1cc88a; color: white; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">Aktif</span>
                            <?php else: ?>
                                <span style="background-color: #858796; color: white; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">Draf</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px 20px; text-align: center; white-space: nowrap;">
                            <!-- PERBAIKAN: Mengubah button modal menjadi tautan link ke halaman detail -->
                            <a href="detail_artikel.php?id=<?php echo $row['id']; ?>" style="color: #36b9cc; text-decoration: none; font-weight: 600; margin-right: 12px; display: inline-block;">
                                Detail
                            </a>
                            
                            <a href="edit_artikel.php?id=<?php echo $row['id']; ?>" style="color: #f6c23e; text-decoration: none; font-weight: 600; margin-right: 12px; display: inline-block;">
                                Edit
                            </a>
                            
                            <button type="button" class="btn" style="color: #e74a3b; background: none; border: none; font-weight: 600; padding: 0; cursor: pointer; vertical-align: baseline;" 
                                    onclick="openDeleteModal(<?php echo $row['id']; ?>)">
                                Hapus
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="padding: 30px; text-align: center; color: #858796; font-style: italic;">
                        Tidak ada data artikel yang ditemukan.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

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

    <!-- =========================================================================
         STRUKTUR MODAL HAPUS DATA (MURNI TAMPILAN)
         ========================================================================= -->
    <div id="modalDelete" class="modal-custom">
        <!-- max-width dipangkas menjadi 400px agar terlihat seperti kotak dialog konfirmasi kecil -->
        <div class="modal-content-custom" style="max-width: 400px; margin: 12% auto; text-align: center; padding: 30px 25px;">
            
            <!-- Ikon Peringatan Besar -->
            <div style="color: #dc2626; font-size: 48px; margin-bottom: 15px; line-height: 1;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            
            <!-- Judul Konfirmasi -->
            <h3 style="margin: 0 0 10px 0; color: #1e293b; font-size: 20px; font-weight: 700;">Hapus Artikel?</h3>
            
            <!-- Teks Penjelasan -->
            <p style="margin: 0 0 25px 0; color: #64748b; font-size: 14px; line-height: 1.5;">
                Apakah Anda yakin ingin menghapus data artikel ini secara permanen? Tindakan ini tidak dapat dibatalkan.
            </p>
            
            <!-- Tombol Aksi Kanan-Kiri Berdampingan -->
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn-secondary-custom" onclick="closeDeleteModal()" style="flex: 1; margin: 0; padding: 10px 0;">
                    Batal
                </button>
            <!-- Cari tombol hapus merah di dalam kode modalDelete Anda, lalu ubah menjadi ini -->
            <button type="button" class="btn-danger-custom" onclick="executeDelete()" style="flex: 1; padding: 10px 0;">
                Ya, Hapus
            </button>
            </div>
        </div>
    </div>


<!-- =========================================================================
     KONTROL MODAL FIX (MODAL HAPUS & MODAL DETAIL)
     ========================================================================= -->
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
