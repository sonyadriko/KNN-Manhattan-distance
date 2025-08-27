<?php
session_start();
require_once '../config/db.php';

echo "<h2>Debug KNN Prediction</h2>";

// Check if table exists
echo "<h3>1. Check Tables:</h3>";
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
    echo "- " . $row[0] . "<br>";
}

// Check if knn_predictions table exists
if (in_array('knn_predictions', $tables)) {
    echo "<div style='color: green;'>✅ Table knn_predictions exists</div>";
    
    echo "<h3>2. Table Structure:</h3>";
    $result = $conn->query("DESCRIBE knn_predictions");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3. Test Data Count:</h3>";
    $result = $conn->query("SELECT COUNT(*) as count FROM knn_predictions");
    $count = $result->fetch_assoc()['count'];
    echo "Records in knn_predictions: " . $count . "<br>";
    
} else {
    echo "<div style='color: red;'>❌ Table knn_predictions does not exist</div>";
    echo "<h3>Creating table...</h3>";
    
    $create_sql = "CREATE TABLE `knn_predictions` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `harga` INT NOT NULL,
        `standar` VARCHAR(100) NOT NULL,
        `kaca` ENUM('Ya', 'No') NOT NULL,
        `double_visor` ENUM('Ya', 'No') NOT NULL,
        `berat` DECIMAL(3,1) NOT NULL,
        `wire_lock` ENUM('Ya', 'No') NOT NULL,
        `predicted_class` ENUM('Mahal', 'Murah') NOT NULL,
        `confidence_score` DECIMAL(5,4),
        `k_value` INT DEFAULT 3,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    if ($conn->query($create_sql)) {
        echo "<div style='color: green;'>✅ Table created successfully</div>";
    } else {
        echo "<div style='color: red;'>❌ Error creating table: " . $conn->error . "</div>";
    }
}

echo "<h3>4. Test Form Data Processing:</h3>";
if ($_POST) {
    echo "<h4>POST Data Received:</h4>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $data = [
        'harga' => intval($_POST['harga']),
        'standar' => $_POST['standar'],
        'kaca' => $_POST['kaca'],
        'double_visor' => $_POST['double_visor'],
        'berat' => floatval($_POST['berat']),
        'wire_lock' => $_POST['wire_lock']
    ];
    
    // Validate and convert form data to match database ENUM values
    $data['kaca'] = ($data['kaca'] === 'Tidak') ? 'No' : 'Ya';
    $data['double_visor'] = ($data['double_visor'] === 'Tidak') ? 'No' : 'Ya';
    $data['wire_lock'] = ($data['wire_lock'] === 'Tidak') ? 'No' : 'Ya';
    
    echo "<h4>Processed Data:</h4>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    // Simple prediction
    $predicted_class = ($data['harga'] > 500000) ? 'Mahal' : 'Murah';
    $confidence = 0.85;
    
    echo "<h4>Prediction Result:</h4>";
    echo "Predicted Class: <strong>$predicted_class</strong><br>";
    echo "Confidence: <strong>" . ($confidence * 100) . "%</strong><br>";
    
    // Test database insert
    try {
        $stmt = $conn->prepare("INSERT INTO knn_predictions (harga, standar, kaca, double_visor, berat, wire_lock, predicted_class, confidence_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("issssdsd", 
                $data['harga'], 
                $data['standar'], 
                $data['kaca'], 
                $data['double_visor'], 
                $data['berat'], 
                $data['wire_lock'], 
                $predicted_class, 
                $confidence
            );
            
            if ($stmt->execute()) {
                echo "<div style='color: green;'>✅ Data saved successfully! Insert ID: " . $conn->insert_id . "</div>";
            } else {
                echo "<div style='color: red;'>❌ Execute error: " . $stmt->error . "</div>";
            }
        } else {
            echo "<div style='color: red;'>❌ Prepare error: " . $conn->error . "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Exception: " . $e->getMessage() . "</div>";
    }
}
?>

<h3>5. Test Form:</h3>
<form method="POST" style="border: 1px solid #ccc; padding: 20px; max-width: 400px;">
    <div style="margin-bottom: 10px;">
        <label>Harga:</label><br>
        <input type="number" name="harga" value="600000" required style="width: 100%; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Standar:</label><br>
        <select name="standar" required style="width: 100%; padding: 5px;">
            <option value="SNI">SNI</option>
            <option value="SNI, DOT" selected>SNI, DOT</option>
            <option value="SNI, DOT, ECE">SNI, DOT, ECE</option>
        </select>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Kaca:</label><br>
        <select name="kaca" required style="width: 100%; padding: 5px;">
            <option value="Ya" selected>Ya</option>
            <option value="Tidak">Tidak</option>
        </select>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Double Visor:</label><br>
        <select name="double_visor" required style="width: 100%; padding: 5px;">
            <option value="Ya">Ya</option>
            <option value="Tidak" selected>Tidak</option>
        </select>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Berat:</label><br>
        <input type="number" step="0.1" name="berat" value="1.5" required style="width: 100%; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Wire Lock:</label><br>
        <select name="wire_lock" required style="width: 100%; padding: 5px;">
            <option value="Ya">Ya</option>
            <option value="Tidak" selected>Tidak</option>
        </select>
    </div>
    
    <button type="submit" name="predict" style="background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer;">
        Test Prediction
    </button>
</form>

<br><a href="knn_prediction.php">← Back to KNN Prediction</a>