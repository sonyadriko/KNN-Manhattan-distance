<?php
session_start();

echo "<h2>Test Form Processing</h2>";

if ($_POST) {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
    echo "<h3>✅ Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['predict'])) {
        echo "<p><strong>✅ Predict button was clicked!</strong></p>";
        
        $data = [
            'harga' => intval($_POST['harga']),
            'standar' => $_POST['standar'],
            'kaca' => $_POST['kaca'],
            'double_visor' => $_POST['double_visor'],
            'berat' => floatval($_POST['berat']),
            'wire_lock' => $_POST['wire_lock']
        ];
        
        // Convert data
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
        
        echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3>🎯 Prediction Result:</h3>";
        echo "<h2>Class: " . $predicted_class . "</h2>";
        echo "<p>Confidence: " . ($confidence * 100) . "%</p>";
        echo "<p>Based on price: Rp " . number_format($data['harga']) . "</p>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p>No form data received yet.</p>";
}
?>

<h3>Simple Test Form:</h3>
<form method="POST" style="max-width: 500px; border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label><strong>Harga (Rp):</strong></label><br>
        <input type="number" name="harga" value="600000" required style="width: 100%; padding: 8px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Standar Keamanan:</strong></label><br>
        <select name="standar" required style="width: 100%; padding: 8px;">
            <option value="SNI">SNI</option>
            <option value="SNI, DOT" selected>SNI, DOT</option>
            <option value="SNI, DOT, ECE">SNI, DOT, ECE</option>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Ada Kaca:</strong></label><br>
        <select name="kaca" required style="width: 100%; padding: 8px;">
            <option value="Ya" selected>Ya</option>
            <option value="Tidak">Tidak</option>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Double Visor:</strong></label><br>
        <select name="double_visor" required style="width: 100%; padding: 8px;">
            <option value="Ya">Ya</option>
            <option value="Tidak" selected>Tidak</option>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Berat (kg):</strong></label><br>
        <input type="number" step="0.1" name="berat" value="1.5" required style="width: 100%; padding: 8px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Wire Lock:</strong></label><br>
        <select name="wire_lock" required style="width: 100%; padding: 8px;">
            <option value="Ya">Ya</option>
            <option value="Tidak" selected>Tidak</option>
        </select>
    </div>
    
    <div style="margin-top: 20px;">
        <button type="submit" name="predict" style="background: #007bff; color: white; padding: 12px 24px; border: none; cursor: pointer; border-radius: 5px; width: 100%; font-size: 16px;">
            🎯 Test Prediction
        </button>
    </div>
</form>

<div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
    <h4>Debug Instructions:</h4>
    <ol>
        <li>Fill out the form and click "Test Prediction"</li>
        <li>Check if form data is received correctly</li>
        <li>Verify data conversion works (Tidak → No)</li>
        <li>See if prediction logic works</li>
    </ol>
    
    <p><a href="knn_prediction.php">← Back to Main Prediction Page</a></p>
</div>