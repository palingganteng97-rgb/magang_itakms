<?php
require_once __DIR__ . '/auth.php';
require_login();

// SISIPKAN PROTEKSI RBAC DI SINI
require_once __DIR__ . '/helper_rbac.php';
protect_page_by_table('network_devices', 'R'); // Mengunci akses baca (Read) secara ketat sesuai nama tabel database asli Anda

// 1. KONFIGURASI KONEKSI DATABASE
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    // SINKRONISASI: Mengubah charset menjadi utf8mb4 agar kompatibel penuh dengan pembacaan karakter khusus
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Koneksi database gagal: " . $e->getMessage();
    exit;
}

// 2. CEK APAKAH ADA PERINTAH AKSES (POST ACTION)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        // --- BLOK LOGIKA GABUNGAN: BUILDINGS (GEDUNG) ---
        if ($action === 'add_building') {
            $nama = trim($_POST['nama'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO buildings (nama, alamat, status) VALUES (?, ?, 1)");
                $sukses = $stmt->execute([$nama, $alamat]);
                
                if ($sukses) {
                    $new_id = $conn->lastInsertId();
                    write_log($conn, "Menambahkan data gedung baru: " . $nama, "buildings", $new_id);
                }
            }
        }
        if ($action === 'edit_building') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("UPDATE buildings SET nama = ?, alamat = ? WHERE id = ?");
                $sukses = $stmt->execute([$nama, $alamat, $id]);
                
                if ($sukses) {
                    write_log($conn, "Mengubah informasi data gedung: " . $nama, "buildings", $id);
                }
            }
        }
        if ($action === 'delete_building') {
            $id = (int)$_POST['id'];
            
            // Ambil nama gedung sebelum dihapus demi info log yang detail
            $get_name = $conn->prepare("SELECT nama FROM buildings WHERE id = ?");
            $get_name->execute([$id]);
            $nama_gedung = $get_name->fetchColumn() ?: 'Unknown';

            $sukses = $conn->prepare("DELETE FROM buildings WHERE id = ?")->execute([$id]);
            
            if ($sukses) {
                write_log($conn, "Menghapus data gedung: " . $nama_gedung, "buildings", $id);
            }
        }

        // --- BLOK LOGIKA GABUNGAN: FLOORS (LANTAI) ---
        if ($action === 'add_floor') {
            $nama = trim($_POST['nama'] ?? '');
            $building_id = (int)$_POST['building_id'];
            if ($nama !== '' && $building_id > 0) {
                $stmt = $conn->prepare("INSERT INTO floors (nama, building_id, status) VALUES (?, ?, 1)");
                $sukses = $stmt->execute([$nama, $building_id]);
                
                if ($sukses) {
                    $new_id = $conn->lastInsertId();
                    write_log($conn, "Menambahkan data lantai baru: " . $nama, "floors", $new_id);
                }
            }
        }
        if ($action === 'edit_floor') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $building_id = (int)$_POST['building_id'];
            if ($nama !== '' && $building_id > 0) {
                $stmt = $conn->prepare("UPDATE floors SET nama = ?, building_id = ? WHERE id = ?");
                $sukses = $stmt->execute([$nama, $building_id, $id]);
                
                if ($sukses) {
                    write_log($conn, "Mengubah data lantai menjadi: " . $nama, "floors", $id);
                }
            }
        }
        if ($action === 'delete_floor') {
            $id = (int)$_POST['id'];
            
            // Ambil nama lantai sebelum dihapus demi info log yang detail
            $get_name = $conn->prepare("SELECT nama FROM floors WHERE id = ?");
            $get_name->execute([$id]);
            $nama_lantai = $get_name->fetchColumn() ?: 'Unknown';

            $sukses = $conn->prepare("DELETE FROM floors WHERE id = ?")->execute([$id]);
            
            if ($sukses) {
                write_log($conn, "Menghapus data lantai: " . $nama_lantai, "floors", $id);
            }
        }

        // --- BLOK LOGIKA GABUNGAN: ROOMS (RUANGAN) ---
        if ($action === 'add_room') {
            $nama = trim($_POST['nama'] ?? '');
            $floor_id = (int)$_POST['floor_id'];
            $kode = trim($_POST['kode'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            
            if ($nama !== '' && $floor_id > 0) {
                $stmt = $conn->prepare("INSERT INTO rooms (nama, floor_id, kode, telepon, status) VALUES (?, ?, ?, ?, 1)");
                $sukses = $stmt->execute([$nama, $floor_id, $kode, $telepon]);
                
                if ($sukses) {
                    $new_id = $conn->lastInsertId();
                    write_log($conn, "Menambahkan data ruangan baru: " . $nama . " (" . $kode . ")", "rooms", $new_id);
                }
            }
        }
        if ($action === 'edit_room') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $floor_id = (int)$_POST['floor_id'];
            $kode = trim($_POST['kode'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            
            if ($nama !== '' && $floor_id > 0) {
                $stmt = $conn->prepare("UPDATE rooms SET nama = ?, floor_id = ?, kode = ?, telepon = ? WHERE id = ?");
                $sukses = $stmt->execute([$nama, $floor_id, $kode, $telepon, $id]);
                
                if ($sukses) {
                    write_log($conn, "Mengubah informasi data ruangan: " . $nama . " (" . $kode . ")", "rooms", $id);
                }
            }
        }
        if ($action === 'delete_room') {
            $id = (int)$_POST['id'];
            
            // Ambil nama ruangan sebelum dihapus demi info log yang detail
            $get_name = $conn->prepare("SELECT nama, kode FROM rooms WHERE id = ?");
            $get_name->execute([$id]);
            $room_data = $get_name->fetch(PDO::FETCH_ASSOC);
            $nama_ruangan = $room_data ? $room_data['nama'] . " (" . $room_data['kode'] . ")" : 'Unknown';

            $sukses = $conn->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
            
            if ($sukses) {
                write_log($conn, "Menghapus data ruangan: " . $nama_ruangan, "rooms", $id);
            }
        }

        // KABARKAN KE BROWSER JIKA PROSES BERHASIL
        http_response_code(200);
        echo "Sukses";
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database Error: " . $e->getMessage();
        exit;
    }
}
?>
