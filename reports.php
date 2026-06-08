<?php
// admin/finance/reports.php
// Financial Reports

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdmin()) {
    redirect('dashboard/');
}

$db = Database::getConnection();

$report_type = isset($_GET['type']) ? $_GET['type'] : 'summary';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Get data based on report type
$report_data = [];
$report_title = '';

if ($report_type == 'summary') {
    $report_title = 'Financial Summary Report';
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN payment_method = 'momo' THEN amount ELSE 0 END) as momo_amount,
            SUM(CASE WHEN payment_method = 'credit_card' THEN amount ELSE 0 END) as card_amount
        FROM payments
        WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $report_data = $stmt->fetchAll();
    
} elseif ($report_type == 'expenses') {
    $report_title = 'Expenses Report';
    $stmt = $db->prepare("
        SELECT category, SUM(amount) as total, COUNT(*) as count, expense_date
        FROM expenses
        WHERE expense_date BETWEEN ? AND ?
        GROUP BY category
        ORDER BY total DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $report_data = $stmt->fetchAll();
    
} elseif ($report_type == 'instructors') {
    $report_title = 'Instructor Earnings Report';
    $stmt = $db->prepare("
        SELECT u.full_name, u.email, COUNT(DISTINCT c.id) as courses,
               COUNT(DISTINCT e.user_id) as students,
               SUM(p.amount) as total_revenue
        FROM users u
        JOIN courses c ON u.id = c.instructor_id
        LEFT JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN payments p ON e.payment_id = p.id AND p.status = 'completed'
        GROUP BY u.id
        ORDER BY total_revenue DESC
    ");
    $stmt->execute();
    $report_data = $stmt->fetchAll();
}

$page_title = 'Financial Reports';
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
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .btn-primary { background: var(--orange); color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
        .report-nav { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .report-btn { padding: 8px 16px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 8px; }
        .report-btn.active { background: var(--orange); color: white; }
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
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/payouts.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Payouts</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/reports.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Reports</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a></div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="top-bar">
                <h1><i class="fas fa-chart-bar"></i> Financial Reports</h1>
                <a href="<?php echo SITE_URL; ?>admin/" class="btn-primary">Back to Admin</a>
            </div>
            
            <!-- Report Navigation -->
            <div class="report-nav">
                <a href="?type=summary&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="report-btn <?php echo $report_type == 'summary' ? 'active' : ''; ?>">Summary Report</a>
                <a href="?type=expenses&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="report-btn <?php echo $report_type == 'expenses' ? 'active' : ''; ?>">Expenses Report</a>
                <a href="?type=instructors" class="report-btn <?php echo $report_type == 'instructors' ? 'active' : ''; ?>">Instructor Earnings</a>
            </div>
            
            <!-- Date Filter -->
            <form method="GET" class="filter-bar">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                <div class="filter-group"><label>From Date</label><input type="date" name="date_from" value="<?php echo $date_from; ?>"></div>
                <div class="filter-group"><label>To Date</label><input type="date" name="date_to" value="<?php echo $date_to; ?>"></div>
                <div class="filter-group"><button type="submit" class="btn-primary">Generate Report</button></div>
                <div class="filter-group"><button type="button" class="btn-primary" onclick="window.print()" style="background: #27AE60;">Print Report</button></div>
            </form>
            
            <!-- Report Content -->
            <div class="section">
                <h2><?php echo $report_title; ?></h2>
                <p>Period: <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?></p>
                
                <table class="table" style="margin-top: 20px;">
                    <thead>
                        <?php if ($report_type == 'summary'): ?>
                            <tr><th>Date</th><th>Transactions</th><th>Mobile Money</th><th>Credit Card</th><th>Total Revenue</th></tr>
                        <?php elseif ($report_type == 'expenses'): ?>
                            <td><th>Category</th><th>Number of Expenses</th><th>Total Amount</th><th>Average</th></tr>
                        <?php elseif ($report_type == 'instructors'): ?>
                            <tr><th>Instructor</th><th>Courses</th><th>Students</th><th>Total Revenue</th></tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if (count($report_data) > 0): ?>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <?php if ($report_type == 'summary'): ?>
                                        <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                        <td><?php echo $row['transaction_count']; ?></a></td>
                                        <td><?php echo number_format($row['momo_amount']); ?> RWF</a></td>
                                        <td><?php echo number_format($row['card_amount']); ?> RWF</a></td>
                                        <td><strong><?php echo number_format($row['total_amount']); ?> RWF</strong></a><tr>
                                    <?php elseif ($report_type == 'expenses'): ?>
                                        <td><?php echo $row['category']; ?></td>
                                        <td><?php echo $row['count']; ?></a></td>
                                        <td><?php echo number_format($row['total']); ?> RWF</a></td>
                                        <td><?php echo number_format($row['total'] / $row['count']); ?> RWF</a></td>
                                    <?php elseif ($report_type == 'instructors'): ?>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></a></td>
                                        <td><?php echo $row['courses']; ?></a></td>
                                        <td><?php echo $row['students']; ?></a></td>
                                        <td><?php echo number_format($row['total_revenue'] ?? 0); ?> RWF</a></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" style="text-align: center;">No data found for selected period</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>