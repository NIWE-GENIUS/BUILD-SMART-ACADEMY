<?php
// admin/finance/transactions.php
// Manage All Transactions

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdmin()) {
    redirect('dashboard/');
}

$db = Database::getConnection();
$is_super_admin = isSuperAdmin();

// Handle transaction status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $payment_id = intval($_POST['payment_id']);
    $new_status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE payments SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $payment_id]);
    $success = "Transaction status updated.";
}

// Handle refund
if (isset($_GET['refund']) && $is_super_admin) {
    $payment_id = intval($_GET['refund']);
    $stmt = $db->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
    $stmt->execute([$payment_id]);
    redirect('admin/finance/transactions.php');
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$sql = "
    SELECT p.*, u.full_name, u.email, c.title as course_title
    FROM payments p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN courses c ON p.course_id = c.id
    WHERE DATE(p.created_at) BETWEEN ? AND ?
";
$params = [$date_from, $date_to];

if ($status_filter != 'all') {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR p.transaction_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get totals
$stmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_completed,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
        SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as total_failed,
        COUNT(*) as total_transactions
    FROM payments
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$date_from, $date_to]);
$totals = $stmt->fetch();

$page_title = 'Transactions';
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        :root { --orange: #FF6B35; --blue: #1A5F7A; --dark: #2C3E50; --shadow: 0 2px 8px rgba(0,0,0,0.05); }
        
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--dark) 0%, #1a252f 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header { padding: 25px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { padding: 20px 0; }
        .nav-item { margin-bottom: 5px; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active { background: var(--orange); color: white; }
        .admin-main { flex: 1; margin-left: 280px; padding: 25px; }
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
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 12px; margin-bottom: 5px; color: #666; }
        .filter-group input, .filter-group select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; }
        .section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow);
        }
        .stat-card h3 { font-size: 28px; margin: 0; color: var(--orange); }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-refunded { background: #e2e3e5; color: #383d41; }
        .btn-sm { padding: 4px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; }
        .btn-primary { background: var(--orange); color: white; }
        @media (max-width: 768px) {
            .admin-sidebar { width: 80px; }
            .sidebar-header h3, .nav-link span { display: none; }
            .admin-main { margin-left: 80px; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <i class="fas fa-chart-line" style="font-size: 28px; color: var(--orange);"></i>
                <h3>Finance</h3>
            </div>
            <div class="sidebar-nav">
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/transactions.php" class="nav-link active"><i class="fas fa-exchange-alt"></i> Transactions</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/expenses.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Expenses</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/invoices.php" class="nav-link"><i class="fas fa-file-invoice"></i> Invoices</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/payouts.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Payouts</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a></div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="top-bar">
                <h1><i class="fas fa-exchange-alt"></i> Transactions</h1>
                <a href="<?php echo SITE_URL; ?>admin/" class="btn-sm btn-primary">Back to Admin</a>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card"><h3><?php echo number_format($totals['total_completed'] ?? 0); ?> RWF</h3><p>Completed</p></div>
                <div class="stat-card"><h3><?php echo number_format($totals['total_pending'] ?? 0); ?> RWF</h3><p>Pending</p></div>
                <div class="stat-card"><h3><?php echo number_format($totals['total_failed'] ?? 0); ?> RWF</h3><p>Failed</p></div>
                <div class="stat-card"><h3><?php echo $totals['total_transactions'] ?? 0; ?></h3><p>Transactions</p></div>
            </div>
            
            <!-- Filter Bar -->
            <form method="GET" class="filter-bar">
                <div class="filter-group"><label>From Date</label><input type="date" name="date_from" value="<?php echo $date_from; ?>"></div>
                <div class="filter-group"><label>To Date</label><input type="date" name="date_to" value="<?php echo $date_to; ?>"></div>
                <div class="filter-group"><label>Status</label>
                    <select name="status">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="filter-group"><label>Search</label><input type="text" name="search" placeholder="User or Transaction ID" value="<?php echo htmlspecialchars($search); ?>"></div>
                <div class="filter-group"><button type="submit" class="btn-sm btn-primary">Filter</button></div>
            </form>
            
            <!-- Transactions Table -->
            <div class="section">
                <table class="table">
                    <thead><tr><th>ID</th><th>User</th><th>Course</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><?php echo $t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($t['course_title'] ?? 'N/A', 0, 25)); ?></td>
                                    <td><?php echo number_format($t['amount']); ?> RWF</td>
                                    <td><?php echo $t['payment_method'] == 'momo' ? 'Mobile Money' : 'Credit Card'; ?></td>
                                    <td><span class="status-badge status-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                                    <td><?php echo date('d M Y H:i', strtotime($t['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="payment_id" value="<?php echo $t['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 2px 5px; font-size: 11px;">
                                                <option value="pending" <?php echo $t['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="completed" <?php echo $t['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="failed" <?php echo $t['status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                        <?php if ($is_super_admin && $t['status'] == 'completed'): ?>
                                            <a href="?refund=<?php echo $t['id']; ?>" class="btn-sm" style="background:#e74c3c; color:white;" onclick="return confirm('Refund this payment?')">Refund</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center;">No transactions found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>