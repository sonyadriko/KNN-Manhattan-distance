<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkAuth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KNN Sistem Rekomendasi Helm</title>
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
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">Dashboard</h1>
                        <p class="text-muted">Selamat datang di sistem rekomendasi helm menggunakan metode K-Nearest Neighbor</p>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Data Training
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            $result = $conn->query("SELECT COUNT(*) as total FROM knn_training_data");
                                            $count = $result->fetch_assoc();
                                            echo $count['total'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stats-icon bg-primary">
                                            <i class="fas fa-database"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
<!--                     
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Prediksi Hari Ini
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            $result = $conn->query("SELECT COUNT(*) as total FROM knn_predictions WHERE DATE(created_at) = CURDATE()");
                                            $count = $result->fetch_assoc();
                                            echo $count['total'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stats-icon bg-success">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Akurasi Model
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">95.2%</div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stats-icon bg-info">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total Prediksi
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            $result = $conn->query("SELECT COUNT(*) as total FROM knn_predictions");
                                            $count = $result->fetch_assoc();
                                            echo $count['total'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stats-icon bg-warning">
                                            <i class="fas fa-calculator"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-0">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Aktivitas Terbaru
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-4">
                                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada aktivitas untuk ditampilkan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-0">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Informasi Sistem
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Metode:</small>
                                    <div class="fw-bold">K-Nearest Neighbor</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Aplikasi:</small>
                                    <div class="fw-bold">Rekomendasi Helm</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Status:</small>
                                    <span class="badge bg-success">Aktif</span>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <a href="knn_prediction.php" class="btn btn-primary btn-sm mb-2 d-block">
                                        <i class="fas fa-robot me-2"></i>
                                        KNN Prediction
                                    </a>
                                    <a href="knn_data.php" class="btn btn-success btn-sm mb-2 d-block">
                                        <i class="fas fa-database me-2"></i>
                                        Kelola Data KNN
                                    </a>
                                    <!-- <a href="knn_history.php" class="btn btn-warning btn-sm mb-2 d-block">
                                        <i class="fas fa-history me-2"></i>
                                        History Prediksi
                                    </a>
                                    <a href="http://localhost:5000" class="btn btn-info btn-sm d-block" target="_blank">
                                        <i class="fas fa-flask me-2"></i>
                                        Flask KNN App
                                    </a> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>