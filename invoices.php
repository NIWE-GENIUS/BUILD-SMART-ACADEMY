<?php
// admin/finance/invoices.php
// Manage Invoices

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdmin()) {
    redirect('dashboard/');
}

$db = Database::getConnection();

// Get all invoices
$stmt = $db->prepare("
    SELECT i.*, u.full_name, u.email, c.title as course_title
    FROM invoices i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN courses c ON i.course_id = c.id
    ORDER BY i.created_at DESC
");
$stmt->execute();
$invoices = $stmt->fetchAll();

$page_title = 'Invoices';
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
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
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
            <div class="sidebar-header"><i class="fas fa-chart-line" style="font-size: 28px; color: var(--orange);"></i><h3>Finance</h3></div>
            <div class="sidebar-nav">
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/transactions.php" class="nav-link"><i class="fas fa-exchange-alt"></i> Transactions</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/expenses.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Expenses</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/invoices.php" class="nav-link active"><i class="fas fa-file-invoice"></i> Invoices</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/payouts.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Payouts</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a></div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="top-bar">
                <h1><i class="fas fa-file-invoice"></i> Invoices</h1>
                <a href="<?php echo SITE_URL; ?>admin/" class="btn-sm btn-primary">Back to Admin</a>
            </div>
            
            <div class="section">
                <table class="table">
                    <thead><tr><th>Invoice #</th><th>User</th><th>Course</th><th>Amount</th><th>Tax</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (count($invoices) > 0): ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><?php echo $inv['invoice_number']; ?></td>
                                    <td><?php echo htmlspecialchars($inv['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($inv['course_title'] ?? 'N/A', 0, 20)); ?></td>
                                    <td><?php echo number_format($inv['amount']); ?> RWF</td>
                                    <td><?php echo number_format($inv['tax_amount']); ?> RWF</td>
                                    <td><strong><?php echo number_format($inv['total_amount']); ?> RWF</strong></td>
                                    <td><span class="status-badge status-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($inv['created_at'])); ?></td>
                                    <td><a href="#" class="btn-sm btn-primary" onclick="alert('Invoice PDF generation coming soon')">View PDF</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align: center;">No invoices found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>