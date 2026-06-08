<?php
// admin/sub-admins.php
// Create and Manage Sub Admins (Super Admin Only)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is Super Admin
if (!isSuperAdmin()) {
    redirect('admin/');
}

$db = Database::getConnection();
$error = '';
$success = '';

// Handle creating new sub admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_sub_admin'])) {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email address.';
    } elseif (!validatePhone($phone)) {
        $error = 'Phone must be in format +250XXXXXXXXX';
    } elseif (!validatePasswordStrength($password)) {
        $error = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.';
    } elseif (emailExists($email)) {
        $error = 'Email already registered.';
    } elseif (phoneExists($phone)) {
        $error = 'Phone number already registered.';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new sub admin (removed created_by column since it doesn't exist)
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, phone, password_hash, role, is_verified) 
            VALUES (?, ?, ?, ?, 'sub_admin', 1)
        ");
        if ($stmt->execute([$full_name, $email, $phone, $password_hash])) {
            $success = 'Sub Admin created successfully. They can now login.';
        } else {
            $error = 'Failed to create Sub Admin.';
        }
    }
}

// Handle deleting sub admin
if (isset($_GET['delete'])) {
    $sub_id = intval($_GET['delete']);
    // Prevent deleting self
    if ($sub_id != getCurrentUserId()) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'sub_admin'");
        $stmt->execute([$sub_id]);
        $success = 'Sub Admin deleted.';
    }
    redirect('admin/sub-admins.php');
}

// Get all sub admins (removed created_by join since column doesn't exist)
$stmt = $db->prepare("
    SELECT id, full_name, email, phone, role, is_verified, created_at 
    FROM users 
    WHERE role = 'sub_admin'
    ORDER BY created_at DESC
");
$stmt->execute();
$sub_admins = $stmt->fetchAll();

$page_title = 'Manage Sub Admins';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        
        :root {
            --orange: #FF6B35;
            --blue: #1A5F7A;
            --dark: #2C3E50;
            --shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        /* Admin Layout */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--dark) 0%, #1a252f 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 25px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            font-size: 1.2rem;
            margin-top: 10px;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link.active {
            background: var(--orange);
            color: white;
        }
        
        .nav-link i {
            width: 20px;
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 25px;
        }
        
        .top-bar {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }
        
        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn-create {
            background: var(--orange);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 80px;
            }
            .sidebar-header h3,
            .sidebar-header p,
            .nav-link span {
                display: none;
            }
            .admin-main {
                margin-left: 80px;
            }
            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <i class="fas fa-crown" style="font-size: 28px; color: var(--orange);"></i>
                <h3>Admin Panel</h3>
                <p style="font-size: 11px; opacity: 0.7;">Super Admin</p>
            </div>
            <div class="sidebar-nav">
                <div class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin/" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin/courses.php" class="nav-link">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin/users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin/sub-admins.php" class="nav-link active">
                        <i class="fas fa-user-shield"></i>
                        <span>Sub Admins</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin/messages.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin/payments.php" class="nav-link">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin/reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin/settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="top-bar">
                <div class="page-title">
                    <h1><i class="fas fa-user-shield"></i> Manage Sub Admins</h1>
                    <p style="color: #666; font-size: 13px; margin-top: 5px;">Create and manage sub administrator accounts</p>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>dashboard/" class="btn-create" style="background: var(--blue); padding: 8px 16px; text-decoration: none;">Back to Site</a>
                </div>
            </div>
            
            <!-- Create Sub Admin Form -->
            <div class="section">
                <h3>Create New Sub Admin</h3>
                <p style="color: #666; font-size: 13px; margin-bottom: 20px;">Sub Admins can manage courses and users, but cannot create other admins.</p>
                
                <?php if ($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="e.g., John Doe">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required placeholder="john@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" required placeholder="+250XXXXXXXXX">
                        <div class="info-text">Include country code (+250 for Rwanda)</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Temporary Password *</label>
                        <input type="password" name="password" required>
                        <div class="info-text">Min 8 chars, include uppercase, lowercase, number, special character</div>
                    </div>
                    
                    <button type="submit" name="create_sub_admin" class="btn-create">
                        <i class="fas fa-plus-circle"></i> Create Sub Admin
                    </button>
                </form>
            </div>
            
            <!-- Sub Admins List -->
            <div class="section">
                <h3>Existing Sub Admins (<?php echo count($sub_admins); ?>)</h3>
                
                <?php if (count($sub_admins) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Phone</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sub_admins as $sub): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['email']); ?></a></td>
                                    <td><?php echo htmlspecialchars($sub['phone']); ?></a></td>
                                    <td><?php echo formatDate($sub['created_at']); ?></a></td>
                                    <td>
                                        <?php if ($sub['id'] != getCurrentUserId()): ?>
                                            <a href="?delete=<?php echo $sub['id']; ?>" class="btn-delete" onclick="return confirm('Delete this Sub Admin?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">(You)</span>
                                        <?php endif; ?>
                                    </a>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #666;">No Sub Admins created yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>