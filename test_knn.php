<?php
session_start();
require_once 'config/db.php';

// Test database connection
echo "Testing database connection...<br>";
if ($conn) {
    echo "✅ Database connected successfully<br>";
    
    // Test query
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "✅ Database queries working<br>";
        echo "Tables in database:<br>";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
    } else {
        echo "❌ Database query failed: " . $conn->error . "<br>";
    }
} else {
    echo "❌ Database connection failed<br>";
}

// Test session
echo "<br>Session status: ";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "✅ Active<br>";
} else {
    echo "❌ Inactive<br>";
}

echo "Session ID: " . session_id() . "<br>";
echo "Session variables:<br>";
print_r($_SESSION);

phpinfo(INFO_MODULES);
?>