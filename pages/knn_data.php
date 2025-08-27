<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';

// Simple auth check
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Handle CRUD operations
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';

// CREATE
if ($_POST && !isset($_POST['id']) && !isset($_POST['confirm_delete_all'])) {
    $no_data = $_POST['no_data'];
    $merk = $_POST['merk'];
    $nama = $_POST['nama'];
    $jenis = $_POST['jenis'];
    $harga = $_POST['harga'];
    $standar = $_POST['standar'];
    $kaca = $_POST['kaca'];
    $double_visor = $_POST['double_visor'];
    $ventilasi_udara = $_POST['ventilasi_udara'];
    $berat = $_POST['berat'];
    $wire_lock = $_POST['wire_lock'];
    $kelas = $_POST['kelas'];
    
    $sql = "INSERT INTO knn_training_data (no_data, merk, nama, jenis, harga, standar, kaca, double_visor, ventilasi_udara, berat, wire_lock, kelas) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssissssdss", $no_data, $merk, $nama, $jenis, $harga, $standar, $kaca, $double_visor, $ventilasi_udara, $berat, $wire_lock, $kelas);
    
    if ($stmt->execute()) {
        $message = "Data berhasil ditambahkan!";
    } else {
        $message = "Error: " . $stmt->error;
    }
}

// UPDATE
if ($_POST && isset($_POST['id']) && !isset($_POST['confirm_delete_all'])) {
    $id = $_POST['id'];
    $no_data = $_POST['no_data'];
    $merk = $_POST['merk'];
    $nama = $_POST['nama'];
    $jenis = $_POST['jenis'];
    $harga = $_POST['harga'];
    $standar = $_POST['standar'];
    $kaca = $_POST['kaca'];
    $double_visor = $_POST['double_visor'];
    $ventilasi_udara = $_POST['ventilasi_udara'];
    $berat = $_POST['berat'];
    $wire_lock = $_POST['wire_lock'];
    $kelas = $_POST['kelas'];
    
    $sql = "UPDATE knn_training_data SET no_data=?, merk=?, nama=?, jenis=?, harga=?, standar=?, kaca=?, double_visor=?, ventilasi_udara=?, berat=?, wire_lock=?, kelas=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssissssdssi", $no_data, $merk, $nama, $jenis, $harga, $standar, $kaca, $double_visor, $ventilasi_udara, $berat, $wire_lock, $kelas, $id);
    
    if ($stmt->execute()) {
        $message = "Data berhasil diupdate!";
    } else {
        $message = "Error: " . $stmt->error;
    }
}

// DELETE
if ($action == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM knn_training_data WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Data berhasil dihapus!";
    } else {
        $message = "Error: " . $stmt->error;
    }
}

// DELETE ALL
if ($action == 'delete_all' && isset($_POST['confirm_delete_all'])) {
    try {
        echo "<!-- DEBUG: Starting delete all process -->\n";
        
        $sql = "DELETE FROM knn_training_data";
        echo "<!-- DEBUG: Executing SQL: $sql -->\n";
        
        $result = $conn->query($sql);
        echo "<!-- DEBUG: Query result: " . ($result ? 'SUCCESS' : 'FAILED') . " -->\n";
        
        if ($result) {
            echo "<!-- DEBUG: Resetting auto increment -->\n";
            $conn->query("ALTER TABLE knn_training_data AUTO_INCREMENT = 1");
            $message = "Semua data training berhasil dihapus!";
            echo "<!-- DEBUG: Delete completed successfully -->\n";
        } else {
            $message = "Error: " . $conn->error;
            echo "<!-- DEBUG: MySQL Error: " . $conn->error . " -->\n";
        }
    } catch (Exception $e) {
        $message = "PHP Error: " . $e->getMessage();
        echo "<!-- DEBUG: PHP Exception: " . $e->getMessage() . " -->\n";
    }
}

// READ - Get data for edit
$edit_data = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM knn_training_data WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
}

