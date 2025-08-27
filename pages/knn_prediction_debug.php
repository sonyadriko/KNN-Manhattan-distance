<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting debug...<br>";

session_start();
echo "Session started<br>";

try {
    require_once '../config/db.php';
    echo "Database connected<br>";
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "<br>";
}

try {
    require_once '../includes/knn_algorithm.php';
    echo "KNN algorithm included<br>";
} catch (Exception $e) {
    echo "KNN Error: " . $e->getMessage() . "<br>";
}

try {
    require_once '../includes/auth_check.php';
    echo "Auth check included<br>";
} catch (Exception $e) {
    echo "Auth Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Session Info:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

try {
    checkAuth();
    echo "Auth check passed<br>";
} catch (Exception $e) {
    echo "Auth Check Error: " . $e->getMessage() . "<br>";
}

try {
    $knn = new KNNAlgorithm($conn);
    echo "KNN initialized successfully<br>";
} catch (Exception $e) {
    echo "KNN Init Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='knn_prediction.php'>Try KNN Prediction Again</a>";
echo "<br><a href='../debug_session.php'>Check Session</a>";
?>