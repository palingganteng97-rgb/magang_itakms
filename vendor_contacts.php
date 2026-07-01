<?php
require_once __DIR__ . '/auth.php';
require_login();

$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Ambil data dropdown vendor (Hanya kolom yang dibutuhkan agar hemat memori RAM)
    $all_vendors = $conn->query("SELECT id, nama FROM vendors ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Hitung total baris menggunakan index primary key (Sangat Cepat)
    $totalRows = $conn->query("SELECT COUNT(id) FROM vendor_contacts")->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // 3. OPTIMASI UTAMA: Ambil ID Kontak terlebih dahulu dengan teknik Subquery Indexing
    // Cara ini memangkas beban pencarian teks JOIN yang lambat di awal
    $sql = "SELECT vc.id, vc.vendor_id, vc.nama, vc.jabatan, vc.telepon, vc.email, v.nama AS nama_vendor 
            FROM (
                SELECT id FROM vendor_contacts ORDER BY id DESC LIMIT :limit OFFSET :offset
            ) AS fast_vc
            JOIN vendor_contacts vc ON fast_vc.id = vc.id
            LEFT JOIN vendors v ON vc.vendor_id = v.id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Koneksi atau Query Database Gagal: " . $e->getMessage());
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
<body>

<div class="container-fluid">
  <div class="row">

    <!-- AREA UTAMA KONTEN -->
    <!-- PERUBAHAN PERMANEN: Mengubah menjadi col-12 dan menghapus ms-sm-auto agar konten mentok ke kiri -->
    <main class="col-12 px-md-4 pt-4">

      <!-- Header Halaman -->
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
          <!-- Tombol kembali ke vendors.php -->
          <div class="mb-2">
            <a href="vendors.php" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1 d-inline-flex align-items-center gap-2 small">
              <i class="bi bi-arrow-left"></i> Kembali ke Vendor
            </a>
          </div>
          <h1 class="h3 fw-bold text-dark mb-1">Data Kontak Person Vendor</h1>
          <p class="text-muted small mb-0 d-none d-sm-block">Kelola sub-kontak spesifik, jabatan, dan nomor staf penghubung dari masing-masing perusahaan vendor.</p>
        </div>
        <button class="btn d-md-none text-dark p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
          <i class="bi bi-list fs-2"></i>
        </button>
      </div>

      <!-- Notifikasi Flash Status CRUD -->
      <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <?php
              if($_GET['status'] == 'success_add') echo '<i class="bi bi-check-circle-fill me-2"></i> Kontak vendor baru berhasil ditambahkan!';
              if($_GET['status'] == 'success_update') echo '<i class="bi bi-check-circle-fill me-2"></i> Data kontak berhasil diperbarui!';
              if($_GET['status'] == 'success_delete') echo '<i class="bi bi-trash-fill me-2"></i> Kontak berhasil dihapus!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Card Wadah Tabel -->
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 bg-white p-3">
        
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
          <h5 class="mb-0 text-dark fw-bold d-flex align-items-center"><i class="bi bi-person-lines-fill me-2 text-success"></i> Daftar Personel Kontak</h5>
          <button type="button" class="btn btn-success btn-sm rounded-3 px-3 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddContact">
              <i class="bi bi-plus-lg"></i> Tambah Kontak
          </button>
        </div>

        <!-- Tabel Data Contacts -->
        <div class="table-responsive w-100 rounded-3 border" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
          <table class="table table-striped table-hover align-middle mb-0 text-nowrap w-100">
            <thead class="table-light border-bottom">
              <tr>
                <th class="ps-3" style="width: 70px;">No</th>
                <th>Perusahaan Vendor</th>
                <th>Nama Lengkap</th>
                <th>Jabatan</th>
                <th>No. Telepon</th>
                <th>Alamat Email</th>
                <th class="text-center pe-3" style="width: 120px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($contacts)): ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted py-5" style="white-space: normal;">
                      <i class="bi bi-person-x display-4 d-block mb-3 text-secondary opacity-50"></i>
                      <span class="d-block fw-semibold text-dark mb-1">Belum Ada Kontak Terdaftar</span>
                    </td>
                  </tr>
              <?php else: $no = 1; foreach ($contacts as $c): ?>
                  <tr>
                    <td class="ps-3 fw-bold text-muted"><?= $no++; ?></td>
                    <td>
                      <span class="badge bg-light text-primary border border-primary-subtle px-2.5 py-1.5 rounded-3 fw-bold">
                        <i class="bi bi-building me-1"></i> <?= htmlspecialchars($c['nama_vendor'] ?? 'Tidak Diketahui'); ?>
                      </span>
                    </td>
                    <td class="fw-bold text-dark"><?= htmlspecialchars($c['nama'] ?? '-'); ?></td>
                    <td><span class="text-muted small fw-semibold"><?= htmlspecialchars($c['jabatan'] ?? '-'); ?></span></td>
                    <td><code class="text-dark bg-light px-2 py-1 rounded border small"><?= htmlspecialchars($c['telepon'] ?? '-'); ?></code></td>
                    <td><?= htmlspecialchars($c['email'] ?? '-'); ?></td>
                    <td class="text-center pe-3">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-warning border-0" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalEditContact"
                                data-id="<?= $c['id']; ?>"
                                data-vendor_id="<?= $c['vendor_id']; ?>"
                                data-nama="<?= htmlspecialchars($c['nama'] ?? ''); ?>"
                                data-jabatan="<?= htmlspecialchars($c['jabatan'] ?? ''); ?>"
                                data-telepon="<?= htmlspecialchars($c['telepon'] ?? ''); ?>"
                                data-email="<?= htmlspecialchars($c['email'] ?? ''); ?>">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <a href="proses_vendor_contact.php?action=delete&id=<?= $c['id']; ?>" 
                           class="btn btn-outline-danger border-0" 
                           onclick="return confirm('Hapus kontak person ini?')">
                            <i class="bi bi-trash3"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

  </div> <!-- /.row -->
</div> <!-- /.container-fluid -->

<!-- MODAL TAMBAH CONTACT -->
<div class="modal fade" id="modalAddContact" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow overflow-hidden">
      <div class="modal-header bg-success text-white py-3 px-4 border-0">
        <h5 class="modal-title fw-bold fs-5"><i class="bi bi-person-plus me-2"></i> Tambah Kontak Person</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="proses_vendor_contact.php" method="POST">
        <input type="hidden" name="action" value="add_contact">
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Pilih Vendor Induk <span class="text-danger">*</span></label>
              <select name="vendor_id" class="form-select rounded-3 bg-light-subtle" required>
                <option value="" disabled selected>-- Pilih Perusahaan --</option>
                <?php foreach ($all_vendors as $v): ?>
                  <option value="<?= $v['id']; ?>"><?= htmlspecialchars($v['nama']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Nama Lengkap Kontak <span class="text-danger">*</span></label>
              <input type="text" name="nama" class="form-control rounded-3 bg-light-subtle" required>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-12 col-sm-4">
              <label class="form-label fw-bold small text-secondary mb-1">Jabatan / Divisi</label>
              <input type="text" name="jabatan" class="form-control rounded-3 bg-light-subtle" placeholder="Ex: Account Manager">
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label fw-bold small text-secondary mb-1">No. Telepon</label>
              <input type="text" name="telepon" class="form-control rounded-3 bg-light-subtle">
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label fw-bold small text-secondary mb-1">Alamat Email</label>
              <input type="email" name="email" class="form-control rounded-3 bg-light-subtle">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light px-4 py-3 border-0">
          <button type="button" class="btn btn-light border rounded-3 px-3 btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success rounded-3 px-4 btn-sm fw-bold">Simpan Kontak</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- REVISI: MODAL EDIT CONTACT (TIDAK MEMANJANG)-->
<!-- ========================================== -->
<div class="modal fade" id="modalEditContact" tabindex="-1" aria-labelledby="modalEditContactLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"> <!-- Menggunakan modal-lg agar melebar menyamping -->
    <div class="modal-content rounded-4 border-0 shadow overflow-hidden">
      
      <!-- Header Modal -->
      <div class="modal-header bg-warning text-dark py-3 px-4 border-0">
        <h5 class="modal-title fw-bold fs-5" id="modalEditContactLabel">
          <i class="bi bi-pencil-square me-2"></i> Ubah Kontak Person
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form action="proses_vendor_contact.php" method="POST">
        <!-- Input Tersembunyi untuk Parameter Logika CRUD -->
        <input type="hidden" name="action" value="edit_contact">
        <input type="hidden" name="id" id="edit_id">
        
        <!-- Body Modal Ringkas (Layout Grid) -->
        <div class="modal-body p-4">
          
          <!-- BARIS 1: Pilihan Vendor Induk & Nama Kontak -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Pilih Vendor Induk <span class="text-danger">*</span></label>
              <select name="vendor_id" id="edit_vendor_id" class="form-select rounded-3 bg-light-subtle" required>
                <?php foreach ($all_vendors as $v): ?>
                  <option value="<?= $v['id']; ?>"><?= htmlspecialchars($v['nama']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-bold small text-secondary mb-1">Nama Lengkap Kontak <span class="text-danger">*</span></label>
              <input type="text" name="nama" id="edit_nama" class="form-control rounded-3 bg-light-subtle" required>
            </div>
          </div>
          
          <!-- BARIS 2: Jabatan, Telepon, & Email -->
          <div class="row g-3 mb-0">
            <div class="col-12 col-sm-4">
              <label class="form-label fw-bold small text-secondary mb-1">Jabatan / Divisi</label>
              <input type="text" name="jabatan" id="edit_jabatan" class="form-control rounded-3 bg-light-subtle" placeholder="Ex: Account Manager">
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label fw-bold small text-secondary mb-1">No. Telepon</label>
              <input type="text" name="telepon" id="edit_telepon" class="form-control rounded-3 bg-light-subtle">
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label fw-bold small text-secondary mb-1">Alamat Email</label>
              <input type="email" name="email" id="edit_email" class="form-control rounded-3 bg-light-subtle">
            </div>
          </div>
          
        </div>
        
        <!-- Footer Modal -->
        <div class="modal-footer bg-light px-4 py-3 border-0">
          <button type="button" class="btn btn-secondary rounded-3 px-4 btn-sm fw-semibold" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning text-dark rounded-3 px-4 btn-sm fw-bold shadow-sm">Perbarui Kontak</button>
        </div>
      </form>
      
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- SCRIPT AUTOMATION MAPPER FOR EDIT MODAL   -->
<!-- ========================================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Ambil elemen modal edit berdasarkan ID
    const modalEditContact = document.getElementById('modalEditContact');
    
    if (modalEditContact) {
        // 2. Dengarkan event ketika modal akan ditampilkan
        modalEditContact.addEventListener('show.bs.modal', function (event) {
            // Tombol (ikon pensil) yang memicu modal terbuka
            const button = event.relatedTarget;
            
            // 3. Ekstrak data dari atribut data-* yang ada di tombol tabel
            const id        = button.getAttribute('data-id');
            const vendorId  = button.getAttribute('data-vendor_id');
            const nama      = button.getAttribute('data-nama');
            const jabatan   = button.getAttribute('data-jabatan');
            const telepon   = button.getAttribute('data-telepon');
            const email     = button.getAttribute('data-email');
            
            // 4. Masukkan data hasil ekstrak ke dalam field input modal edit
            document.getElementById('edit_id').value        = id;
            document.getElementById('edit_vendor_id').value = vendorId;
            document.getElementById('edit_nama').value      = nama;
            document.getElementById('edit_jabatan').value   = jabatan;
            document.getElementById('edit_telepon').value   = telepon;
            document.getElementById('edit_email').value     = email;
        });
    }
});
</script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
