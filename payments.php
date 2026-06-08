<?php
// admin/payments.php
// Manage Payments and Transactions

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard/');
}

$db = Database::getConnection();
$is_super_admin = isSuperAdmin();

// Handle payment status update
if ($is_super_admin && isset($_POST['update_status'])) {
    $payment_id = intval($_POST['payment_id']);
    $new_status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE payments SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $payment_id]);
    $success = "Payment status updated.";
}

// Handle refund
if ($is_super_admin && isset($_GET['refund'])) {
    $payment_id = intval($_GET['refund']);
    $stmt = $db->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
    $stmt->execute([$payment_id]);
    redirect('admin/payments.php');
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$sql = "
    SELECT p.*, u.full_name, u.email, c.title as course_title
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    WHERE 1=1
";

if ($status_filter != 'all') {
    $sql .= " AND p.status = '" . $status_filter . "'";
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute();
$payments = $stmt->fetchAll();

// Calculate totals
$total_revenue = 0;
$pending_amount = 0;
$completed_amount = 0;

foreach ($payments as $payment) {
    if ($payment['status'] == 'completed') {
        $total_revenue += $payment['amount'];
        $completed_amount += $payment['amount'];
    } elseif ($payment['status'] == 'pending') {
        $pending_amount += $payment['amount'];
    }
}

$page_title = 'Payments Management';
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
        }
        
        .admin-sidebar .nav li a:hover, .admin-sidebar .nav li a.active {
            background: var(--orange);
        }
        
        .admin-main {
            flex: 1;
            background: #f5f6fa;
            padding: 25px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            font-size: 28px;
            margin: 0;
            color: var(--orange);
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
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
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-refunded {
            background: #e2e3e5;
            color: #383d41;
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
        
        .btn-refund {
            background: #f39c12;
            color: white;
        }
        
        select.status-select {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
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
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-container">
        <div class="admin-sidebar">
            <ul class="nav">
                <li><a href="<?php echo SITE_URL; ?>admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/courses.php"><i class="fas fa-book"></i> Courses</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/sub-admins.php"><i class="fas fa-user-shield"></i> Sub Admins</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/payments.php" class="active"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <h1><i class="fas fa-credit-card"></i> Payments Management</h1>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo number_format($total_revenue); ?> RWF</h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format($completed_amount); ?> RWF</h3>
                    <p>Completed Payments</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format($pending_amount); ?> RWF</h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($payments); ?></h3>
                    <p>Total Transactions</p>
                </div>
            </div>
            
            <div class="section">
                <div class="filter-bar">
                    <a href="?status=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=completed" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
                    <a href="?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=failed" class="filter-btn <?php echo $status_filter == 'failed' ? 'active' : ''; ?>">Failed</a>
                    <a href="?status=refunded" class="filter-btn <?php echo $status_filter == 'refunded' ? 'active' : ''; ?>">Refunded</a>
                </div>
                
                <?php if (count($payments) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Course</th>
                                <th>Amount (RWF)</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['course_title']); ?></td>
                                    <td><?php echo number_format($payment['amount']); ?> RWF</td>
                                    <td>
                                        <?php if ($payment['payment_method'] == 'momo'): ?>
                                            <i class="fas fa-mobile-alt"></i> Mobile Money
                                        <?php else: ?>
                                            <i class="fas fa-credit-card"></i> Credit Card
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $payment['status']; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($payment['created_at']); ?></td>
                                    <td>
                                        <?php if ($is_super_admin): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                <select name="status" class="status-select" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $payment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="completed" <?php echo $payment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="failed" <?php echo $payment['status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                    <option value="refunded" <?php echo $payment['status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            <?php if ($payment['status'] == 'completed'): ?>
                                                <a href="?refund=<?php echo $payment['id']; ?>" class="btn-sm btn-refund" onclick="return confirm('Refund this payment?')">Refund</a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="btn-sm" style="background: #ccc;">View Only</span>
                                        <?php endif; ?>
                                    </td>
                                </table>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 40px;">No payments found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>