<?php
// admin/finance/expenses.php
// Manage Expenses

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isSuperAdmin()) {
    redirect('admin/');
}

$db = Database::getConnection();

// Handle add expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $category = sanitizeInput($_POST['category']);
    $description = sanitizeInput($_POST['description']);
    $amount = floatval($_POST['amount']);
    $expense_date = $_POST['expense_date'];
    $payment_method = $_POST['payment_method'];
    
    $stmt = $db->prepare("
        INSERT INTO expenses (category, description, amount, expense_date, payment_method, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$category, $description, $amount, $expense_date, $payment_method, getCurrentUserId()]);
    $success = "Expense added successfully.";
}

// Handle delete expense
if (isset($_GET['delete'])) {
    $expense_id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$expense_id]);
    redirect('admin/finance/expenses.php');
}

// Get expenses
$stmt = $db->prepare("
    SELECT e.*, u.full_name as created_by_name
    FROM expenses e
    LEFT JOIN users u ON e.created_by = u.id
    ORDER BY e.expense_date DESC
");
$stmt->execute();
$expenses = $stmt->fetchAll();

// Get totals by category
$stmt = $db->prepare("
    SELECT category, SUM(amount) as total, COUNT(*) as count
    FROM expenses
    GROUP BY category
");
$stmt->execute();
$category_totals = $stmt->fetchAll();

$page_title = 'Expenses';
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
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-primary { background: var(--orange); color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-danger { background: #e74c3c; color: white; padding: 4px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; text-align: center; box-shadow: var(--shadow); }
        .stat-card h3 { font-size: 24px; margin: 0; color: var(--orange); }
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
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/expenses.php" class="nav-link active"><i class="fas fa-shopping-cart"></i> Expenses</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/invoices.php" class="nav-link"><i class="fas fa-file-invoice"></i> Invoices</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/payouts.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Payouts</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a></div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="top-bar">
                <h1><i class="fas fa-shopping-cart"></i> Expenses</h1>
                <a href="<?php echo SITE_URL; ?>admin/" class="btn-primary" style="padding: 8px 16px; text-decoration: none;">Back to Admin</a>
            </div>
            
            <!-- Add Expense Form -->
            <div class="section">
                <h3>Add New Expense</h3>
                <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="form-group"><label>Category</label><select name="category" required>
                        <option value="Marketing">Marketing</option><option value="Hosting">Hosting</option>
                        <option value="Salaries">Salaries</option><option value="Software">Software</option>
                        <option value="Office">Office</option><option value="Other">Other</option>
                    </select></div>
                    <div class="form-group"><label>Amount (RWF)</label><input type="number" name="amount" required step="0.01"></div>
                    <div class="form-group"><label>Date</label><input type="date" name="expense_date" required value="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="form-group"><label>Payment Method</label><select name="payment_method">
                        <option value="bank">Bank Transfer</option><option value="mobile_money">Mobile Money</option>
                        <option value="cash">Cash</option><option value="credit_card">Credit Card</option>
                    </select></div>
                    <div class="form-group"><label>Description</label><input type="text" name="description" placeholder="Description..."></div>
                    <div class="form-group"><button type="submit" name="add_expense" class="btn-primary">Add Expense</button></div>
                </form>
            </div>
            
            <!-- Category Summary -->
            <div class="stats-grid">
                <?php foreach ($category_totals as $cat): ?>
                    <div class="stat-card"><h3><?php echo number_format($cat['total']); ?> RWF</h3><p><?php echo $cat['category']; ?> (<?php echo $cat['count']; ?> expenses)</p></div>
                <?php endforeach; ?>
            </div>
            
            <!-- Expenses List -->
            <div class="section">
                <table class="table">
                    <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Method</th><th>Added By</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($e['expense_date'])); ?></td>
                                <td><?php echo $e['category']; ?></td>
                                <td><?php echo htmlspecialchars($e['description']); ?></td>
                                <td><?php echo number_format($e['amount']); ?> RWF</td>
                                <td><?php echo str_replace('_', ' ', ucfirst($e['payment_method'])); ?></td>
                                <td><?php echo htmlspecialchars($e['created_by_name'] ?? 'System'); ?></td>
                                <td><a href="?delete=<?php echo $e['id']; ?>" class="btn-danger" onclick="return confirm('Delete this expense?')">Delete</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>