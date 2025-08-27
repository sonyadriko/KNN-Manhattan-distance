<?php
session_start();
require_once '../config/db.php';

// Simple auth check
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$users = [];
$error_message = null;
$success_message = null;

// Check if current user is admin
$is_admin = false;
try {
    $check_admin = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check_admin->bind_param("i", $_SESSION['user_id']);
    $check_admin->execute();
    $admin_result = $check_admin->get_result();
    if ($admin_row = $admin_result->fetch_assoc()) {
        $is_admin = ($admin_row['role'] === 'admin');
    }
} catch (Exception $e) {
    // Default to non-admin if error
    $is_admin = false;
}

// Redirect if not admin
if (!$is_admin) {
    header("Location: dashboard.php");
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_user') {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $role = $_POST['role'] ?? 'user';
            
            // Validation
            if (empty($username) || empty($password)) {
                throw new Exception("Username dan password tidak boleh kosong");
            }
            
            if ($password !== $confirm_password) {
                throw new Exception("Konfirmasi password tidak cocok");
            }
            
            if (strlen($password) < 6) {
                throw new Exception("Password minimal 6 karakter");
            }
            
            if (!in_array($role, ['admin', 'user'])) {
                throw new Exception("Role tidak valid");
            }
            
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception("Username sudah digunakan");
            }
            
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $success_message = "User berhasil ditambahkan dengan role " . $role;
            } else {
                throw new Exception("Gagal menambahkan user");
            }
            
        } elseif ($action === 'delete_user') {
            $user_id = intval($_POST['user_id']);
            
            // Don't allow deleting current user
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception("Tidak dapat menghapus user yang sedang login");
            }
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $success_message = "User berhasil dihapus";
            } else {
                throw new Exception("Gagal menghapus user");
            }
            
        } elseif ($action === 'reset_password') {
            $user_id = intval($_POST['user_id']);
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_new_password'];
            
            // Validation
            if (empty($new_password)) {
                throw new Exception("Password baru tidak boleh kosong");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("Konfirmasi password tidak cocok");
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception("Password minimal 6 karakter");
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Password berhasil direset";
            } else {
                throw new Exception("Gagal mereset password");
            }
            
        } elseif ($action === 'update_role') {
            $user_id = intval($_POST['user_id']);
            $new_role = $_POST['new_role'];
            
            // Validation
            if (!in_array($new_role, ['admin', 'user'])) {
                throw new Exception("Role tidak valid");
            }
            
            // Don't allow changing own role
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception("Tidak dapat mengubah role sendiri");
            }
            
            // Update role
            $stmt = $conn->prepare("UPDATE users SET role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Role berhasil diupdate menjadi " . $new_role;
            } else {
                throw new Exception("Gagal mengupdate role");
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all users
try {
    $result = $conn->query("SELECT id, username, role, created_at, updated_at FROM users ORDER BY role DESC, created_at DESC");
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Count users by role
    $admin_count = 0;
    $user_count = 0;
    foreach ($users as $user) {
        if ($user['role'] === 'admin') {
            $admin_count++;
        } else {
            $user_count++;
        }
    }
} catch (Exception $e) {
    $error_message = "Error loading users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Helm Classification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .user-card:hover {
            transform: translateY(-2px);
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        .current-user {
            border-left-color: #28a745;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-action {
            margin: 0 0.25rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div id="content" class="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-primary">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content -->
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-users me-2"></i>Kelola User</h2>
                        <p class="text-muted">Manajemen user sistem KNN Helm Classification</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>Tambah User
                        </button>
                    </div>
                </div>

                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="fas fa-chart-bar me-2"></i>Statistik User</h3>
                            <div class="row">
                                <div class="col-md-3">
                                    <h2 class="display-6"><?= count($users) ?></h2>
                                    <p class="mb-0">Total User</p>
                                </div>
                                <div class="col-md-3">
                                    <h2 class="display-6"><?= $admin_count ?></h2>
                                    <p class="mb-0">Admin</p>
                                </div>
                                <div class="col-md-3">
                                    <h2 class="display-6"><?= $user_count ?></h2>
                                    <p class="mb-0">User</p>
                                </div>
                                <div class="col-md-3">
                                    <h2 class="display-6">1</h2>
                                    <p class="mb-0">Online</p>
                                    <small class="text-muted">(<?= $_SESSION['username'] ?>)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-users-cog fa-5x opacity-50"></i>
                        </div>
                    </div>
                </div>

                <!-- Users List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list me-2"></i>Daftar User</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($users)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada user terdaftar</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <div class="user-card <?= $user['id'] == $_SESSION['user_id'] ? 'current-user' : '' ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-1">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <?= htmlspecialchars($user['username']) ?>
                                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?> ms-2">
                                                            <?= ucfirst($user['role']) ?>
                                                        </span>
                                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                            <span class="badge bg-success ms-1">Current User</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Dibuat: <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                                                        <?php if ($user['updated_at'] != $user['created_at']): ?>
                                                            | Diupdate: <?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-5 text-end">
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-sm btn-info btn-action" 
                                                            onclick="showRoleModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= $user['role'] ?>')">
                                                        <i class="fas fa-user-shield"></i> Role
                                                    </button>
                                                    <button class="btn btn-sm btn-warning btn-action" 
                                                            onclick="showResetPasswordModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                        <i class="fas fa-key"></i> Password
                                                    </button>
                                                    <button class="btn btn-sm btn-danger btn-action" 
                                                            onclick="showDeleteModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                <?php else: ?>
                                                    <small class="text-muted">User yang sedang login</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah User Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required maxlength="50" 
                                   placeholder="Masukkan username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required minlength="6" 
                                   placeholder="Minimal 6 karakter">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" class="form-control" name="confirm_password" required 
                                   placeholder="Ulangi password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="user" selected>User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <small class="text-muted">Admin memiliki akses penuh, User hanya akses terbatas</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="reset_user_id">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Anda akan mereset password untuk user: <strong id="reset_username"></strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="new_password" required minlength="6" 
                                   placeholder="Minimal 6 karakter">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" name="confirm_new_password" required 
                                   placeholder="Ulangi password baru">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Role Modal -->
    <div class="modal fade" id="updateRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-user-shield me-2"></i>Update Role</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" id="role_user_id">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Anda akan mengubah role untuk user: <strong id="role_username"></strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role Baru</label>
                            <select class="form-select" name="new_role" id="role_select" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <small class="text-muted">
                                <strong>Admin:</strong> Akses penuh ke semua fitur<br>
                                <strong>User:</strong> Akses terbatas (hanya prediction & history)
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-user-shield me-2"></i>Update Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Hapus User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Peringatan!</strong> Anda yakin ingin menghapus user: <strong id="delete_username"></strong>?
                            <br><br>
                            <small>Tindakan ini tidak dapat dibatalkan.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Ya, Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    
    <script>
        function showRoleModal(userId, username, currentRole) {
            document.getElementById('role_user_id').value = userId;
            document.getElementById('role_username').textContent = username;
            document.getElementById('role_select').value = currentRole;
            new bootstrap.Modal(document.getElementById('updateRoleModal')).show();
        }

        function showResetPasswordModal(userId, username) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').textContent = username;
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }

        function showDeleteModal(userId, username) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_username').textContent = username;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Add User form validation
            const addUserForm = document.querySelector('#addUserModal form');
            addUserForm.addEventListener('submit', function(e) {
                const password = this.querySelector('input[name="password"]').value;
                const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Konfirmasi password tidak cocok!');
                    return false;
                }
            });

            // Reset Password form validation
            const resetPasswordForm = document.querySelector('#resetPasswordModal form');
            resetPasswordForm.addEventListener('submit', function(e) {
                const newPassword = this.querySelector('input[name="new_password"]').value;
                const confirmPassword = this.querySelector('input[name="confirm_new_password"]').value;
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Konfirmasi password tidak cocok!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>