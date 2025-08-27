<?php
echo "=== PATH DEBUGGING ===\n";
echo "Current working directory: " . getcwd() . "\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "dirname(__FILE__): " . dirname(__FILE__) . "\n";

echo "\n=== CHECKING PATHS ===\n";
$db_path = __DIR__ . '/config/db.php';
echo "DB Path: $db_path\n";
echo "DB exists: " . (file_exists($db_path) ? 'YES' : 'NO') . "\n";

$knn_path = __DIR__ . '/includes/knn_algorithm.php';
echo "KNN Path: $knn_path\n";
echo "KNN exists: " . (file_exists($knn_path) ? 'YES' : 'NO') . "\n";

$auth_path = __DIR__ . '/includes/auth_check.php';
echo "Auth Path: $auth_path\n";
echo "Auth exists: " . (file_exists($auth_path) ? 'YES' : 'NO') . "\n";

echo "\n=== SESSION CHECK ===\n";
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session vars: " . print_r($_SESSION, true) . "\n";
?>