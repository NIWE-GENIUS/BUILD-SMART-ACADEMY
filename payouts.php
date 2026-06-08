<?php
// admin/finance/payouts.php
// Manage Instructor Payouts

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isSuperAdmin()) {
    redirect('admin/');
}

$db = Database::getConnection();

// Handle add payout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payout'])) {
    $instructor_id = intval($_POST['instructor_id']);
    $amount = floatval($_POST['amount']);
    $period_start = $_POST['period_start'];
    $period_end = $_POST['period_end'];
    $payment_method = $_POST['payment_method'];
    
    $stmt = $db->prepare("
        INSERT INTO instructor_payouts (instructor_id, amount, period_start, period_end, payment_method)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$instructor_id, $amount, $period_start, $period_end, $payment_method]);
    $success = "Payout added successfully.";
}

// Handle update payout status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $payout_id = intval($_POST['payout_id']);
    $status = $_POST['status'];
    $transaction_id = sanitizeInput($_POST['transaction_id'] ?? '');
    
    $stmt = $db->prepare("
        UPDATE instructor_payouts SET status = ?, transaction_id = ?, paid_at = NOW() WHERE id = ?
    ");
    $stmt->execute([$status, $transaction_id, $payout_id]);
    $success = "Payout status updated.";
}

// Get all payouts
$stmt = $db->prepare("
    SELECT p.*, u.full_name, u.email
    FROM instructor_payouts p
    JOIN users u ON p.instructor_id = u.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$payouts = $stmt->fetchAll();

// Get instructors (users with courses)
$stmt = $db->prepare("
    SELECT DISTINCT u.id, u.full_name, u.email
    FROM users u
    JOIN courses c ON u.id = c.instructor_id
    WHERE u.role != 'super_admin'
");
$stmt->execute();
$instructors = $stmt->fetchAll();

$page_title = 'Payouts';
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
        .section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; }
        .form-group input, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-primary { background: var(--orange); color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-sm { padding: 4px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
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
            <div class="sidebar-header"><i class="fas fa-chart-line" style="font-size: 28px; color: var(--orange);"></i><h3>Finance</h3></div>
            <div class="sidebar-nav">
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/transactions.php" class="nav-link"><i class="fas fa-exchange-alt"></i> Transactions</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/expenses.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Expenses</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/invoices.php" class="nav-link"><i class="fas fa-file-invoice"></i> Invoices</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/payouts.php" class="nav-link active"><i class="fas fa-money-bill-wave"></i> Payouts</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a></div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="top-bar">
                <h1><i class="fas fa-money-bill-wave"></i> Instructor Payouts</h1>
                <a href="<?php echo SITE_URL; ?>admin/" class="btn-primary" style="padding: 8px 16px; text-decoration: none;">Back to Admin</a>
            </div>
            
            <!-- Add Payout Form -->
            <div class="section">
                <h3>Create New Payout</h3>
                <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="form-group"><label>Instructor</label><select name="instructor_id" required>
                        <option value="">Select Instructor</option>
                        <?php foreach ($instructors as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>"><?php echo htmlspecialchars($inst['full_name']); ?> (<?php echo $inst['email']; ?>)</option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="form-group"><label>Amount (RWF)</label><input type="number" name="amount" required step="0.01"></div>
                    <div class="form-group"><label>Period Start</label><input type="date" name="period_start" required></div>
                    <div class="form-group"><label>Period End</label><input type="date" name="period_end" required></div>
                    <div class="form-group"><label>Payment Method</label><select name="payment_method">
                        <option value="mobile_money">Mobile Money</option><option value="bank">Bank Transfer</option>
                    </select></div>
                    <div class="form-group"><button type="submit" name="add_payout" class="btn-primary">Create Payout</button></div>
                </form>
            </div>
            
            <!-- Payouts List -->
            <div class="section">
                <table class="table">
                    <thead><tr><th>Instructor</th><th>Amount</th><th>Period</th><th>Method</th><th>Status</th><th>Transaction ID</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($payouts as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['full_name']); ?></a></td>
                                <td><?php echo number_format($p['amount']); ?> RWF</a></td>
                                <td><?php echo date('d M Y', strtotime($p['period_start'])); ?> - <?php echo date('d M Y', strtotime($p['period_end'])); ?></a></td>
                                <td><?php echo $p['payment_method'] == 'mobile_money' ? 'Mobile Money' : 'Bank Transfer'; ?></a></td>
                                <td><span class="status-badge status-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></a></td>
                                <td><?php echo $p['transaction_id'] ?? '-'; ?></a></td>
                                <td><?php echo date('d M Y', strtotime($p['created_at'])); ?></a></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="payout_id" value="<?php echo $p['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 2px 5px; font-size: 11px;">
                                            <option value="pending" <?php echo $p['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo $p['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        </select>
                                        <input type="text" name="transaction_id" placeholder="Transaction ID" value="<?php echo $p['transaction_id']; ?>" style="width: 100px;">
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                 </a>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>