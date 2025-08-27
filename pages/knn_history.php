<?php
session_start();
require_once '../config/db.php';

// Simple auth check
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$total_result = $conn->query("SELECT COUNT(*) as total FROM knn_predictions");
$total_count = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $limit);

// Get predictions with pagination
$sql = "SELECT * FROM knn_predictions ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Statistics
$stats_sql = "SELECT 
    predicted_class,
    COUNT(*) as count,
    AVG(confidence_score) as avg_confidence
    FROM knn_predictions 
    GROUP BY predicted_class";
$stats_result = $conn->query($stats_sql);
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['predicted_class']] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Prediksi KNN</title>
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
                    <div>
                        <h2><i class="fas fa-history me-2"></i>History Prediksi KNN</h2>
                        <p class="text-muted">Riwayat prediksi klasifikasi helm yang telah dilakukan</p>
                    </div>
                    <div>
                        <a href="knn_prediction.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Prediksi Baru
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-calculator fa-2x text-primary mb-2"></i>
                                <h4><?= $total_count ?></h4>
                                <small class="text-muted">Total Prediksi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-arrow-up fa-2x text-danger mb-2"></i>
                                <h4><?= $stats['Mahal']['count'] ?? 0 ?></h4>
                                <small class="text-muted">Prediksi Mahal</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-arrow-down fa-2x text-success mb-2"></i>
                                <h4><?= $stats['Murah']['count'] ?? 0 ?></h4>
                                <small class="text-muted">Prediksi Murah</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-bullseye fa-2x text-info mb-2"></i>
                                <h4><?= number_format((($stats['Mahal']['avg_confidence'] ?? 0) + ($stats['Murah']['avg_confidence'] ?? 0)) / 2 * 100, 1) ?>%</h4>
                                <small class="text-muted">Avg Confidence</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Riwayat Prediksi</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Harga</th>
                                        <th>Standar</th>
                                        <th>Kaca</th>
                                        <th>Double Visor</th>
                                        <th>Berat</th>
                                        <th>Wire Lock</th>
                                        <th>Prediksi</th>
                                        <th>Confidence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <small>
                                                <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>Rp <?= number_format($row['harga']) ?></td>
                                        <td><small><?= $row['standar'] ?></small></td>
                                        <td>
                                            <?= $row['kaca'] == 'Ya' ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?>
                                        </td>
                                        <td>
                                            <?= $row['double_visor'] == 'Ya' ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?>
                                        </td>
                                        <td><?= $row['berat'] ?> kg</td>
                                        <td>
                                            <?= $row['wire_lock'] == 'Ya' ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $row['predicted_class'] == 'Mahal' ? 'danger' : 'success' ?>">
                                                <?= $row['predicted_class'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-<?= $row['predicted_class'] == 'Mahal' ? 'danger' : 'success' ?>" 
                                                     style="width: <?= $row['confidence_score'] * 100 ?>%"></div>
                                            </div>
                                            <small><?= number_format($row['confidence_score'] * 100, 1) ?>%</small>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum Ada History Prediksi</h5>
                            <p class="text-muted">Mulai buat prediksi untuk melihat riwayat di sini</p>
                            <a href="knn_prediction.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Buat Prediksi Pertama
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>