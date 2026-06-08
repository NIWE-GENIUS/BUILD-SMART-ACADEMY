<?php
// admin/database-viewer.php
// View all database tables and data

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

if (!isSuperAdmin()) {
    redirect('admin/');
}

$db = Database::getConnection();

// Get all tables
$stmt = $db->prepare("SHOW TABLES");
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$selected_table = isset($_GET['table']) ? $_GET['table'] : null;
$table_data = [];
$table_columns = [];

if ($selected_table && in_array($selected_table, $tables)) {
    $stmt = $db->prepare("SELECT * FROM $selected_table ORDER BY id DESC LIMIT 100");
    $stmt->execute();
    $table_data = $stmt->fetchAll();
    
    $stmt = $db->prepare("DESCRIBE $selected_table");
    $stmt->execute();
    $table_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$page_title = 'Database Viewer';
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
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .table-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .table-btn {
            padding: 8px 16px;
            background: #f0f0f0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
        }
        .table-btn.active {
            background: #FF6B35;
            color: white;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            overflow-x: auto;
            display: block;
        }
        .data-table th, .data-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 12px;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> Database Viewer</h1>
        <p>View all data in your database (Super Admin only)</p>
        
        <div class="card">
            <h3>Tables</h3>
            <div class="table-list">
                <?php foreach ($tables as $table): ?>
                    <a href="?table=<?php echo urlencode($table); ?>" class="table-btn <?php echo $selected_table == $table ? 'active' : ''; ?>">
                        <?php echo $table; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($selected_table && !empty($table_data)): ?>
            <div class="card">
                <h3>Table: <?php echo $selected_table; ?></h3>
                <p>Showing <?php echo count($table_data); ?> records</p>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php foreach ($table_columns as $col): ?>
                                    <th><?php echo $col; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_data as $row): ?>
                                <tr>
                                    <?php foreach ($table_columns as $col): ?>
                                        <td><?php echo htmlspecialchars(substr($row[$col] ?? '', 0, 100)); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($selected_table): ?>
            <div class="card">
                <p>No data found in this table.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>