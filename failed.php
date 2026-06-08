<?php
// payment/failed.php
// Payment Failed Page

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$page_title = 'Payment Failed';
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
        .failed-container {
            max-width: 600px;
            margin: 80px auto;
            text-align: center;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .failed-icon {
            font-size: 80px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .btn-retry {
            background: var(--orange);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="failed-container">
            <div class="failed-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1>Payment Failed</h1>
            <p>Your payment could not be processed. Please try again or contact support.</p>
            <a href="javascript:history.back()" class="btn-retry">
                <i class="fas fa-redo"></i> Try Again
            </a>
            <br>
            <a href="<?php echo SITE_URL; ?>contact.php" style="display: inline-block; margin-top: 15px; color: var(--blue);">
                Contact Support
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>