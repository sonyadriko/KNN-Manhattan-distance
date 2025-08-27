<?php
// Get current page name to set active menu
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure session is started and user exists
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'Guest';
}

// Check user role
$user_role = $_SESSION['user_role'] ?? 'user';
$is_admin = ($user_role === 'admin');
?>

<!-- Sidebar -->
<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-motorcycle me-2"></i>
            <span>KNN Helm</span>
        </div>
    </div>
    
    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                <span class="user-role"><?php echo ucfirst($user_role); ?></span>
            </div>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <?php if ($is_admin): ?>
        <li class="nav-item">
            <a href="knn_data.php" class="nav-link <?php echo ($current_page == 'knn_data.php') ? 'active' : ''; ?>">
                <i class="fas fa-database"></i>
                <span>Data Training KNN</span>
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a href="knn_prediction.php" class="nav-link <?php echo ($current_page == 'knn_prediction.php') ? 'active' : ''; ?>">
                <i class="fas fa-robot"></i>
                <span>KNN Prediction</span>
            </a>
        </li>
        <!-- <li class="nav-item">
            <a href="knn_history.php" class="nav-link <?php echo ($current_page == 'knn_history.php') ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>History Prediksi</span>
            </a>
        </li> -->
        <?php if ($is_admin): ?>
        <li class="nav-item">
            <a href="knn_evaluation.php" class="nav-link <?php echo ($current_page == 'knn_evaluation.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Evaluasi Kinerja KNN</span>
            </a>
        </li>
        <li class="nav-divider"></li>
        <li class="nav-header">
            <span>Sistem</span>
        </li>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Kelola User</span>
            </a>
        </li>
        <!-- <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Pengaturan</span>
            </a>
        </li> -->
        <?php endif; ?>
        <li class="nav-item mt-auto">
            <a href="logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>