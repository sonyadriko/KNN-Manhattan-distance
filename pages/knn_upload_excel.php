<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

checkAuth();

echo "<!-- DEBUG: Auth passed -->\n";

$message = '';
$error = '';
$upload_results = [];

echo "<!-- DEBUG: Variables initialized -->\n";
echo "<!-- DEBUG: POST check - " . (empty($_POST) ? 'EMPTY' : 'HAS DATA') . " -->\n";
echo "<!-- DEBUG: FILES check - " . (empty($_FILES) ? 'EMPTY' : 'HAS DATA') . " -->\n";
echo "<!-- DEBUG: POST data - " . print_r($_POST, true) . " -->\n";
echo "<!-- DEBUG: FILES data - " . print_r($_FILES, true) . " -->\n";

if (isset($_FILES['excel_file']) && !empty($_FILES['excel_file']['tmp_name'])) {
    echo "<!-- DEBUG: Processing file upload -->\n";
    $file = $_FILES['excel_file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Error uploading file: " . $file['error'];
    } elseif (!in_array($file['type'], [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel' // .xls
    ])) {
        $error = "File harus berformat Excel (.xlsx atau .xls)";
    } else {
        try {
            // Load Excel file
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row
            $header = array_shift($rows);
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($rows as $index => $row) {
                $row_number = $index + 2; // +2 because we skipped header and array is 0-indexed
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                try {
                    // Map Excel columns to database fields
                    $no_data = isset($row[0]) ? $row[0] : '';
                    $merk = isset($row[1]) ? $row[1] : '';
                    $nama = isset($row[2]) ? $row[2] : '';
                    $jenis = isset($row[3]) ? $row[3] : '';
                    $harga = isset($row[4]) ? intval($row[4]) : 0;
                    $standar = isset($row[5]) ? $row[5] : '';
                    $kaca = isset($row[6]) ? $row[6] : '';
                    $double_visor = isset($row[7]) ? $row[7] : '';
                    $ventilasi_udara = isset($row[8]) ? $row[8] : '';
                    $berat = isset($row[9]) ? floatval($row[9]) : 0.0;
                    $wire_lock = isset($row[10]) ? $row[10] : '';
                    $kelas = isset($row[11]) ? $row[11] : '';
                    
                    // Validate required fields
                    if (empty($no_data) || empty($merk) || empty($nama) || empty($kelas)) {
                        $upload_results[] = "Baris $row_number: Data tidak lengkap (no_data, merk, nama, kelas harus diisi)";
                        $error_count++;
                        continue;
                    }
                    
                    // Insert to database
                    $sql = "INSERT INTO knn_training_data (no_data, merk, nama, jenis, harga, standar, kaca, double_visor, ventilasi_udara, berat, wire_lock, kelas) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                            merk=VALUES(merk), nama=VALUES(nama), jenis=VALUES(jenis), 
                            harga=VALUES(harga), standar=VALUES(standar), kaca=VALUES(kaca),
                            double_visor=VALUES(double_visor), ventilasi_udara=VALUES(ventilasi_udara),
                            berat=VALUES(berat), wire_lock=VALUES(wire_lock), kelas=VALUES(kelas)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isssissssdss", $no_data, $merk, $nama, $jenis, $harga, $standar, $kaca, $double_visor, $ventilasi_udara, $berat, $wire_lock, $kelas);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                        $upload_results[] = "Baris $row_number: Berhasil - $merk $nama";
                    } else {
                        $upload_results[] = "Baris $row_number: Error database - " . $stmt->error;
                        $error_count++;
                    }
                    
                } catch (Exception $e) {
                    $upload_results[] = "Baris $row_number: Error - " . $e->getMessage();
                    $error_count++;
                }
            }
            
            if ($success_count > 0) {
                $message = "Upload berhasil! $success_count data ditambahkan/diupdate.";
                if ($error_count > 0) {
                    $message .= " $error_count data gagal diproses.";
                }
            } else {
                $error = "Tidak ada data yang berhasil diproses. $error_count error ditemukan.";
            }
            
        } catch (Exception $e) {
            $error = "Error membaca file Excel: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel - KNN Data Training</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div id="content" class="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-primary">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content -->
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-file-excel me-2"></i>Upload Data Excel</h2>
                        <p class="text-muted">Upload file Excel untuk import data training KNN</p>
                    </div>
                    <div>
                        <a href="knn_data.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <a href="#" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#templateModal">
                            <i class="fas fa-download me-2"></i>Download Template
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Upload Form -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-upload me-2"></i>Upload File Excel</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                    <div class="mb-4">
                                        <label class="form-label">Pilih File Excel</label>
                                        <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls" required id="fileInput">
                                        <div class="form-text">Format yang didukung: .xlsx, .xls. Maksimal 10MB</div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Format Excel yang Diharapkan:</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small>
                                                    <strong>Kolom A:</strong> No Data<br>
                                                    <strong>Kolom B:</strong> Merk<br>
                                                    <strong>Kolom C:</strong> Nama<br>
                                                    <strong>Kolom D:</strong> Jenis<br>
                                                    <strong>Kolom E:</strong> Harga<br>
                                                    <strong>Kolom F:</strong> Standar
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <small>
                                                    <strong>Kolom G:</strong> Kaca (Ya/No)<br>
                                                    <strong>Kolom H:</strong> Double Visor (Ya/No)<br>
                                                    <strong>Kolom I:</strong> Ventilasi Udara (Ya/No)<br>
                                                    <strong>Kolom J:</strong> Berat<br>
                                                    <strong>Kolom K:</strong> Wire Lock (Ya/No)<br>
                                                    <strong>Kolom L:</strong> Kelas (Mahal/Murah)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-upload me-2"></i>Upload & Import Data
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Results -->
                    <div class="col-md-4">
                        <?php if (!empty($upload_results)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-list me-2"></i>Hasil Upload</h6>
                            </div>
                            <div class="card-body">
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($upload_results as $result): ?>
                                    <div class="mb-2">
                                        <small class="<?= strpos($result, 'Error') !== false || strpos($result, 'gagal') !== false ? 'text-danger' : 'text-success' ?>">
                                            <?= htmlspecialchars($result) ?>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Template Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Gunakan template berikut untuk format Excel yang benar:</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>A</th><th>B</th><th>C</th><th>D</th><th>E</th><th>F</th>
                                    <th>G</th><th>H</th><th>I</th><th>J</th><th>K</th><th>L</th>
                                </tr>
                                <tr>
                                    <th>No Data</th><th>Merk</th><th>Nama</th><th>Jenis</th><th>Harga</th><th>Standar</th>
                                    <th>Kaca</th><th>Double Visor</th><th>Ventilasi Udara</th><th>Berat</th><th>Wire Lock</th><th>Kelas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td><td>Honda</td><td>XR150</td><td>Full Face</td><td>500000</td><td>SNI, DOT</td>
                                    <td>Ya</td><td>No</td><td>Ya</td><td>1.5</td><td>Ya</td><td>Murah</td>
                                </tr>
                                <tr>
                                    <td>2</td><td>Arai</td><td>RX7</td><td>Full Face</td><td>8000000</td><td>SNI, DOT, ECE, SNELL</td>
                                    <td>Ya</td><td>Ya</td><td>Ya</td><td>1.6</td><td>Ya</td><td>Mahal</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <a href="knn_template.php" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Download Template Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('uploadForm');
            const fileInput = document.getElementById('fileInput');
            
            console.log('Upload form loaded');
            
            form.addEventListener('submit', function(e) {
                console.log('Form submitted');
                console.log('File input:', fileInput.files);
                if (fileInput.files.length === 0) {
                    alert('Pilih file Excel terlebih dahulu!');
                    e.preventDefault();
                    return false;
                }
                
                const file = fileInput.files[0];
                console.log('Selected file:', file.name, file.size, file.type);
                
                if (!file.name.match(/\.(xlsx?|xls)$/i)) {
                    alert('File harus berformat Excel (.xlsx atau .xls)');
                    e.preventDefault();
                    return false;
                }
                
                if (file.size > 10 * 1024 * 1024) { // 10MB
                    alert('File terlalu besar. Maksimal 10MB');
                    e.preventDefault();
                    return false;
                }
                
                console.log('Form validation passed, submitting...');
            });
        });
    </script>
</body>
</html>