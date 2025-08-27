<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
</head>
<body>
    <h1>Session Debug</h1>
    
    <h2>Session ID:</h2>
    <p><?= session_id() ?></p>
    
    <h2>Session Data:</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>Authentication Check:</h2>
    <p>Username: <?= $_SESSION['username'] ?? 'NOT SET' ?></p>
    <p>User ID: <?= $_SESSION['user_id'] ?? 'NOT SET' ?></p>
    <p>User Role: <?= $_SESSION['user_role'] ?? 'NOT SET' ?></p>
    
    <h2>Links:</h2>
    <a href="login.php">Login</a> | 
    <a href="dashboard.php">Dashboard</a> | 
    <a href="knn_prediction.php">KNN Prediction</a>
    
    <?php if (!isset($_SESSION['username'])): ?>
    <h3 style="color: red;">NOT LOGGED IN - Please login first!</h3>
    <?php endif; ?>
</body>
</html>