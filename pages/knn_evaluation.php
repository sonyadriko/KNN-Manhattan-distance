<?php
session_start();
require_once '../config/db.php';
require_once '../includes/knn_algorithm.php';
require_once '../includes/auth_check.php';

// Check admin role
checkAdminRole();

// Initialize variables
$cross_validation_result = null;
$confusion_matrix_result = null;
$error_message = null;
$evaluation_type = null;

// Check if evaluation requested
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $k_value = isset($_POST['k']) ? intval($_POST['k']) : 3;
    $evaluation_type = $_POST['evaluation_type'] ?? '';
    
    try {
        $knn = new KNNAlgorithm($conn);
        
        if ($evaluation_type === 'cross_validation') {
            $folds = isset($_POST['folds']) ? intval($_POST['folds']) : 5;
            $cross_validation_result = $knn->crossValidation($k_value, $folds);
        } elseif ($evaluation_type === 'confusion_matrix') {
            $confusion_matrix_result = $knn->calculateConfusionMatrixStandalone($k_value);
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Kinerja KNN - Helm Classification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .evaluation-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .confusion-matrix-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .confusion-cell {
            text-align: center;
            font-weight: bold;
            padding: 15px;
        }
        .confusion-cell.diagonal {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .confusion-cell.off-diagonal {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }
        .metric-card {
            border-left: 4px solid #667eea;
            transition: transform 0.2s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .fold-result {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #17a2b8;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }
    </style>
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
                        <h2><i class="fas fa-chart-line me-2"></i>Evaluasi Kinerja KNN</h2>
                        <p class="text-muted">Evaluasi performa algoritma K-Nearest Neighbor menggunakan Cross Validation dan Confusion Matrix</p>
                    </div>
                    <div>
                        <a href="knn_prediction.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-robot me-2"></i>Prediction
                        </a>
                        <a href="knn_data.php" class="btn btn-outline-secondary">
                            <i class="fas fa-database me-2"></i>Data Training
                        </a>
                    </div>
                </div>

                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Cross Validation Results -->
                <?php if ($cross_validation_result): ?>
                <div class="evaluation-card mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h3><i class="fas fa-chart-bar me-2"></i>Hasil Cross Validation</h3>
                            <div class="d-flex align-items-center mb-3">
                                <h1 class="display-4 me-3 mb-0"><?= number_format($cross_validation_result['overall_accuracy'] * 100, 1) ?>%</h1>
                                <div>
                                    <small>Overall Accuracy</small><br>
                                    <small><?= $cross_validation_result['fold_count'] ?>-Fold Cross Validation</small><br>
                                    <small>K = <?= $cross_validation_result['k_value'] ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="accuracyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fold Details -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-layer-group me-2"></i>Detail Setiap Fold</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($cross_validation_result['folds'] as $fold): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="fold-result">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong>Fold <?= $fold['fold'] ?></strong>
                                                <span class="badge bg-success">
                                                    <?= number_format($fold['accuracy'] * 100, 1) ?>% Accuracy
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                <?= count($fold['predictions']) ?> samples tested
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cross Validation Confusion Matrix -->
                <?php if (isset($cross_validation_result['confusion_matrix'])): ?>
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-table me-2"></i>Confusion Matrix (Cross Validation)</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                $matrix = $cross_validation_result['confusion_matrix']['matrix'];
                                $classes = $cross_validation_result['confusion_matrix']['classes'];
                                ?>
                                <div class="table-responsive">
                                    <table class="table confusion-matrix-table">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Actual \ Predicted</th>
                                                <?php foreach ($classes as $class): ?>
                                                <th class="text-center"><?= $class ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($classes as $actual_class): ?>
                                            <tr>
                                                <td class="text-center"><strong><?= $actual_class ?></strong></td>
                                                <?php foreach ($classes as $predicted_class): ?>
                                                <td class="confusion-cell <?= $actual_class == $predicted_class ? 'diagonal' : 'off-diagonal' ?>">
                                                    <?= $matrix[$actual_class][$predicted_class] ?>
                                                </td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-calculator me-2"></i>Metrics per Class</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($cross_validation_result['confusion_matrix']['metrics'] as $class => $metrics): ?>
                                <div class="metric-card card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="mb-2"><?= $class ?></h6>
                                        <small>
                                            <strong>Precision:</strong> <?= number_format($metrics['precision'] * 100, 1) ?>%<br>
                                            <strong>Recall:</strong> <?= number_format($metrics['recall'] * 100, 1) ?>%<br>
                                            <strong>F1-Score:</strong> <?= number_format($metrics['f1_score'] * 100, 1) ?>%<br>
                                            <strong>Support:</strong> <?= $metrics['support'] ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Confusion Matrix Results (Standalone) -->
                <?php if ($confusion_matrix_result): ?>
                <div class="evaluation-card mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h3><i class="fas fa-table me-2"></i>Confusion Matrix Analysis</h3>
                            <div class="d-flex align-items-center mb-3">
                                <h1 class="display-4 me-3 mb-0"><?= number_format($confusion_matrix_result['accuracy'] * 100, 1) ?>%</h1>
                                <div>
                                    <small>Overall Accuracy</small><br>
                                    <small><?= $confusion_matrix_result['method'] ?></small><br>
                                    <small>K = <?= $confusion_matrix_result['k_value'] ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="confusionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Standalone Confusion Matrix -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-table me-2"></i>Confusion Matrix (Leave-One-Out)</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                $matrix = $confusion_matrix_result['confusion_matrix']['matrix'];
                                $classes = $confusion_matrix_result['confusion_matrix']['classes'];
                                ?>
                                <div class="table-responsive">
                                    <table class="table confusion-matrix-table">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Actual \ Predicted</th>
                                                <?php foreach ($classes as $class): ?>
                                                <th class="text-center"><?= $class ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($classes as $actual_class): ?>
                                            <tr>
                                                <td class="text-center"><strong><?= $actual_class ?></strong></td>
                                                <?php foreach ($classes as $predicted_class): ?>
                                                <td class="confusion-cell <?= $actual_class == $predicted_class ? 'diagonal' : 'off-diagonal' ?>">
                                                    <?= $matrix[$actual_class][$predicted_class] ?>
                                                </td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-calculator me-2"></i>Performance Metrics</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($confusion_matrix_result['confusion_matrix']['metrics'] as $class => $metrics): ?>
                                <div class="metric-card card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="mb-2"><?= $class ?></h6>
                                        <small>
                                            <strong>Precision:</strong> <?= number_format($metrics['precision'] * 100, 1) ?>%<br>
                                            <strong>Recall:</strong> <?= number_format($metrics['recall'] * 100, 1) ?>%<br>
                                            <strong>F1-Score:</strong> <?= number_format($metrics['f1_score'] * 100, 1) ?>%<br>
                                            <strong>Support:</strong> <?= $metrics['support'] ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Evaluation Controls -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cog me-2"></i>Cross Validation</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small">
                                    Cross Validation membagi data training menjadi beberapa fold dan menggunakan setiap fold secara bergantian sebagai data test.
                                </p>
                                <form method="POST">
                                    <input type="hidden" name="evaluation_type" value="cross_validation">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nilai K</label>
                                                <select class="form-select" name="k">
                                                    <option value="3" <?= ($_POST['k'] ?? 3) == 3 ? 'selected' : '' ?>>K = 3</option>
                                                    <option value="5" <?= ($_POST['k'] ?? 3) == 5 ? 'selected' : '' ?>>K = 5</option>
                                                    <option value="7" <?= ($_POST['k'] ?? 3) == 7 ? 'selected' : '' ?>>K = 7</option>
                                                    <option value="9" <?= ($_POST['k'] ?? 3) == 9 ? 'selected' : '' ?>>K = 9</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label">Jumlah Fold</label>
                                                <select class="form-select" name="folds">
                                                    <option value="3" <?= ($_POST['folds'] ?? 5) == 3 ? 'selected' : '' ?>>3-Fold</option>
                                                    <option value="5" <?= ($_POST['folds'] ?? 5) == 5 ? 'selected' : '' ?>>5-Fold</option>
                                                    <option value="10" <?= ($_POST['folds'] ?? 5) == 10 ? 'selected' : '' ?>>10-Fold</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-play me-2"></i>Jalankan Cross Validation
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-table me-2"></i>Confusion Matrix</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small">
                                    Confusion Matrix menggunakan Leave-One-Out method untuk mengevaluasi setiap data training dengan menggunakan data lainnya sebagai training set.
                                </p>
                                <form method="POST">
                                    <input type="hidden" name="evaluation_type" value="confusion_matrix">
                                    <div class="mb-3">
                                        <label class="form-label">Nilai K</label>
                                        <select class="form-select" name="k">
                                            <option value="3" <?= ($_POST['k'] ?? 3) == 3 ? 'selected' : '' ?>>K = 3</option>
                                            <option value="5" <?= ($_POST['k'] ?? 3) == 5 ? 'selected' : '' ?>>K = 5</option>
                                            <option value="7" <?= ($_POST['k'] ?? 3) == 7 ? 'selected' : '' ?>>K = 7</option>
                                            <option value="9" <?= ($_POST['k'] ?? 3) == 9 ? 'selected' : '' ?>>K = 9</option>
                                        </select>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-calculator me-2"></i>Hitung Confusion Matrix
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-info-circle me-2"></i>Tentang Evaluasi Kinerja</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Cross Validation</h6>
                                        <p class="small text-muted">
                                            Cross Validation adalah teknik evaluasi yang membagi dataset menjadi beberapa fold. 
                                            Setiap fold digunakan sekali sebagai data test sementara fold lainnya digunakan sebagai data training.
                                            Ini memberikan estimasi yang lebih robust tentang performa model.
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Confusion Matrix</h6>
                                        <p class="small text-muted">
                                            Confusion Matrix adalah tabel yang menampilkan performa klasifikasi dengan menunjukkan 
                                            hubungan antara kelas aktual dan prediksi. Dari matrix ini dapat dihitung precision, 
                                            recall, dan F1-score untuk setiap kelas.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart for Cross Validation Results
        <?php if ($cross_validation_result): ?>
        const accuracyCtx = document.getElementById('accuracyChart').getContext('2d');
        const accuracyChart = new Chart(accuracyCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($cross_validation_result['folds'] as $fold): ?>'Fold <?= $fold['fold'] ?>',<?php endforeach; ?>'Overall'],
                datasets: [{
                    label: 'Accuracy (%)',
                    data: [
                        <?php foreach ($cross_validation_result['folds'] as $fold): ?>
                        <?= $fold['accuracy'] * 100 ?>,
                        <?php endforeach; ?>
                        <?= $cross_validation_result['overall_accuracy'] * 100 ?>
                    ],
                    backgroundColor: [
                        <?php foreach ($cross_validation_result['folds'] as $fold): ?>
                        'rgba(54, 162, 235, 0.6)',
                        <?php endforeach; ?>
                        'rgba(255, 99, 132, 0.8)'
                    ],
                    borderColor: [
                        <?php foreach ($cross_validation_result['folds'] as $fold): ?>
                        'rgba(54, 162, 235, 1)',
                        <?php endforeach; ?>
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Chart for Confusion Matrix
        <?php if ($confusion_matrix_result): ?>
        const confusionCtx = document.getElementById('confusionChart').getContext('2d');
        const confusionChart = new Chart(confusionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Correct Predictions', 'Incorrect Predictions'],
                datasets: [{
                    data: [
                        <?= $confusion_matrix_result['accuracy'] * 100 ?>,
                        <?= (1 - $confusion_matrix_result['accuracy']) * 100 ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>