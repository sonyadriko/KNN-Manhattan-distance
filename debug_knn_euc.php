<?php
session_start();
require_once 'config/db.php';
require_once 'includes/knn_algorithm.php';

echo "<h2>🔍 KNN Debug Test</h2>";

// Check database connection
if ($conn) {
    echo "<p>✅ Database connected</p>";
} else {
    echo "<p>❌ Database connection failed</p>";
    exit;
}

// Check if training data table exists and has data
$result = $conn->query("SELECT COUNT(*) as count FROM knn_training_data");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "<p>✅ Training data table exists with {$count} records</p>";
    
    if ($count == 0) {
        echo "<p>❌ No training data found!</p>";
        echo "<p>Please import database.sql first</p>";
        exit;
    }
} else {
    echo "<p>❌ Training data table not found: " . $conn->error . "</p>";
    exit;
}

// Test KNN Algorithm
echo "<h3>🧮 Testing KNN Algorithm</h3>";

try {
    $knn = new KNNAlgorithm($conn);
    echo "<p>✅ KNN class initialized</p>";
    
    // Test with sample data (YOUR DATA)
    $test_data = [
        'harga' => 400000,
        'standar' => 'SNI, DOT, ECE, SNELL',
        'kaca' => 'Ya',
        'double_visor' => 'Ya',
        'ventilasi_udara' => 'Ya',
        'berat' => 1.3,
        'wire_lock' => 'Ya'
    ];
    
    echo "<h4>Test Input:</h4>";
    echo "<pre>";
    print_r($test_data);
    echo "</pre>";
    
    $result = $knn->predict($test_data, 3);
    
    if ($result) {
        echo "<p>✅ KNN prediction successful!</p>";
        
        echo "<h4>🔢 Data Normalisasi Input:</h4>";
        echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Feature</th><th>Original Value</th><th>Normalized Value</th><th>Encoding Method</th></tr>";
        
        $normalized_input = $result['input_normalized'];
        echo "<tr><td>Harga</td><td>Rp " . number_format($test_data['harga']) . "</td><td>" . number_format($normalized_input['harga'], 4) . "</td><td>Simple Ratio (Value/Max)</td></tr>";
        echo "<tr><td>Standar</td><td>{$test_data['standar']}</td><td>" . $normalized_input['standar'] . "</td><td>Safety Level Encoding (1-4)</td></tr>";
        echo "<tr><td>Kaca</td><td>{$test_data['kaca']}</td><td>" . $normalized_input['kaca'] . "</td><td>Binary Encoding (Ya=1, No=0)</td></tr>";
        echo "<tr><td>Double Visor</td><td>{$test_data['double_visor']}</td><td>" . $normalized_input['double_visor'] . "</td><td>Binary Encoding (Ya=1, No=0)</td></tr>";
        echo "<tr><td>Ventilasi Udara</td><td>{$test_data['ventilasi_udara']}</td><td>" . $normalized_input['ventilasi_udara'] . "</td><td>Binary Encoding (Ya=1, No=0)</td></tr>";
        echo "<tr><td>Berat</td><td>{$test_data['berat']} kg</td><td>" . number_format($normalized_input['berat'], 4) . "</td><td>Simple Ratio (Value/Max)</td></tr>";
        echo "<tr><td>Wire Lock</td><td>{$test_data['wire_lock']}</td><td>" . $normalized_input['wire_lock'] . "</td><td>Binary Encoding (Ya=1, No=0)</td></tr>";
        echo "</table>";
        echo "</div>";

        echo "<h4>🎯 Prediction Result:</h4>";
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Predicted Class:</strong> " . $result['predicted_class'] . "<br>";
        echo "<strong>Confidence:</strong> " . number_format($result['confidence'] * 100, 1) . "%<br>";
        echo "<strong>K Value:</strong> 3<br>";
        echo "</div>";
        
        echo "<h4>📊 Class Votes:</h4>";
        foreach ($result['class_votes'] as $class => $votes) {
            $percentage = ($votes / 3) * 100;
            echo "<span style='background: " . ($class == 'Mahal' ? '#dc3545' : '#198754') . "; color: white; padding: 5px 10px; border-radius: 3px; margin: 5px;'>$class: $votes ($percentage%)</span>";
        }
        
        echo "<h4>🎯 K-Nearest Neighbors:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Rank</th><th>Helm</th><th>Class</th><th>Euclidean Distance</th><th>Original Data</th><th>Normalized Data</th></tr>";
        
        foreach ($result['k_neighbors'] as $index => $neighbor) {
            $rank = $index + 1;
            $data = $neighbor['data'];
            $normalized = $neighbor['normalized'];
            $distance = number_format($neighbor['distance'], 4);
            
            echo "<tr>";
            echo "<td><strong>#{$rank}</strong></td>";
            echo "<td>{$data['merk']} {$data['nama']}<br><small>{$data['jenis']}</small></td>";
            echo "<td style='color: " . ($data['kelas'] == 'Mahal' ? 'red' : 'green') . "; font-weight: bold;'>{$data['kelas']}</td>";
            echo "<td style='font-family: monospace; font-weight: bold;'>{$distance}</td>";
            echo "<td style='font-size: 12px;'>";
            echo "<strong>Harga:</strong> Rp " . number_format($data['harga']) . "<br>";
            echo "<strong>Standar:</strong> {$data['standar']}<br>";
            echo "<strong>Kaca:</strong> {$data['kaca']} | <strong>DV:</strong> {$data['double_visor']} | <strong>VU:</strong> {$data['ventilasi_udara']} | <strong>WL:</strong> {$data['wire_lock']}<br>";
            echo "<strong>Berat:</strong> {$data['berat']}kg";
            echo "</td>";
            echo "<td style='font-size: 11px; font-family: monospace;'>";
            echo "<strong>H:</strong> " . number_format($normalized['harga'], 3) . "<br>";
            echo "<strong>S:</strong> " . $normalized['standar'] . "<br>";
            echo "<strong>K:</strong> " . $normalized['kaca'] . " | <strong>DV:</strong> " . $normalized['double_visor'] . " | <strong>VU:</strong> " . $normalized['ventilasi_udara'] . " | <strong>WL:</strong> " . $normalized['wire_lock'] . "<br>";
            echo "<strong>B:</strong> " . number_format($normalized['berat'], 3);
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h4>📊 Training Data Normalisasi (Semua Data):</h4>";
        echo "<div style='background: #fff8e1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 11px;'>";
        echo "<tr style='background: #f39c12; color: white;'>";
        echo "<th>No</th><th>Helm</th><th>Harga Asli</th><th>Harga Norm</th><th>Standar Asli</th><th>Standar Enc</th>";
        echo "<th>Kaca</th><th>DV</th><th>VU</th><th>Berat Asli</th><th>Berat Norm</th><th>WL</th><th>Kelas</th></tr>";
        
        // Get training data stats for max values
        $stats = $knn->getTrainingDataStats();
        $max_harga = $stats['features']['harga']['max'];
        $max_berat = $stats['features']['berat']['max'];
        
        // Show all training data with normalization
        foreach ($result['all_distances'] as $index => $item) {
            $data = $item['data'];
            $normalized = $item['normalized'];
            $bgColor = ($index < 3) ? "background: #fff3cd;" : "";
            
            echo "<tr style='$bgColor'>";
            echo "<td>{$data['no_data']}</td>";
            echo "<td style='font-size: 10px;'>{$data['merk']} {$data['nama']}<br><small>{$data['jenis']}</small></td>";
            echo "<td>Rp " . number_format($data['harga']) . "</td>";
            echo "<td>" . number_format($normalized['harga'], 4) . "</td>";
            echo "<td style='font-size: 9px;'>{$data['standar']}</td>";
            echo "<td>{$normalized['standar']}</td>";
            echo "<td>{$data['kaca']}({$normalized['kaca']})</td>";
            echo "<td>{$data['double_visor']}({$normalized['double_visor']})</td>";
            echo "<td>{$data['ventilasi_udara']}({$normalized['ventilasi_udara']})</td>";
            echo "<td>{$data['berat']} kg</td>";
            echo "<td>" . number_format($normalized['berat'], 4) . "</td>";
            echo "<td>{$data['wire_lock']}({$normalized['wire_lock']})</td>";
            echo "<td style='color: " . ($data['kelas'] == 'Mahal' ? 'red' : 'green') . "; font-weight: bold;'>{$data['kelas']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='font-size: 12px; color: #666;'><strong>Keterangan:</strong> Normalisasi menggunakan Simple Ratio (Value/Max). Max Harga: Rp" . number_format($max_harga) . ", Max Berat: {$max_berat}kg</p>";
        echo "</div>";
        
        echo "<h4>📈 All Training Data Distances (Ranking):</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Rank</th><th>Helm</th><th>Class</th><th>Euclidean Distance</th><th>Used for K=3</th></tr>";
        
        foreach (array_slice($result['all_distances'], 0, 10) as $index => $item) {
            $rank = $index + 1;
            $data = $item['data'];
            $distance = number_format($item['distance'], 4);
            
            echo "<tr" . ($index < 3 ? " style='background: #fff3cd;'" : "") . ">";
            echo "<td>#{$rank}</td>";
            echo "<td>{$data['merk']} {$data['nama']}</td>";
            echo "<td style='color: " . ($data['kelas'] == 'Mahal' ? 'red' : 'green') . ";'><strong>{$data['kelas']}</strong></td>";
            echo "<td>{$distance}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h4>🔢 Distance Calculation Table:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>No</th><th>K1</th><th>K6</th><th>K3</th><th>K4</th><th>K5</th><th>K7</th><th>K2</th><th>sum</th><th>Rank</th>";
        echo "</tr>";
        
        foreach (array_slice($result['all_distances'], 0, 10) as $index => $item) {
            $rank = $index + 1;
            $data = $item['data'];
            $normalized = $item['normalized'];
            $input_norm = $result['input_normalized'];
            
            // Calculate individual feature differences squared
            $k1_diff = pow($input_norm['harga'] - $normalized['harga'], 2);
            $k6_diff = pow($input_norm['berat'] - $normalized['berat'], 2);
            $k3_diff = pow($input_norm['kaca'] - $normalized['kaca'], 2);
            $k4_diff = pow($input_norm['double_visor'] - $normalized['double_visor'], 2);
            $k5_diff = pow($input_norm['ventilasi_udara'] - $normalized['ventilasi_udara'], 2);
            $k7_diff = pow($input_norm['wire_lock'] - $normalized['wire_lock'], 2);
            $k2_diff = pow($input_norm['standar'] - $normalized['standar'], 2);
            
            $sum = $k1_diff + $k6_diff + $k3_diff + $k4_diff + $k5_diff + $k7_diff + $k2_diff;
            
            echo "<tr" . ($index < 3 ? " style='background: #fff3cd;'" : "") . ">";
            echo "<td>{$data['no_data']}</td>";
            echo "<td>" . number_format($k1_diff, 4) . "</td>";
            echo "<td>" . number_format($k6_diff, 0) . "</td>";
            echo "<td>" . number_format($k3_diff, 0) . "</td>";
            echo "<td>" . number_format($k4_diff, 0) . "</td>";
            echo "<td>" . number_format($k5_diff, 0) . "</td>";
            echo "<td>" . number_format($k7_diff, 4) . "</td>";
            echo "<td>" . number_format($k2_diff, 0) . "</td>";
            echo "<td>" . number_format($sum, 4) . "</td>";
            echo "<td>$rank</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='font-size: 11px; color: #666;'><strong>Keterangan:</strong> K1=Harga², K2=Standar², K3=Kaca², K4=Double Visor², K5=Ventilasi², K6=Berat², K7=Wire Lock² (selisih kuadrat dari input vs training)</p>";
        
        echo "<h4>🔍 Debug: Berat Calculation Detail (ID 63):</h4>";
        $input_norm = $result['input_normalized'];
        echo "<div style='background: #e7f3ff; padding: 10px; font-family: monospace; font-size: 12px;'>";
        echo "<strong>Input Berat:</strong> {$test_data['berat']} kg<br>";
        echo "<strong>Input Berat Normalized:</strong> " . number_format($input_norm['berat'], 10) . "<br><br>";
        
        // Find ID 63 specifically
        $id63_item = null;
        foreach ($result['all_distances'] as $item) {
            if ($item['data']['no_data'] == 63) {
                $id63_item = $item;
                break;
            }
        }
        
        if ($id63_item) {
            $data63 = $id63_item['data'];
            $norm63 = $id63_item['normalized'];
            $stats = $knn->getTrainingDataStats();
            $max_berat = $stats['features']['berat']['max'];
            
            echo "<strong>ID 63 Berat:</strong> {$data63['berat']} kg<br>";
            echo "<strong>ID 63 Berat Normalized:</strong> " . number_format($norm63['berat'], 10) . "<br>";
            echo "<strong>Max Berat:</strong> {$max_berat} kg<br>";
            echo "<strong>Manual Calc (63):</strong> " . number_format($data63['berat'] / $max_berat, 10) . "<br><br>";
            
            $diff = $input_norm['berat'] - $norm63['berat'];
            $diff_squared = pow($diff, 2);
            
            echo "<strong>Difference:</strong> " . number_format($diff, 15) . "<br>";
            echo "<strong>Difference²:</strong> " . number_format($diff_squared, 15) . "<br>";
            echo "<strong>Should be 0?:</strong> " . (abs($diff) < 0.0000000001 ? "✅ YES" : "❌ NO") . "<br>";
        }
        echo "</div>";
        
        echo "<h4>🔍 Debug: All Berat Values:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 11px;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>No</th><th>Berat Asli</th><th>Berat Norm</th><th>Input Norm</th><th>Diff</th><th>Diff²</th>";
        echo "</tr>";
        
        foreach (array_slice($result['all_distances'], 0, 10) as $index => $item) {
            $data = $item['data'];
            $normalized = $item['normalized'];
            $diff = $input_norm['berat'] - $normalized['berat'];
            $diff_squared = pow($diff, 2);
            $highlight = ($data['no_data'] == 63) ? "background: #ffeb3b;" : "";
            
            echo "<tr style='$highlight'>";
            echo "<td>{$data['no_data']}</td>";
            echo "<td>{$data['berat']} kg</td>";
            echo "<td>" . number_format($normalized['berat'], 10) . "</td>";
            echo "<td>" . number_format($input_norm['berat'], 10) . "</td>";
            echo "<td>" . number_format($diff, 15) . "</td>";
            echo "<td>" . number_format($diff_squared, 15) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>❌ KNN prediction failed!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($e);
    echo "</pre>";
}

echo "<h3>🔧 Test Form Processing</h3>";

if ($_POST) {
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
    echo "<h4>✅ Form Data Received:</h4>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['predict'])) {
        echo "<p><strong>✅ Predict button was clicked!</strong></p>";
        
        // Process form data
        $form_data = [
            'harga' => intval($_POST['harga']),
            'standar' => $_POST['standar'],
            'kaca' => ($_POST['kaca'] === 'Tidak') ? 'No' : 'Ya',
            'double_visor' => ($_POST['double_visor'] === 'Tidak') ? 'No' : 'Ya',
            'ventilasi_udara' => ($_POST['ventilasi_udara'] === 'Tidak') ? 'No' : 'Ya',
            'berat' => floatval($_POST['berat']),
            'wire_lock' => ($_POST['wire_lock'] === 'Tidak') ? 'No' : 'Ya'
        ];
        
        $k = isset($_POST['k']) ? intval($_POST['k']) : 3;
        
        echo "<h4>Processed Form Data:</h4>";
        echo "<pre>";
        print_r($form_data);
        echo "</pre>";
        echo "<p><strong>K Value:</strong> $k</p>";
        
        try {
            $form_result = $knn->predict($form_data, $k);
            
            if ($form_result) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4>🎉 Form KNN Result:</h4>";
                echo "<strong>Predicted Class:</strong> " . $form_result['predicted_class'] . "<br>";
                echo "<strong>Confidence:</strong> " . number_format($form_result['confidence'] * 100, 1) . "%<br>";
                echo "<strong>K Value:</strong> $k<br>";
                echo "</div>";
                
                echo "<h4>📊 Class Votes (Form Result):</h4>";
                foreach ($form_result['class_votes'] as $class => $votes) {
                    $percentage = ($votes / $k) * 100;
                    echo "<span style='background: " . ($class == 'Mahal' ? '#dc3545' : '#198754') . "; color: white; padding: 5px 10px; border-radius: 3px; margin: 5px;'>$class: $votes (" . number_format($percentage, 1) . "%)</span>";
                }
                
                echo "<h4>🎯 K-Nearest Neighbors (Form Input):</h4>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
                echo "<tr style='background: #f8f9fa;'><th>Rank</th><th>Helm</th><th>Jenis</th><th>Class</th><th>Euclidean Distance</th><th>Harga</th><th>Specs</th></tr>";
                
                foreach ($form_result['k_neighbors'] as $index => $neighbor) {
                    $rank = $index + 1;
                    $data = $neighbor['data'];
                    $distance = number_format($neighbor['distance'], 4);
                    $bgColor = ($rank <= 3) ? "background: #fff3cd;" : "";
                    
                    echo "<tr style='$bgColor'>";
                    echo "<td><strong>#{$rank}</strong></td>";
                    echo "<td>{$data['merk']} {$data['nama']}</td>";
                    echo "<td>{$data['jenis']}</td>";
                    echo "<td style='color: " . ($data['kelas'] == 'Mahal' ? 'red' : 'green') . "; font-weight: bold;'>{$data['kelas']}</td>";
                    echo "<td style='font-family: monospace; font-weight: bold;'>{$distance}</td>";
                    echo "<td>Rp " . number_format($data['harga']) . "</td>";
                    echo "<td>{$data['berat']}kg | {$data['standar']} | DV: {$data['double_visor']} | VU: {$data['ventilasi_udara']} | WL: {$data['wire_lock']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<h4>📊 Training Data Normalisasi (Form Result):</h4>";
                echo "<div style='background: #fff8e1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 11px;'>";
                echo "<tr style='background: #f39c12; color: white;'>";
                echo "<th>No</th><th>Helm</th><th>Harga Asli</th><th>Harga Norm</th><th>Standar Asli</th><th>Standar Enc</th>";
                echo "<th>Kaca</th><th>DV</th><th>VU</th><th>Berat Asli</th><th>Berat Norm</th><th>WL</th><th>Kelas</th></tr>";
                
                // Get stats for max values
                $stats = $knn->getTrainingDataStats();
                $max_harga = $stats['features']['harga']['max'];
                $max_berat = $stats['features']['berat']['max'];
                
                // Show all training data with normalization
                foreach ($form_result['all_distances'] as $index => $item) {
                    $data = $item['data'];
                    $normalized = $item['normalized'];
                    $isUsed = $index < $k;
                    $bgColor = $isUsed ? "background: #fff3cd;" : "";
                    
                    echo "<tr style='$bgColor'>";
                    echo "<td>{$data['no_data']}</td>";
                    echo "<td style='font-size: 10px;'>{$data['merk']} {$data['nama']}<br><small>{$data['jenis']}</small></td>";
                    echo "<td>Rp " . number_format($data['harga']) . "</td>";
                    echo "<td>" . number_format($normalized['harga'], 4) . "</td>";
                    echo "<td style='font-size: 9px;'>{$data['standar']}</td>";
                    echo "<td>{$normalized['standar']}</td>";
                    echo "<td>{$data['kaca']}({$normalized['kaca']})</td>";
                    echo "<td>{$data['double_visor']}({$normalized['double_visor']})</td>";
                    echo "<td>{$data['ventilasi_udara']}({$normalized['ventilasi_udara']})</td>";
                    echo "<td>{$data['berat']} kg</td>";
                    echo "<td>" . number_format($normalized['berat'], 4) . "</td>";
                    echo "<td>{$data['wire_lock']}({$normalized['wire_lock']})</td>";
                    echo "<td style='color: " . ($data['kelas'] == 'Mahal' ? 'red' : 'green') . "; font-weight: bold;'>{$data['kelas']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<p style='font-size: 12px; color: #666;'><strong>Keterangan:</strong> Yellow background = K={$k} nearest neighbors. Max Harga: Rp" . number_format($max_harga) . ", Max Berat: {$max_berat}kg</p>";
                echo "</div>";
                
                echo "<h4>📈 All Training Data Distances (Sorted):</h4>";
                echo "<p><small><em>Yellow background = K nearest neighbors yang digunakan untuk voting</em></small></p>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background: #f8f9fa;'><th>Rank</th><th>Helm</th><th>Class</th><th>Distance</th><th>Used for Voting</th></tr>";
                
                foreach ($form_result['all_distances'] as $index => $item) {
                    $rank = $index + 1;
                    $data = $item['data'];
                    $distance = number_format($item['distance'], 4);
                    $isUsed = $index < $k;
                    $bgColor = $isUsed ? "background: #fff3cd;" : "";
                    
                    echo "<tr style='$bgColor'>";
                    echo "<td>#{$rank}</td>";
                    echo "<td>{$data['merk']} {$data['nama']}</td>";
                    echo "<td style='color: " . ($data['kelas'] == 'Mahal' ? 'red' : 'green') . ";'><strong>{$data['kelas']}</strong></td>";
                    echo "<td style='font-family: monospace;'>{$distance}</td>";
                    echo "<td>" . ($isUsed ? '✅ Yes' : '❌ No') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<h4>🧮 Algorithm Details & Distance Calculation:</h4>";
                echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
                echo "<strong>Euclidean Distance Formula:</strong><br>";
                echo "Distance = √[(H₁-H₂)² + (S₁-S₂)² + (K₁-K₂)² + (DV₁-DV₂)² + (VU₁-VU₂)² + (B₁-B₂)² + (WL₁-WL₂)²]<br><br>";
                
                echo "<strong>Your Normalized Input Vector:</strong><br>";
                $normalized_input = $form_result['input_normalized'];
                echo "Input = [" . number_format($normalized_input['harga'], 3) . ", " . $normalized_input['standar'] . ", " . $normalized_input['kaca'] . ", " . $normalized_input['double_visor'] . ", " . $normalized_input['ventilasi_udara'] . ", " . number_format($normalized_input['berat'], 3) . ", " . $normalized_input['wire_lock'] . "]<br><br>";
                
                echo "<strong>Example Distance Calculation (to #1 neighbor):</strong><br>";
                if (!empty($form_result['k_neighbors'])) {
                    $first_neighbor = $form_result['k_neighbors'][0];
                    $neighbor_norm = $first_neighbor['normalized'];
                    echo "Distance to {$first_neighbor['data']['merk']} {$first_neighbor['data']['nama']}:<br>";
                    echo "= √[(" . number_format($normalized_input['harga'], 3) . "-" . number_format($neighbor_norm['harga'], 3) . ")² + (" . $normalized_input['standar'] . "-" . $neighbor_norm['standar'] . ")² + (" . $normalized_input['kaca'] . "-" . $neighbor_norm['kaca'] . ")² + (" . $normalized_input['double_visor'] . "-" . $neighbor_norm['double_visor'] . ")² + (" . $normalized_input['ventilasi_udara'] . "-" . $neighbor_norm['ventilasi_udara'] . ")² + (" . number_format($normalized_input['berat'], 3) . "-" . number_format($neighbor_norm['berat'], 3) . ")² + (" . $normalized_input['wire_lock'] . "-" . $neighbor_norm['wire_lock'] . ")²]<br>";
                    echo "= " . number_format($first_neighbor['distance'], 4) . "<br><br>";
                }
                
                echo "<strong>Features Used:</strong> 6 features dengan normalisasi lengkap<br>";
                echo "<strong>Normalization:</strong> Simple Ratio (Value/Max) untuk Harga & Berat, Level encoding (1-4) untuk Standar, Binary (0-1) untuk fitur Ya/No<br>";
                echo "<strong>Classification:</strong> Majority vote dari K={$k} tetangga terdekat<br>";
                echo "</div>";
                
            } else {
                echo "<p>❌ Form KNN prediction failed!</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Form Error: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
}

?>

<h3>🧪 Test Form</h3>
<form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
        <div>
            <label><strong>Harga:</strong></label><br>
            <input type="number" name="harga" value="400000" required style="width: 100%; padding: 5px;">
        </div>
        
        <div>
            <label><strong>Berat:</strong></label><br>
            <input type="number" step="0.1" name="berat" value="1.3" required style="width: 100%; padding: 5px;">
        </div>
        
        <div>
            <label><strong>Standar:</strong></label><br>
            <select name="standar" required style="width: 100%; padding: 5px;">
                <option value="SNI, DOT">SNI, DOT</option>
                <option value="SNI">SNI</option>
                <option value="SNI, DOT, ECE">SNI, DOT, ECE</option>
                <option value="SNI, DOT, ECE, SNELL" selected>SNI, DOT, ECE, SNELL</option>
            </select>
        </div>
        
        <div>
            <label><strong>K Value:</strong></label><br>
            <select name="k" style="width: 100%; padding: 5px;">
                <option value="3" selected>K = 3</option>
                <option value="5">K = 5</option>
                <option value="7">K = 7</option>
            </select>
        </div>
        
        <div>
            <label><strong>Kaca:</strong></label><br>
            <select name="kaca" required style="width: 100%; padding: 5px;">
                <option value="Ya" selected>Ya</option>
                <option value="Tidak">Tidak</option>
            </select>
        </div>
        
        <div>
            <label><strong>Double Visor:</strong></label><br>
            <select name="double_visor" required style="width: 100%; padding: 5px;">
                <option value="Ya" selected>Ya</option>
                <option value="Tidak">Tidak</option>
            </select>
        </div>
        
        <div>
            <label><strong>Ventilasi Udara:</strong></label><br>
            <select name="ventilasi_udara" required style="width: 100%; padding: 5px;">
                <option value="Ya" selected>Ya</option>
                <option value="Tidak">Tidak</option>
            </select>
        </div>
        
        <div>
            <label><strong>Wire Lock:</strong></label><br>
            <select name="wire_lock" required style="width: 100%; padding: 5px;">
                <option value="Ya" selected>Ya</option>
                <option value="Tidak">Tidak</option>
            </select>
        </div>
    </div>
    
    <div style="margin-top: 20px; text-align: center;">
        <button type="submit" name="predict" style="background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            🎯 Test KNN Prediction
        </button>
    </div>
</form>

<div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
    <h4>📋 Instructions:</h4>
    <ol>
        <li>This page tests the KNN algorithm independently</li>
        <li>If KNN works here, the issue is in the main form</li>
        <li>If KNN fails here, the issue is in the algorithm</li>
        <li>Check database connection and training data</li>
    </ol>
    
    <p><strong>Next:</strong> <a href="pages/knn_prediction.php">← Back to Main Prediction Page</a></p>
</div>