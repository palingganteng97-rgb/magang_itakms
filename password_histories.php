<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Koneksi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ========================================================
    // LOGIKA MENGAMBIL DATA RIWAYAT + NAMA VAULT (READ-ONLY)
    // ========================================================
    $sqlSelect = "SELECT ph.id, ph.vault_id, ph.password_lama, ph.diubah_pada, pv.nama AS nama_akun
                  FROM password_histories ph
                  LEFT JOIN password_vaults pv ON ph.vault_id = pv.id
                  ORDER BY ph.id DESC";
    $stmtSelect = $conn->query($sqlSelect);
    $histories = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Kesalahan Database: " . $e->getMessage());
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
</head>
<body class="bg-light">

<!-- TOPBAR SEDERHANA SEBAGAI NAVIGASI BALIK -->
<nav class="navbar navbar-dark bg-dark px-3 shadow mb-4">
    <div class="container-fluid">
        <span class="navbar-brand text-warning fw-bold mb-0 h1"><i class="bi bi-speedometer2"></i> ITAKMS</span>
        <a href="password_vault.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left-short"></i> Kembali ke Vault
        </a>
    </div>
</nav>

<!-- CONTAINER UTAMA MELEBAR PENUH (FULL WIDTH) -->
<div class="container-fluid px-3 px-md-5 pb-5">
    
    <!-- Header Konten Utama -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 border-bottom border-light-subtle pb-3">
      <div>
        <h3 class="fw-bold mb-1 text-dark fs-4 fs-md-3">
          <i class="bi bi-clock-history text-primary me-2"></i> Password Histories
        </h3>
        <small class="text-secondary d-block">Log rekaman seluruh perubahan kata sandi lama pada sistem</small>
      </div>
      <!-- Badge Informasi Total Log -->
      <span class="badge bg-primary px-3 py-2 fs-6">
        Total Log: <?= count($histories); ?> Record
      </span>
    </div>

    <!-- Card Box untuk Tabel Data -->
    <div class="card bg-white text-dark border-light-subtle shadow-sm mb-4">
      <div class="card-header bg-light border-light-subtle d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
        <h5 class="card-title mb-0 fw-semibold text-primary fs-6 fs-md-5">
          <i class="bi bi-journal-text me-2"></i> Log Aktivitas Sandi
        </h5>
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Mode Lihat (Read-Only)</span>
      </div>
      
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0 table-sm table-md-normal text-nowrap" style="border-color: #dee2e6;">
            <thead class="table-light text-dark fw-bold">
              <tr>
                <th scope="col" class="text-center" style="width: 70px;">No</th>
                <th scope="col">Nama Akun / Layanan</th>
                <th scope="col">Password Lama</th>
                <th scope="col">Tanggal Perubahan</th>
                <th scope="col" class="text-center" style="width: 120px;">Aksi</th>
              </tr>
            </thead>
            <tbody class="text-dark">
              <?php if (empty($histories)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-secondary text-wrap">
                    <i class="bi bi-folder-x fs-1 d-block mb-2 text-muted"></i>
                    Belum ada catatan riwayat perubahan password di database.
                  </td>
                </tr>
              <?php else: ?>
                <?php $no = 1; foreach ($histories as $row): ?>
                  <tr>
                    <td class="text-center fw-semibold text-secondary"><?= $no++; ?></td>
                    <td class="fw-semibold text-dark text-wrap">
                      <?= htmlspecialchars($row['nama_akun'] ?? 'Akun Telah Dihapus (#' . $row['vault_id'] . ')'); ?>
                    </td>
                    <td>
                      <!-- Form group mini dengan icon mata untuk intip password lama -->
                      <div class="input-group input-group-sm" style="width: 160px;">
                        <input type="password" class="form-control bg-light border-0" value="<?= htmlspecialchars($row['password_lama']); ?>" readonly id="passHistInput<?= $row['id']; ?>">
                        <button class="btn btn-outline-secondary border-0" type="button" onclick="togglePassword(<?= $row['id']; ?>)">
                          <i class="bi bi-eye" id="eyeIcon<?= $row['id']; ?>"></i>
                        </button>
                      </div>
                    </td>
                    <td class="text-secondary fw-medium">
                      <?= htmlspecialchars($row['diubah_pada']); ?>
                    </td>
                    <td class="text-center">
                      <!-- Tombol Salin Cepat -->
                      <button class="btn btn-sm btn-outline-primary fw-medium" onclick="copyToClipboard('passHistInput<?= $row['id']; ?>')">
                        <i class="bi bi-clipboard"></i> Salin
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

</div>

<!-- ========================================== -->
<!-- SCRIPT JAVASCRIPT PEMBANTU UTAMA           -->
<!-- ========================================== -->
<script>
// 1. Fungsi Klik Lihat / Sembunyikan Password Lama
function togglePassword(id) {
    var input = document.getElementById('passHistInput' + id);
    var icon = document.getElementById('eyeIcon' + id);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// 2. Fungsi Salin Cepat ke Clipboard
function copyToClipboard(inputId) {
    const input = document.getElementById(inputId);
    navigator.clipboard.writeText(input.value).then(() => {
        alert("Password lama berhasil disalin ke clipboard!");
    }).catch(err => {
        alert("Gagal menyalin teks: " + err);
    });
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
