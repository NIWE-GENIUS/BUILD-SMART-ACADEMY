<?php
// admin/users.php
// Manage All Users (Super Admin Only for sensitive actions)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard/');
}

$db = Database::getConnection();
$is_super_admin = isSuperAdmin();

// Handle user role change (Super Admin only)
if ($is_super_admin && isset($_POST['change_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = $_POST['new_role'];
    
    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $user_id]);
    $success = "User role updated successfully.";
}

// Handle user status toggle (verify/unverify)
if ($is_super_admin && isset($_GET['toggle_verify'])) {
    $user_id = intval($_GET['toggle_verify']);
    $stmt = $db->prepare("UPDATE users SET is_verified = NOT is_verified WHERE id = ?");
    $stmt->execute([$user_id]);
    redirect('admin/users.php');
}

// Handle user deletion (Super Admin only)
if ($is_super_admin && isset($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    // Don't allow deleting self
    if ($user_id != getCurrentUserId()) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $success = "User deleted successfully.";
    }
    redirect('admin/users.php');
}

// Get all users with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM enrollments WHERE user_id = u.id) as courses_enrolled,
           (SELECT COUNT(*) FROM certificates WHERE user_id = u.id) as certificates_earned
    FROM users u
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$users = $stmt->fetchAll();

// Get total count for pagination
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$total_users = $stmt->fetch()['total'];
$total_pages = ceil($total_users / $limit);

$page_title = 'Manage Users';
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
        .admin-container {
            display: flex;
            min-height: calc(100vh - 150px);
        }
        
        .admin-sidebar {
            width: 260px;
            background: #2C3E50;
            color: white;
            flex-shrink: 0;
        }
        
        .admin-sidebar .nav {
            list-style: none;
            padding: 0;
        }
        
        .admin-sidebar .nav li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .admin-sidebar .nav li a:hover, .admin-sidebar .nav li a.active {
            background: var(--orange);
        }
        
        .admin-sidebar .nav li a i {
            width: 20px;
        }
        
        .admin-main {
            flex: 1;
            background: #f5f6fa;
            padding: 25px;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
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
        
        .table tr:hover {
            background: #f9f9f9;
        }
        
        .role-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        
        .role-super_admin {
            background: #e74c3c;
            color: white;
        }
        
        .role-sub_admin {
            background: #3498db;
            color: white;
        }
        
        .role-user {
            background: #95a5a6;
            color: white;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .status-verified {
            background: #d4edda;
            color: #155724;
        }
        
        .status-unverified {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-sm {
            padding: 4px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            margin: 2px;
            display: inline-block;
        }
        
        .btn-edit {
            background: var(--blue);
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 15px;
            background: white;
            border-radius: 5px;
            text-decoration: none;
            color: var(--dark);
        }
        
        .pagination .active {
            background: var(--orange);
            color: white;
        }
        
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border-radius: 25px;
            text-decoration: none;
            background: #f0f0f0;
            color: #666;
        }
        
        .filter-btn.active {
            background: var(--orange);
            color: white;
        }
        
        .search-box {
            margin-left: auto;
        }
        
        .search-box input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 250px;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            .admin-sidebar {
                width: 100%;
            }
            .table {
                display: block;
                overflow-x: auto;
            }
            .filter-bar {
                flex-direction: column;
            }
            .search-box {
                margin-left: 0;
            }
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <ul class="nav">
                <li><a href="<?php echo SITE_URL; ?>admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/courses.php"><i class="fas fa-book"></i> Courses</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/add-course.php"><i class="fas fa-plus-circle"></i> Add Course</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <?php if ($is_super_admin): ?>
                    <li><a href="<?php echo SITE_URL; ?>admin/sub-admins.php"><i class="fas fa-user-shield"></i> Sub Admins</a></li>
                <?php endif; ?>
                <li><a href="<?php echo SITE_URL; ?>admin/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <h1><i class="fas fa-users"></i> Manage Users</h1>
            <p>Total registered users: <?php echo $total_users; ?></p>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="section">
                <div class="filter-bar">
                    <a href="?role=all" class="filter-btn <?php echo (!isset($_GET['role']) || $_GET['role'] == 'all') ? 'active' : ''; ?>">All Users</a>
                    <a href="?role=user" class="filter-btn <?php echo (isset($_GET['role']) && $_GET['role'] == 'user') ? 'active' : ''; ?>">Students</a>
                    <?php if ($is_super_admin): ?>
                        <a href="?role=sub_admin" class="filter-btn <?php echo (isset($_GET['role']) && $_GET['role'] == 'sub_admin') ? 'active' : ''; ?>">Sub Admins</a>
                    <?php endif; ?>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search by name or email...">
                    </div>
                </div>
                
                <table class="table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Courses</th>
                            <th>Certificates</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                    <?php if ($user['lifetime_free']): ?>
                                        <span style="background: #F39C12; color: #2C3E50; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">🎁 Free</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['is_verified'] ? 'verified' : 'unverified'; ?>">
                                        <?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?>
                                    </span>
                                </td>
                                <td><?php echo $user['courses_enrolled']; ?></td>
                                <td><?php echo $user['certificates_earned']; ?></td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <?php if ($is_super_admin): ?>
                                        <?php if ($user['role'] !== 'super_admin'): ?>
                                            <button onclick="showRoleModal(<?php echo $user['id']; ?>, '<?php echo $user['full_name']; ?>', '<?php echo $user['role']; ?>')" 
                                                    class="btn-sm btn-edit"><i class="fas fa-user-tag"></i> Role</button>
                                            <a href="?toggle_verify=<?php echo $user['id']; ?>" class="btn-sm btn-warning">
                                                <?php echo $user['is_verified'] ? 'Unverify' : 'Verify'; ?>
                                            </a>
                                            <a href="?delete_user=<?php echo $user['id']; ?>" class="btn-sm btn-danger" 
                                               onclick="return confirm('Delete this user? All data will be lost.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="btn-sm" style="background: #ccc;">View Only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Role Change Modal -->
    <div id="roleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 10px; padding: 30px; max-width: 400px; margin: 20px;">
            <h3>Change User Role</h3>
            <p>User: <span id="modalUserName"></span></p>
            <form method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="change_role" value="1">
                <div class="form-group" style="margin: 20px 0;">
                    <label>New Role</label>
                    <select name="new_role" id="modalUserRole" class="form-control" style="width: 100%; padding: 10px;">
                        <option value="user">User (Student)</option>
                        <?php if ($is_super_admin): ?>
                            <option value="sub_admin">Sub Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Update Role</button>
                <button type="button" onclick="closeModal()" class="btn-secondary">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function showRoleModal(userId, userName, currentRole) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUserName').textContent = userName;
            const roleSelect = document.getElementById('modalUserRole');
            if (currentRole === 'sub_admin') {
                roleSelect.value = 'sub_admin';
            } else {
                roleSelect.value = 'user';
            }
            document.getElementById('roleModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('roleModal').style.display = 'none';
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1]?.textContent.toLowerCase() || '';
                const email = row.cells[2]?.textContent.toLowerCase() || '';
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>