<?php
// Authentication and role check functions

function checkAuth() {
    if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    // Auto-fix role if missing (for users logged in before role system)
    if (!isset($_SESSION['user_role'])) {
        require_once '../config/db.php';
        global $conn;
        
        $user_id = $_SESSION['user_id'];
        $query = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $result = $query->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $_SESSION['user_role'] = $user['role'];
        } else {
            // If user not found, logout
            session_destroy();
            header("Location: login.php");
            exit();
        }
    }
}

function checkAdminRole() {
    checkAuth();
    
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getUserRole() {
    return $_SESSION['user_role'] ?? 'user';
}

function hasAccess($requiredRole = 'user') {
    $userRole = getUserRole();
    
    if ($requiredRole === 'admin') {
        return $userRole === 'admin';
    }
    
    // Both admin and user have access to 'user' level pages
    return in_array($userRole, ['admin', 'user']);
}
?>