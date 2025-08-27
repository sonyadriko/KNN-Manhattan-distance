<?php
session_start();
require_once '../config/db.php';
require_once '../includes/knn_algorithm.php';

// Simple auth check
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Initialize KNN
$knn = new KNNAlgorithm($conn);

// Handle prediction submission
$prediction_result = null;
$error_message = null;

// Check if form submitted (same logic as simple version)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predict'])) {
    // Get form data
    $data = [
        'harga' => intval($_POST['harga']),
        'standar' => $_POST['standar'],
        'kaca' => ($_POST['kaca'] === 'Tidak') ? 'No' : 'Ya',
        'double_visor' => ($_POST['double_visor'] === 'Tidak') ? 'No' : 'Ya',
        'ventilasi_udara' => ($_POST['ventilasi_udara'] === 'Tidak') ? 'No' : 'Ya',
        'berat' => floatval($_POST['berat']),
        'wire_lock' => ($_POST['wire_lock'] === 'Tidak') ? 'No' : 'Ya'
    ];
    
    $k = isset($_POST['k']) ? intval($_POST['k']) : 3;
    
    try {
        // Run KNN Algorithm
        $knn_result = $knn->predict($data, $k);
        
        if ($knn_result) {
            $prediction_result = [
                'prediction' => $knn_result['predicted_class'],
                'confidence' => $knn_result['confidence'],
                'input_data' => $data,
                'knn_result' => $knn_result,
                'k' => $k
            ];
            
            // Save to database (optional - skip if error)
            try {
                $stmt = $conn->prepare("INSERT INTO knn_predictions (harga, standar, kaca, double_visor, ventilasi_udara, berat, wire_lock, predicted_class, confidence_score, k_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssdsdi", 
                    $data['harga'], $data['standar'], $data['kaca'], 
                    $data['double_visor'], $data['ventilasi_udara'], $data['berat'], 
                    $data['wire_lock'], $knn_result['predicted_class'], 
                    $knn_result['confidence'], $k
                );
                $stmt->execute();
            } catch (Exception $e) {
                // Ignore database save errors - prediction still works
            }
        } else {
            $error_message = "KNN prediction failed";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get recent predictions
$recent_predictions = [];
try {
    $result = $conn->query("SELECT * FROM knn_predictions ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        $recent_predictions = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Silent fail for recent predictions
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNN Prediction - Helm Classification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .prediction-result {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .confidence-bar {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background: rgba(255,255,255,0.2);
        }
        .confidence-fill {
            height: 100%;
            background: white;
            transition: width 0.5s ease;
        }
        .input-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .recent-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                        <h2><i class="fas fa-robot me-2"></i>KNN Prediction</h2>
                        <p class="text-muted">Klasifikasi helm berdasarkan kriteria menggunakan algoritma K-Nearest Neighbor</p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Dashboard
                        </a>
                        <a href="knn_data.php" class="btn btn-outline-primary">
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

                <!-- Prediction Result - Full Width -->
                <?php if ($prediction_result): ?>
                <div class="prediction-result mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h3><i class="fas fa-chart-line me-2"></i>Hasil Prediksi</h3>
                            <div class="d-flex align-items-center mb-3">
                                <h1 class="display-4 me-3 mb-0"><?= $prediction_result['prediction'] ?></h1>
                                <div class="flex-grow-1">
                                    <div class="confidence-bar">
                                        <div class="confidence-fill" style="width: <?= $prediction_result['confidence'] * 100 ?>%"></div>
                                    </div>
                                    <small>Confidence: <?= number_format($prediction_result['confidence'] * 100, 1) ?>%</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-summary">
                                <h6 class="text-dark">Data Input & Algoritma:</h6>
                                <div class="row text-dark">
                                    <div class="col-6">
                                        <small><strong>Harga:</strong> Rp <?= number_format($prediction_result['input_data']['harga']) ?></small><br>
                                        <small><strong>Standar:</strong> <?= $prediction_result['input_data']['standar'] ?></small><br>
                                        <small><strong>Kaca:</strong> <?= $prediction_result['input_data']['kaca'] ?></small>
                                    </div>
                                    <div class="col-6">
                                        <small><strong>Double Visor:</strong> <?= $prediction_result['input_data']['double_visor'] ?></small><br>
                                        <small><strong>Ventilasi Udara:</strong> <?= $prediction_result['input_data']['ventilasi_udara'] ?></small><br>
                                        <small><strong>Berat:</strong> <?= $prediction_result['input_data']['berat'] ?> kg</small><br>
                                        <small><strong>Wire Lock:</strong> <?= $prediction_result['input_data']['wire_lock'] ?></small>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-dark">
                                    <small><strong>Algoritma KNN:</strong> K = <?= $prediction_result['k'] ?></small><br>
                                    <small><strong>Distance Metric:</strong> Manhattan Distance</small><br>
                                    <small><strong>Class Votes:</strong> 
                                        <?php 
                                        $votes = $prediction_result['knn_result']['class_votes'];
                                        foreach ($votes as $class => $count) {
                                            echo "$class: $count ";
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- K-Nearest Neighbors & Manhattan Distance - Full Width -->
                <?php if (isset($prediction_result['knn_result']['k_neighbors'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <!-- K-Nearest Neighbors Details -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-users me-2"></i>K-Nearest Neighbors (Top <?= $prediction_result['k'] ?>)</h6>
                            </div>
                            <div class="card-body">
                                <small class="text-muted mb-3 d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Tetangga terdekat berdasarkan Manhattan Distance
                                </small>
                                
                                <div class="row">
                                    <?php foreach ($prediction_result['knn_result']['k_neighbors'] as $index => $neighbor): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="neighbor-card h-100 p-3 border rounded-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid <?= $neighbor['data']['kelas'] == 'Mahal' ? '#dc3545' : '#198754' ?>!important;">
                                            <div class="d-flex align-items-start justify-content-between mb-2">
                                                <span class="badge bg-primary rounded-pill" style="font-size: 12px;">
                                                    #<?= $index + 1 ?>
                                                </span>
                                                <span class="badge bg-<?= $neighbor['data']['kelas'] == 'Mahal' ? 'danger' : 'success' ?>">
                                                    <?= $neighbor['data']['kelas'] ?>
                                                </span>
                                            </div>
                                            <h6 class="text-dark mb-2">
                                                <strong><?= $neighbor['data']['merk'] ?> <?= $neighbor['data']['nama'] ?></strong>
                                            </h6>
                                            <div class="helm-specs mb-2">
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-motorcycle me-1"></i><?= $neighbor['data']['jenis'] ?>
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-weight me-1"></i><?= $neighbor['data']['berat'] ?>kg
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-tag me-1"></i>Rp <?= number_format($neighbor['data']['harga']) ?>
                                                </small>
                                            </div>
                                            <div class="distance-info text-center p-2 rounded" style="background: rgba(108, 117, 125, 0.1);">
                                                <div class="distance-value mb-1">
                                                    <strong style="font-size: 18px; color: #6c757d;">
                                                        <?= number_format($neighbor['distance'], 4) ?>
                                                    </strong>
                                                </div>
                                                <small class="text-muted">Manhattan Distance</small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="voting-result mt-4 p-3 rounded-3" style="background: #e3f2fd;">
                                    <h6 class="text-primary mb-2">
                                        <i class="fas fa-vote-yea me-2"></i>Hasil Voting (Majority Vote)
                                    </h6>
                                    <div class="vote-breakdown">
                                        <?php 
                                        $votes = $prediction_result['knn_result']['class_votes'];
                                        foreach ($votes as $class => $count) {
                                            $percentage = ($count / $prediction_result['k']) * 100;
                                            $width = $percentage;
                                            echo "<div class='mb-2'>";
                                            echo "<div class='d-flex justify-content-between mb-1'>";
                                            echo "<span class='fw-bold'>$class</span>";
                                            echo "<span>$count votes (" . number_format($percentage, 1) . "%)</span>";
                                            echo "</div>";
                                            echo "<div class='progress' style='height: 10px;'>";
                                            echo "<div class='progress-bar bg-" . ($class == 'Mahal' ? 'danger' : 'success') . "' style='width: {$width}%'></div>";
                                            echo "</div>";
                                            echo "</div>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manhattan Distance Calculation - Full Width -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Detail Perhitungan Manhattan Distance</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                $knn_result = $prediction_result['knn_result'];
                                $input_normalized = $knn_result['input_normalized'];
                                $k_neighbors = $knn_result['k_neighbors'];
                                ?>
                                
                                <!-- Formula Section -->
                                <div class="formula-section mb-4 p-3 rounded-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid #17a2b8;">
                                    <h6 class="text-info mb-2">
                                        <i class="fas fa-function me-2"></i>Manhattan Distance Formula
                                    </h6>
                                    <div class="formula-box p-2 rounded" style="background: white; border: 1px dashed #17a2b8;">
                                        <code class="text-dark" style="font-size: 14px;">
                                            Distance = |K1| + |K2| + |K3| + |K4| + |K5| + |K6| + |K7|
                                        </code>
                                    </div>
                                    <small class="text-muted mt-2 d-block">
                                        <strong>Keterangan:</strong> K1=Harga, K2=Standar, K3=Kaca, K4=Double Visor, K5=Ventilasi, K6=Berat, K7=Wire Lock
                                    </small>
                                </div>
                                
                                <!-- Input Data Section -->
                                <div class="input-section mb-4 p-3 rounded-3" style="background: #e7f3ff;">
                                    <h6 class="text-primary mb-2">
                                        <i class="fas fa-input-numeric me-2"></i>Data Input Ternormalisasi
                                    </h6>
                                    <div class="row g-2">
                                        <div class="col-lg-2 col-md-3">
                                            <div class="input-item p-2 rounded text-center" style="background: white;">
                                                <small class="text-muted">K1 (Harga)</small><br>
                                                <strong><?= number_format($input_normalized['harga'], 4) ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-lg-1 col-md-2">
                                            <div class="input-item p-2 rounded text-center" style="background: white;">
                                                <small class="text-muted">K2 (Std)</small><br>
                                                <strong><?= $input_normalized['standar'] ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-lg-1 col-md-2">
                                            <div class="input-item p-2 rounded text-center" style="background: white;">
                                                <small class="text-muted">K3</small><br>
                                                <strong><?= $input_normalized['kaca'] ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-lg-1 col-md-2">
                                            <div class="input-item p-2 rounded text-center" style="background: white;">
                                                <small class="text-muted">K4</small><br>
                                                <strong><?= $input_normalized['double_visor'] ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-lg-1 col-md-2">
                                            <div class="input-item p-2 rounded text-center" style="background: white;">
                                                <small class="text-muted">K5</small><br>
                                                <strong><?= $input_normalized['ventilasi_udara'] ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-3">
                                            <div class="input-item p-2 rounded text-center" style="background: white;">
                                                <small class="text-muted">K6 (Berat)</small><br>
                                                <strong><?= number_format($input_normalized['berat'], 4) ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-lg-1 col-md-2">
                                            <div class="input-item p-2 rounded text-center" style="background: white;">
                                                <small class="text-muted">K7</small><br>
                                                <strong><?= $input_normalized['wire_lock'] ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Calculation Table -->
                                <div class="calculation-table mb-4">
                                    <h6 class="text-dark mb-3">
                                        <i class="fas fa-table me-2"></i>Tabel Perhitungan Detail
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-dark">
                                                <tr class="text-center">
                                                    <th>Rank</th>
                                                    <th>ID</th>
                                                    <th>K1<br><small>Harga</small></th>
                                                    <th>K2<br><small>Standar</small></th>
                                                    <th>K3<br><small>Kaca</small></th>
                                                    <th>K4<br><small>DV</small></th>
                                                    <th>K5<br><small>VU</small></th>
                                                    <th>K6<br><small>Berat</small></th>
                                                    <th>K7<br><small>WL</small></th>
                                                    <th class="bg-warning">Sum</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($k_neighbors as $index => $neighbor): 
                                                    $rank = $index + 1;
                                                    $data = $neighbor['data'];
                                                    $normalized = $neighbor['normalized'];
                                                    
                                                    $k1_diff = abs($input_normalized['harga'] - $normalized['harga']);
                                                    $k2_diff = abs($input_normalized['standar'] - $normalized['standar']);
                                                    $k3_diff = abs($input_normalized['kaca'] - $normalized['kaca']);
                                                    $k4_diff = abs($input_normalized['double_visor'] - $normalized['double_visor']);
                                                    $k5_diff = abs($input_normalized['ventilasi_udara'] - $normalized['ventilasi_udara']);
                                                    $k6_diff = abs($input_normalized['berat'] - $normalized['berat']);
                                                    $k7_diff = abs($input_normalized['wire_lock'] - $normalized['wire_lock']);
                                                    
                                                    $sum = $k1_diff + $k2_diff + $k3_diff + $k4_diff + $k5_diff + $k6_diff + $k7_diff;
                                                    
                                                    $bgColor = $rank == 1 ? 'table-success' : ($rank == 2 ? 'table-info' : ($rank == 3 ? 'table-warning' : ''));
                                                ?>
                                                <tr class="<?= $bgColor ?> text-center">
                                                    <td><strong>#<?= $rank ?></strong></td>
                                                    <td><strong><?= $data['no_data'] ?></strong></td>
                                                    <td><?= number_format($k1_diff, 4) ?></td>
                                                    <td><?= number_format($k2_diff, 2) ?></td>
                                                    <td><?= $k3_diff ?></td>
                                                    <td><?= $k4_diff ?></td>
                                                    <td><?= $k5_diff ?></td>
                                                    <td><?= number_format($k6_diff, 4) ?></td>
                                                    <td><?= $k7_diff ?></td>
                                                    <td class="bg-warning"><strong><?= number_format($sum, 4) ?></strong></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Prediction Form -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cog me-2"></i>Input Data Helm</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Harga (Rp)</label>
                                                <input type="number" class="form-control" name="harga" required 
                                                       placeholder="contoh: 500000" value="<?= $_POST['harga'] ?? '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Berat (kg)</label>
                                                <input type="number" class="form-control" name="berat" step="0.1" required 
                                                       placeholder="contoh: 1.5" value="<?= $_POST['berat'] ?? '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Nilai K (Neighbors)</label>
                                                <select class="form-select" name="k">
                                                    <option value="3" <?= ($_POST['k'] ?? 3) == 3 ? 'selected' : '' ?>>K = 3</option>
                                                    <option value="5" <?= ($_POST['k'] ?? 3) == 5 ? 'selected' : '' ?>>K = 5</option>
                                                    <option value="7" <?= ($_POST['k'] ?? 3) == 7 ? 'selected' : '' ?>>K = 7</option>
                                                    <option value="9" <?= ($_POST['k'] ?? 3) == 9 ? 'selected' : '' ?>>K = 9</option>
                                                </select>
                                                <small class="text-muted">Jumlah tetangga terdekat</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Standar Keamanan</label>
                                                <select class="form-select" name="standar" required>
                                                    <option value="">Pilih Standar</option>
                                                    <option value="SNI" <?= ($_POST['standar'] ?? '') == 'SNI' ? 'selected' : '' ?>>SNI</option>
                                                    <option value="SNI, DOT" <?= ($_POST['standar'] ?? '') == 'SNI, DOT' ? 'selected' : '' ?>>SNI, DOT</option>
                                                    <option value="SNI, DOT, ECE" <?= ($_POST['standar'] ?? '') == 'SNI, DOT, ECE' ? 'selected' : '' ?>>SNI, DOT, ECE</option>
                                                    <option value="SNI, DOT, ECE, SNELL" <?= ($_POST['standar'] ?? '') == 'SNI, DOT, ECE, SNELL' ? 'selected' : '' ?>>SNI, DOT, ECE, SNELL</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Kaca</label>
                                                <select class="form-select" name="kaca" required>
                                                    <option value="">Ada Kaca?</option>
                                                    <option value="Ya" <?= ($_POST['kaca'] ?? '') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                                    <option value="Tidak" <?= ($_POST['kaca'] ?? '') == 'Tidak' ? 'selected' : '' ?>>Tidak</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Double Visor</label>
                                                <select class="form-select" name="double_visor" required>
                                                    <option value="">Ada Double Visor?</option>
                                                    <option value="Ya" <?= ($_POST['double_visor'] ?? '') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                                    <option value="Tidak" <?= ($_POST['double_visor'] ?? '') == 'Tidak' ? 'selected' : '' ?>>Tidak</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Ventilasi Udara</label>
                                                <select class="form-select" name="ventilasi_udara" required>
                                                    <option value="">Ada Ventilasi Udara?</option>
                                                    <option value="Ya" <?= ($_POST['ventilasi_udara'] ?? '') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                                    <option value="Tidak" <?= ($_POST['ventilasi_udara'] ?? '') == 'Tidak' ? 'selected' : '' ?>>Tidak</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Wire Lock</label>
                                                <select class="form-select" name="wire_lock" required>
                                                    <option value="">Ada Wire Lock?</option>
                                                    <option value="Ya" <?= ($_POST['wire_lock'] ?? '') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                                    <option value="Tidak" <?= ($_POST['wire_lock'] ?? '') == 'Tidak' ? 'selected' : '' ?>>Tidak</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" name="predict" value="1" class="btn btn-primary btn-lg" id="predictBtn">
                                            <span id="btnText">
                                                <i class="fas fa-search me-2"></i>Prediksi Kelas Helm
                                            </span>
                                            <span id="btnLoading" style="display: none;">
                                                <i class="fas fa-spinner fa-spin me-2"></i>Memproses...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Recent Predictions -->
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-history me-2"></i>Prediksi Terakhir</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_predictions)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-robot fa-2x mb-2"></i>
                                        <p>Belum ada prediksi</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_predictions as $pred): ?>
                                    <div class="recent-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?= $pred['predicted_class'] == 'Mahal' ? 'danger' : 'success' ?>">
                                                    <?= $pred['predicted_class'] ?>
                                                </span>
                                                <small class="text-muted d-block">
                                                    Rp <?= number_format($pred['harga']) ?>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <?= date('d/m H:i', strtotime($pred['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <div class="d-grid mt-3">
                                    <a href="knn_history.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-list me-2"></i>Lihat Semua
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Results now shown in full width above -->
                        
                        <!-- Info KNN -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6><i class="fas fa-info-circle me-2"></i>Tentang KNN</h6>
                            </div>
                            <div class="card-body">
                                <small class="text-muted">
                                    K-Nearest Neighbor mengklasifikasikan data berdasarkan jarak Manhattan ke K tetangga terdekat dalam training data.
                                </small>
                                <hr>
                                <div class="d-grid">
                                    <a href="../debug_knn.php" class="btn btn-info btn-sm" target="_blank">
                                        <i class="fas fa-flask me-2"></i>Debug KNN
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Temporarily disable dashboard.js to debug -->
    <!-- <script src="../assets/js/dashboard.js"></script> -->
    
    <script>
        console.log('🔥 DEBUGGING FORM SUBMISSION');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            
            const form = document.querySelector('form');
            const button = document.getElementById('predictBtn');
            
            console.log('Form found:', form);
            console.log('Button found:', button);
            
            if (form && button) {
                console.log('Form action:', form.action);
                console.log('Form method:', form.method);
                
                // Add both click and submit handlers
                button.addEventListener('click', function(e) {
                    console.log('🔵 BUTTON CLICKED!');
                    console.log('Button type:', this.type);
                    console.log('Button name:', this.name);
                    console.log('Button value:', this.value);
                });
                
                form.addEventListener('submit', function(e) {
                    console.log('🚀 FORM SUBMIT EVENT!');
                    console.log('Event target:', e.target);
                    console.log('Form data check:');
                    
                    // Check all form fields
                    const formData = new FormData(form);
                    for (let [key, value] of formData.entries()) {
                        console.log(`  ${key}: "${value}"`);
                    }
                    
                    console.log('✅ Letting form submit naturally...');
                    // NO e.preventDefault() - let it submit
                });
                
                // Also test direct form submission
                console.log('Adding manual submit test...');
                window.testFormSubmit = function() {
                    console.log('🧪 MANUAL FORM SUBMIT TEST');
                    form.submit();
                };
                console.log('Type testFormSubmit() in console to test');
                
            } else {
                console.log('❌ Form or button not found!');
                console.log('All forms:', document.querySelectorAll('form'));
                console.log('All buttons:', document.querySelectorAll('button'));
            }
        });
    </script>
</body>
</html>
