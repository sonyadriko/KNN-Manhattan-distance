<?php
session_start();
require_once '../config/db.php';
require_once '../includes/knn_algorithm.php';

// Simple auth check
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$prediction_result = null;
$error_message = null;

// Debug POST data
echo "<!-- DEBUG: POST = " . json_encode($_POST) . " -->";

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predict'])) {
    echo "<!-- DEBUG: Form was submitted -->";
    
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
    
    echo "<!-- DEBUG: Processed data = " . json_encode($data) . " -->";
    
    try {
        // Run KNN Algorithm
        $knn = new KNNAlgorithm($conn);
        $knn_result = $knn->predict($data, $k);
        
        if ($knn_result) {
            $prediction_result = [
                'prediction' => $knn_result['predicted_class'],
                'confidence' => $knn_result['confidence'],
                'input_data' => $data,
                'knn_result' => $knn_result,
                'k' => $k
            ];
            echo "<!-- DEBUG: KNN prediction successful -->";
            
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
                echo "<!-- DEBUG: Saved to database -->";
            } catch (Exception $e) {
                echo "<!-- DEBUG: Database save failed: " . $e->getMessage() . " -->";
            }
        } else {
            $error_message = "KNN prediction failed";
            echo "<!-- DEBUG: KNN prediction failed -->";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        echo "<!-- DEBUG: Exception: " . $e->getMessage() . " -->";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNN Prediction - Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-robot me-2"></i>KNN Prediction - Simple Version</h1>
        <p class="text-muted">Versi sederhana untuk debug masalah</p>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
        </div>
        <?php endif; ?>

        <?php if ($prediction_result): ?>
        <div class="prediction-result">
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
                    <div class="bg-white text-dark p-3 rounded">
                        <h6>Data Input:</h6>
                        <small><strong>Harga:</strong> Rp <?= number_format($prediction_result['input_data']['harga']) ?></small><br>
                        <small><strong>Standar:</strong> <?= $prediction_result['input_data']['standar'] ?></small><br>
                        <small><strong>Kaca:</strong> <?= $prediction_result['input_data']['kaca'] ?></small><br>
                        <small><strong>Double Visor:</strong> <?= $prediction_result['input_data']['double_visor'] ?></small><br>
                        <small><strong>Ventilasi:</strong> <?= $prediction_result['input_data']['ventilasi_udara'] ?></small><br>
                        <small><strong>Berat:</strong> <?= $prediction_result['input_data']['berat'] ?> kg</small><br>
                        <small><strong>Wire Lock:</strong> <?= $prediction_result['input_data']['wire_lock'] ?></small>
                        <hr>
                        <small><strong>K Value:</strong> <?= $prediction_result['k'] ?></small><br>
                        <small><strong>Distance:</strong> Manhattan Distance</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- K-Nearest Neighbors Detail -->
        <?php if (isset($prediction_result['knn_result']['k_neighbors'])): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-ruler me-2"></i>K-Nearest Neighbors (Top <?= $prediction_result['k'] ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($prediction_result['knn_result']['k_neighbors'] as $index => $neighbor): ?>
                        <div class="mb-2 p-2 border rounded" style="background: #f8f9fa;">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong class="text-primary">#<?= $index + 1 ?></strong>
                                    <span><?= $neighbor['data']['merk'] ?> <?= $neighbor['data']['nama'] ?></span>
                                </div>
                                <div>
                                    <span class="badge bg-<?= $neighbor['data']['kelas'] == 'Mahal' ? 'danger' : 'success' ?>">
                                        <?= $neighbor['data']['kelas'] ?>
                                    </span>
                                </div>
                            </div>
                            <small class="text-muted">
                                Distance: <?= number_format($neighbor['distance'], 4) ?> | 
                                Rp <?= number_format($neighbor['data']['harga']) ?> | 
                                <?= $neighbor['data']['berat'] ?>kg
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-calculator me-2"></i>Manhattan Distance Detail</h6>
                    </div>
                    <div class="card-body">
                        <?php 
                        $input_norm = $prediction_result['knn_result']['input_normalized'];
                        ?>
                        <small><strong>Input Normalized:</strong></small><br>
                        <small class="text-muted">
                            K1(Harga): <?= number_format($input_norm['harga'], 4) ?><br>
                            K2(Standar): <?= $input_norm['standar'] ?><br>
                            K3(Kaca): <?= $input_norm['kaca'] ?><br>
                            K4(DV): <?= $input_norm['double_visor'] ?><br>
                            K5(VU): <?= $input_norm['ventilasi_udara'] ?><br>
                            K6(Berat): <?= number_format($input_norm['berat'], 4) ?><br>
                            K7(WL): <?= $input_norm['wire_lock'] ?>
                        </small>
                        
                        <hr>
                        <small><strong>Formula:</strong><br>
                        Distance = |K1| + |K2| + |K3| + |K4| + |K5| + |K6| + |K7|
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-cog me-2"></i>Input Data Helm</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Harga (Rp)</label>
                                <input type="number" class="form-control" name="harga" required 
                                       value="<?= $_POST['harga'] ?? '400000' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Berat (kg)</label>
                                <input type="number" class="form-control" name="berat" step="0.1" required 
                                       value="<?= $_POST['berat'] ?? '1.3' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">K Value</label>
                                <select class="form-select" name="k">
                                    <option value="3" <?= ($_POST['k'] ?? 3) == 3 ? 'selected' : '' ?>>K = 3</option>
                                    <option value="5" <?= ($_POST['k'] ?? 3) == 5 ? 'selected' : '' ?>>K = 5</option>
                                    <option value="7" <?= ($_POST['k'] ?? 3) == 7 ? 'selected' : '' ?>>K = 7</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Standar Keamanan</label>
                                <select class="form-select" name="standar" required>
                                    <option value="SNI" <?= ($_POST['standar'] ?? '') == 'SNI' ? 'selected' : '' ?>>SNI</option>
                                    <option value="SNI, DOT" <?= ($_POST['standar'] ?? '') == 'SNI, DOT' ? 'selected' : '' ?>>SNI, DOT</option>
                                    <option value="SNI, DOT, ECE" <?= ($_POST['standar'] ?? '') == 'SNI, DOT, ECE' ? 'selected' : '' ?>>SNI, DOT, ECE</option>
                                    <option value="SNI, DOT, ECE, SNELL" <?= ($_POST['standar'] ?? 'SNI, DOT, ECE, SNELL') == 'SNI, DOT, ECE, SNELL' ? 'selected' : '' ?>>SNI, DOT, ECE, SNELL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kaca</label>
                                <select class="form-select" name="kaca" required>
                                    <option value="Ya" <?= ($_POST['kaca'] ?? 'Ya') == 'Ya' ? 'selected' : '' ?>>Ya</option>
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
                                    <option value="Ya" <?= ($_POST['double_visor'] ?? 'Ya') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                    <option value="Tidak" <?= ($_POST['double_visor'] ?? '') == 'Tidak' ? 'selected' : '' ?>>Tidak</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Ventilasi Udara</label>
                                <select class="form-select" name="ventilasi_udara" required>
                                    <option value="Ya" <?= ($_POST['ventilasi_udara'] ?? 'Ya') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                    <option value="Tidak" <?= ($_POST['ventilasi_udara'] ?? '') == 'Tidak' ? 'selected' : '' ?>>Tidak</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Wire Lock</label>
                                <select class="form-select" name="wire_lock" required>
                                    <option value="Ya" <?= ($_POST['wire_lock'] ?? 'Ya') == 'Ya' ? 'selected' : '' ?>>Ya</option>
                                    <option value="Tidak" <?= ($_POST['wire_lock'] ?? '') == 'Tidak' ? 'selected' : '' ?>>Tidak</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="predict" value="1" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Prediksi Kelas Helm
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="knn_prediction.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Original
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>