// READ - Get all data
$sql = "SELECT * FROM knn_training_data ORDER BY no_data";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Data Training KNN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
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
                    <h2><i class="fas fa-database me-2"></i>Kelola Data Training KNN</h2>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <a href="knn_upload_excel.php" class="btn btn-success me-2">
                            <i class="fas fa-file-excel me-2"></i>Upload Excel
                        </a>
                        <a href="knn_template.php" class="btn btn-info me-2">
                            <i class="fas fa-download me-2"></i>Template Excel
                        </a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                            <i class="fas fa-trash me-2"></i>Delete All Data
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Form Add/Edit -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i><?= $edit_data ? 'Edit Data' : 'Tambah Data Baru' ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($edit_data): ?>
                                <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">No Data</label>
                                        <input type="number" class="form-control" name="no_data" 
                                               value="<?= $edit_data['no_data'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Merk</label>
                                        <input type="text" class="form-control" name="merk" 
                                               value="<?= $edit_data['merk'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Helm</label>
                                        <input type="text" class="form-control" name="nama" 
                                               value="<?= $edit_data['nama'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Jenis</label>
                                        <select class="form-select" name="jenis" required>
                                            <option value="">Pilih Jenis</option>
                                            <option value="Fullface" <?= ($edit_data['jenis'] ?? '') == 'Fullface' ? 'selected' : '' ?>>Fullface</option>
                                            <option value="Half face" <?= ($edit_data['jenis'] ?? '') == 'Half face' ? 'selected' : '' ?>>Half face</option>
                                            <option value="Retro" <?= ($edit_data['jenis'] ?? '') == 'Retro' ? 'selected' : '' ?>>Retro</option>
                                            <option value="Modular" <?= ($edit_data['jenis'] ?? '') == 'Modular' ? 'selected' : '' ?>>Modular</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Harga (Rp)</label>
                                        <input type="number" class="form-control" name="harga" 
                                               value="<?= $edit_data['harga'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Standar</label>
                                        <select class="form-select" name="standar" required>
                                            <option value="">Pilih Standar</option>
                                            <option value="SNI" <?= ($edit_data['standar'] ?? '') == 'SNI' ? 'selected' : '' ?>>SNI</option>
                                            <option value="SNI, DOT" <?= ($edit_data['standar'] ?? '') == 'SNI, DOT' ? 'selected' : '' ?>>SNI, DOT</option>
                                            <option value="SNI, DOT, ECE" <?= ($edit_data['standar'] ?? '') == 'SNI, DOT, ECE' ? 'selected' : '' ?>>SNI, DOT, ECE</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">Kaca</label>
                                        <select class="form-select" name="kaca" required>
                                            <option value="Ya" <?= ($edit_data['kaca'] ?? '') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                            <option value="No" <?= ($edit_data['kaca'] ?? '') == 'No' ? 'selected' : '' ?>>Tidak</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">Double Visor</label>
                                        <select class="form-select" name="double_visor" required>
                                            <option value="Ya" <?= ($edit_data['double_visor'] ?? '') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                            <option value="No" <?= ($edit_data['double_visor'] ?? '') == 'No' ? 'selected' : '' ?>>Tidak</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">Ventilasi</label>
                                        <select class="form-select" name="ventilasi_udara" required>
                                            <option value="Ya" <?= ($edit_data['ventilasi_udara'] ?? '') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                            <option value="No" <?= ($edit_data['ventilasi_udara'] ?? '') == 'No' ? 'selected' : '' ?>>Tidak</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Berat (kg)</label>
                                        <input type="number" step="0.1" class="form-control" name="berat" 
                                               value="<?= $edit_data['berat'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Wire Lock</label>
                                        <select class="form-select" name="wire_lock" required>
                                            <option value="Ya" <?= ($edit_data['wire_lock'] ?? '') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                            <option value="No" <?= ($edit_data['wire_lock'] ?? '') == 'No' ? 'selected' : '' ?>>Tidak</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Kelas</label>
                                        <select class="form-select" name="kelas" required>
                                            <option value="">Pilih Kelas</option>
                                            <option value="Mahal" <?= ($edit_data['kelas'] ?? '') == 'Mahal' ? 'selected' : '' ?>>Mahal</option>
                                            <option value="Murah" <?= ($edit_data['kelas'] ?? '') == 'Murah' ? 'selected' : '' ?>>Murah</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="mb-3 w-100">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-2"></i><?= $edit_data ? 'Update' : 'Simpan' ?>
                                        </button>
                                        <?php if ($edit_data): ?>
                                        <a href="knn_data.php" class="btn btn-secondary w-100 mt-2">
                                            <i class="fas fa-times me-2"></i>Batal
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Data Training (<?= $result->num_rows ?> data)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Merk</th>
                                        <th>Nama</th>
                                        <th>Jenis</th>
                                        <th>Harga</th>
                                        <th>Standar</th>
                                        <th>DV</th>
                                        <th>Berat</th>
                                        <th>WL</th>
                                        <th>Kelas</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['no_data'] ?></td>
                                            <td><?= $row['merk'] ?></td>
                                            <td><?= $row['nama'] ?></td>
                                            <td><span class="badge bg-info"><?= $row['jenis'] ?></span></td>
                                            <td>Rp <?= number_format($row['harga']) ?></td>
                                            <td><small><?= $row['standar'] ?></small></td>
                                            <td><?= $row['double_visor'] == 'Ya' ? '✓' : '✗' ?></td>
                                            <td><?= $row['berat'] ?>kg</td>
                                            <td><?= $row['wire_lock'] == 'Ya' ? '✓' : '✗' ?></td>
                                            <td>
                                                <span class="badge bg-<?= $row['kelas'] == 'Mahal' ? 'danger' : 'success' ?>">
                                                    <?= $row['kelas'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=edit&id=<?= $row['id'] ?>" class="btn btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-danger"
                                                       onclick="return confirm('Yakin hapus data ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center">Tidak ada data</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete All Confirmation Modal -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Semua Data
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-warning me-2"></i>
                        <strong>PERHATIAN!</strong> Tindakan ini akan menghapus SEMUA data training KNN dan tidak dapat dibatalkan.
                    </div>
                    <p>Apakah Anda yakin ingin menghapus semua data training?</p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                        <label class="form-check-label" for="confirmCheck">
                            Saya memahami bahwa tindakan ini tidak dapat dibatalkan
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <form method="POST" style="display: inline;" id="deleteAllForm">
                        <input type="hidden" name="confirm_delete_all" value="1">
                        <button type="submit" class="btn btn-danger no-loading" id="deleteAllBtn" disabled>
                            <i class="fas fa-trash me-2"></i>Ya, Hapus Semua Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="../assets/js/dashboard.js"></script> -->
    <script>
        // Enable delete button only when checkbox is checked
        document.getElementById('confirmCheck').addEventListener('change', function() {
            document.getElementById('deleteAllBtn').disabled = !this.checked;
        });
        
        // Add URL parameter for delete_all action
        document.getElementById('deleteAllForm').addEventListener('submit', function(e) {
            this.action = '?action=delete_all';
            
            // Double confirmation
            if (!confirm('Apakah Anda BENAR-BENAR yakin ingin menghapus SEMUA data training?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>