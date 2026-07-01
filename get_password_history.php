<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Koneksi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Menangkap ID Vault dari parameter AJAX Fetch
$vault_id = isset($_GET['vault_id']) ? intval($_GET['vault_id']) : 0;

if ($vault_id <= 0) {
    echo '<div class="text-center text-danger small p-2">ID tidak valid.</div>';
    exit;
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Query mengambil riwayat kata sandi diurutkan dari yang TERBARU (ORDER BY id DESC)
    $stmt = $conn->prepare("SELECT id, password_lama, diubah_pada FROM password_histories WHERE vault_id = :vault_id ORDER BY id DESC");
    $stmt->execute([':vault_id' => $vault_id]);
    $histories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Tampilan struktur tabel di dalam modal popup
    if (empty($histories)) {
        echo '<div class="text-center py-3 text-muted small">
                <i class="bi bi-info-circle d-block mb-1 fs-5"></i>
                Belum ada riwayat perubahan kata sandi untuk akun ini.
              </div>';
    } else {
        echo '<div class="table-responsive">
                <table class="table table-sm table-bordered table-striped align-middle mb-0 small" style="font-size: 0.85rem;">
                    <thead class="table-light text-dark fw-bold">
                        <tr>
                            <th width="45%">Tanggal Diubah</th>
                            <th>Password Lama</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-dark">';
                    
        foreach ($histories as $index => $history) {
            $input_id = "histPass_" . $vault_id . "_" . $history['id'];
            echo '<tr>
                    <td class="text-secondary fw-medium">' . htmlspecialchars($history['diubah_pada'] ?? '-') . '</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="password" class="form-control bg-light border-0 py-0" style="font-size: 0.85rem;" value="' . htmlspecialchars($history['password_lama'] ?? '') . '" readonly id="' . $input_id . '">
                            <button class="btn btn-outline-secondary border-0 py-0" type="button" onclick="toggleHistoryPassword(\'' . $input_id . '\')">
                                <i class="bi bi-eye" id="icon_' . $input_id . '"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-link btn-sm p-0 text-decoration-none text-primary" onclick="copyToClipboard(\'' . $input_id . '\')">
                            <i class="bi bi-clipboard"></i> Salin
                        </button>
                    </td>
                  </tr>';
        }
        
        echo '    </tbody>
                </table>
              </div>';
    }

} catch (PDOException $e) {
    echo '<div class="text-center text-danger small p-2">Error Database: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
