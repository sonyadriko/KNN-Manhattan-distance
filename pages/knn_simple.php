<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNN Data - Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>KNN Training Data - Simple Version</h2>
        
        <?php
        // Test database connection
        try {
            require_once '../config/db.php';
            echo "<div class='alert alert-success'>✅ Database connected successfully</div>";
            
            // Get data
            $sql = "SELECT * FROM knn_training_data ORDER BY no_data";
            $result = $conn->query($sql);
            
            if ($result) {
                echo "<div class='alert alert-info'>Found " . $result->num_rows . " records</div>";
                
                echo "<table class='table table-striped'>";
                echo "<thead><tr><th>No</th><th>Merk</th><th>Nama</th><th>Jenis</th><th>Harga</th><th>Kelas</th></tr></thead>";
                echo "<tbody>";
                
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['no_data'] . "</td>";
                    echo "<td>" . $row['merk'] . "</td>";
                    echo "<td>" . $row['nama'] . "</td>";
                    echo "<td>" . $row['jenis'] . "</td>";
                    echo "<td>Rp " . number_format($row['harga']) . "</td>";
                    echo "<td><span class='badge bg-" . ($row['kelas'] == 'Mahal' ? 'danger' : 'success') . "'>" . $row['kelas'] . "</span></td>";
                    echo "</tr>";
                }
                
                echo "</tbody></table>";
            } else {
                echo "<div class='alert alert-danger'>❌ Query failed: " . $conn->error . "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        <a href="../test_knn.php" class="btn btn-info">System Test</a>
    </div>
</body>
</html>