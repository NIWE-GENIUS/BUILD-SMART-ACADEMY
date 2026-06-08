<?php
// admin/finance/settings.php
// Financial Settings

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isSuperAdmin()) {
    redirect('admin/');
}

$db = Database::getConnection();
$message = '';
$error = '';

// Get current settings
$stmt = $db->prepare("SELECT * FROM commission_settings LIMIT 1");
$stmt->execute();
$commission = $stmt->fetch();

$stmt = $db->prepare("SELECT * FROM tax_settings WHERE is_active = 1");
$stmt->execute();
$taxes = $stmt->fetchAll();

// Handle commission update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_commission'])) {
    $commission_rate = floatval($_POST['commission_rate']);
    $instructor_percentage = floatval($_POST['instructor_percentage']);
    
    $stmt = $db->prepare("
        UPDATE commission_settings 
        SET commission_rate = ?, instructor_percentage = ?, updated_by = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$commission_rate, $instructor_percentage, getCurrentUserId(), $commission['id']]);
    $message = "Commission settings updated successfully.";
    
    // Refresh data
    $stmt = $db->prepare("SELECT * FROM commission_settings LIMIT 1");
    $stmt->execute();
    $commission = $stmt->fetch();
}

// Handle tax update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tax'])) {
    $tax_id = intval($_POST['tax_id']);
    $tax_rate = floatval($_POST['tax_rate']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $db->prepare("UPDATE tax_settings SET tax_rate = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$tax_rate, $is_active, $tax_id]);
    $message = "Tax settings updated successfully.";
    
    // Refresh data
    $stmt = $db->prepare("SELECT * FROM tax_settings WHERE is_active = 1");
    $stmt->execute();
    $taxes = $stmt->fetchAll();
}

$page_title = 'Financial Settings';
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
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
        .btn-primary { background: var(--orange); color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
        .success-message { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
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
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></div>
                <div class="nav-item"><a href="<?php echo SITE_URL; ?>admin/finance/settings.php" class="nav-link active"><i class="fas fa-cog"></i> Settings</a></div>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="top-bar">
                <h1><i class="fas fa-cog"></i> Financial Settings</h1>
                <a href="<?php echo SITE_URL; ?>admin/" class="btn-primary" style="padding: 8px 16px; text-decoration: none;">Back to Admin</a>
            </div>
            
            <?php if ($message): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Commission Settings -->
            <div class="section">
                <h2><i class="fas fa-percent"></i> Commission Settings</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Platform Commission Rate (%)</label>
                        <input type="number" name="commission_rate" step="0.01" value="<?php echo $commission['commission_rate'] ?? 20; ?>" required>
                        <small>Percentage taken from each sale as platform fee</small>
                    </div>
                    <div class="form-group">
                        <label>Instructor Earnings (%)</label>
                        <input type="number" name="instructor_percentage" step="0.01" value="<?php echo $commission['instructor_percentage'] ?? 80; ?>" required>
                        <small>Percentage that goes to the instructor</small>
                    </div>
                    <button type="submit" name="update_commission" class="btn-primary">Save Commission Settings</button>
                </form>
            </div>
            
            <!-- Tax Settings -->
            <div class="section">
                <h2><i class="fas fa-file-invoice-dollar"></i> Tax Settings</h2>
                <form method="POST">
                    <?php foreach ($taxes as $tax): ?>
                        <div class="form-group">
                            <label><?php echo htmlspecialchars($tax['tax_name']); ?> (%)</label>
                            <input type="number" name="tax_rate" step="0.01" value="<?php echo $tax['tax_rate']; ?>" required>
                            <div class="checkbox-group" style="margin-top: 5px;">
                                <input type="checkbox" name="is_active" value="1" <?php echo $tax['is_active'] ? 'checked' : ''; ?>>
                                <label style="margin: 0;">Active</label>
                            </div>
                            <input type="hidden" name="tax_id" value="<?php echo $tax['id']; ?>">
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="update_tax" class="btn-primary">Save Tax Settings</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>