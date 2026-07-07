<?php
// 1. Inisialisasi Auth dan Koneksi Database
require_once __DIR__ . '/auth.php';
require_login();

// SISIPKAN FILE UTAMA RBAC DI SINI
require_once __DIR__ . '/helper_rbac.php';

$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Menangkap nilai action, baik dari parameter POST maupun URL (GET)
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// =========================================================================
// PERBAIKAN LOGIKA RBAC ASLI: Mengunci aksi manipulasi data tabel vendors
// =========================================================================
if (!empty($action)) {
    $table_name = 'vendors'; // Dikunci langsung ke nama tabel database fisik Anda

    if ($action === 'add_vendor') {
        protect_page_by_table($table_name, 'C'); // Hanya Super Admin (1) & Admin IT (2) yang lolos
    } elseif ($action === 'edit_vendor') {
        protect_page_by_table($table_name, 'U'); // Hanya Super Admin (1) & Admin IT (2) yang lolos
    } elseif ($action === 'delete') {
        protect_page_by_table($table_name, 'D'); // Hanya Super Admin (1) & Admin IT (2) yang lolos
    } else {
        header('HTTP/1.1 403 Forbidden');
        exit("Aksi tidak valid atau dilarang.");
    }
}

try {
    // SINKRONISASI: Mengubah charset menjadi utf8mb4 agar kompatibel penuh dengan pembacaan karakter khusus
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// 2. LOGIKA TAMBAH DATA (CREATE)
if (isset($_POST['action']) && $_POST['action'] == 'add_vendor') {
    $nama    = trim($_POST['nama']);
    $pic     = trim($_POST['pic']);
    $telepon = trim($_POST['telepon']);
    $email   = trim($_POST['email']);
    $website = trim($_POST['website']);
    $status  = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    if (empty($nama)) {
        header("Location: vendors.php?status=failed_empty");
        exit();
    }

    try {
        $sql = "INSERT INTO vendors (nama, pic, telepon, email, website, status) 
                VALUES (:nama, :pic, :telepon, :email, :website, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nama'    => $nama,
            ':pic'     => $pic,
            ':telepon' => $telepon,
            ':email'   => $email,
            ':website' => $website,
            ':status'  => $status
        ]);

        // =========================================================================
        // PENGISIAN DATA ID LOGS INSTAN (TAMBAH DATA VENDOR)
        // =========================================================================
        $last_id = $conn->lastInsertId();
        write_log($conn, "Menambahkan data vendor baru: '" . $nama . "'", "vendors", $last_id);

        header("Location: vendors.php?status=success_add");
        exit();
    } catch (PDOException $e) {
        die("Error simpan data: " . $e->getMessage());
    }
}

// 3. LOGIKA UBAH DATA (UPDATE)
if (isset($_POST['action']) && $_POST['action'] == 'edit_vendor') {
    $id      = (int)$_POST['id'];
    $nama    = trim($_POST['nama']);
    $pic     = trim($_POST['pic']);
    $telepon = trim($_POST['telepon']);
    $email   = trim($_POST['email']);
    $website = trim($_POST['website']);
    $status  = (int)$_POST['status'];

    if (empty($id) || empty($nama)) {
        header("Location: vendors.php?status=failed_empty");
        exit();
    }

    try {
        $sql = "UPDATE vendors SET 
                    nama = :nama, 
                    pic = :pic, 
                    telepon = :telepon, 
                    email = :email, 
                    website = :website, 
                    status = :status 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nama'    => $nama,
            ':pic'     => $pic,
            ':telepon' => $telepon,
            ':email'   => $email,
            ':website' => $website,
            ':status'  => $status,
            ':id'      => $id
        ]);

        // =========================================================================
        // PENGISIAN DATA ID LOGS INSTAN (UBAH DATA VENDOR)
        // =========================================================================
        write_log($conn, "Memperbarui profil informasi data vendor: '" . $nama . "'", "vendors", $id);

        header("Location: vendors.php?status=success_update");
        exit();
    } catch (PDOException $e) {
        die("Error ubah data: " . $e->getMessage());
    }
}

// 4. LOGIKA HAPUS DATA (DELETE DENGAN PEMBERSIHAN MULTI-TABEL)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        // A. Mulai Database Transaction untuk mengunci integritas proses hapus berantai
        $conn->beginTransaction();

        // B. Ambil nama vendor sebelum datanya dimusnahkan untuk deskripsi log audit
        $get_vendor = $conn->prepare("SELECT nama FROM vendors WHERE id = :id");
        $get_vendor->execute([':id' => $id]);
        $vendor = $get_vendor->fetch(PDO::FETCH_ASSOC);

        if ($vendor) {
            $nama_vendor = $vendor['nama'] ?? 'Tidak Diketahui';

            // C. FIX ERROR 1451: Hapus data kontak relasi di tabel anak 'vendor_contacts' terlebih dahulu
            $stmt_contacts = $conn->prepare("DELETE FROM vendor_contacts WHERE vendor_id = :id");
            $stmt_contacts->execute([':id' => $id]);

            // D. Setelah tabel anak bersih, barulah aman menghapus baris data di tabel induk 'vendors'
            $stmt_vendor = $conn->prepare("DELETE FROM vendors WHERE id = :id");
            $stmt_vendor->execute([':id' => $id]);

            // =========================================================================
            // PENGISIAN DATA ID LOGS INSTAN (HAPUS DATA VENDOR)
            // =========================================================================
            write_log($conn, "Menghapus total data vendor: '" . $nama_vendor . "' beserta seluruh kontak relasinya", "vendors", $id);

            // E. Commit seluruh rangkaian query transaksi database jika sukses tanpa kendala
            $conn->commit();
        }

        header("Location: vendors.php?status=success_delete");
        exit();
    } catch (Exception $e) {
        // Batalkan semua query hapus jika ada salah satu operasi tabel anak/induk yang gagal
        $conn->rollBack();
        die("Error hapus data akibat batasan sistem: " . $e->getMessage());
    }
}

// Jika tidak ada aksi valid, kembalikan ke halaman utama vendor
header("Location: vendors.php");
exit();
?>
