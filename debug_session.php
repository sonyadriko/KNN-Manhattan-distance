<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Session</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <div class="info">
        <h3>Current Session Data:</h3>
        <pre><?= print_r($_SESSION, true) ?></pre>
    </div>
    
    <div class="info">
        <h3>Session Checks:</h3>
        <p><strong>Session Started:</strong> <?= session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No' ?></p>
        <p><strong>Username Set:</strong> <?= isset($_SESSION['username']) ? 'Yes (' . $_SESSION['username'] . ')' : 'No' ?></p>
        <p><strong>User ID Set:</strong> <?= isset($_SESSION['user_id']) ? 'Yes (' . $_SESSION['user_id'] . ')' : 'No' ?></p>
        <p><strong>User Role Set:</strong> <?= isset($_SESSION['user_role']) ? 'Yes (' . $_SESSION['user_role'] . ')' : 'No' ?></p>
    </div>
    
    <div class="info">
        <h3>Database User Check:</h3>
        <?php
        if (isset($_SESSION['user_id'])) {
            require_once 'config/db.php';
            $user_id = $_SESSION['user_id'];
            $query = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
            $query->bind_param("i", $user_id);
            $query->execute();
            $result = $query->get_result();
            if ($user = $result->fetch_assoc()) {
                echo "<p><strong>DB Username:</strong> " . $user['username'] . "</p>";
                echo "<p><strong>DB Role:</strong> " . $user['role'] . "</p>";
                
                // Fix session role if missing
                if (!isset($_SESSION['user_role'])) {
                    $_SESSION['user_role'] = $user['role'];
                    echo "<p style='color: green;'><strong>Fixed session role to:</strong> " . $user['role'] . "</p>";
                }
            } else {
                echo "<p style='color: red;'>User not found in database!</p>";
            }
        } else {
            echo "<p>No user_id in session to check</p>";
        }
        ?>
    </div>
    
    <a href="pages/knn_prediction.php">Go to KNN Prediction</a> |
    <a href="pages/dashboard.php">Go to Dashboard</a> |
    <a href="pages/logout.php">Logout</a>
</body>
</html>