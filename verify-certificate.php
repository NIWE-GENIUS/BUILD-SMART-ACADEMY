<?php
// verify-certificate.php
// Public certificate verification page

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

$verification_code = isset($_GET['code']) ? sanitizeInput($_GET['code']) : '';

$certificate = null;
$error = '';

if (!empty($verification_code)) {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT c.*, u.full_name as user_name, cr.title as course_title
        FROM certificates c
        JOIN users u ON c.user_id = u.id
        JOIN courses cr ON c.course_id = cr.id
        WHERE c.verification_code = ?
    ");
    $stmt->execute([$verification_code]);
    $certificate = $stmt->fetch();
    
    if (!$certificate) {
        $error = 'Invalid verification code. Certificate not found.';
    }
}

$page_title = 'Verify Certificate';
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
        .verify-container {
            max-width: 800px;
            margin: 50px auto;
            text-align: center;
        }
        
        .verification-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .valid-badge {
            background: #27ae60;
            color: white;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .invalid-badge {
            background: #e74c3c;
            color: white;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .certificate-details {
            text-align: left;
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }
        
        .detail-row {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: 600;
            width: 150px;
            display: inline-block;
        }
        
        .search-box {
            margin-top: 30px;
        }
        
        .search-input {
            padding: 12px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .search-btn {
            padding: 12px 24px;
            background: var(--orange);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <div class="verify-container">
            <div class="verification-card">
                <h1><i class="fas fa-shield-alt"></i> Certificate Verification</h1>
                <p>Verify the authenticity of BUILD SMART ACADEMY certificates</p>
                
                <div class="search-box">
                    <form method="GET" action="">
                        <input type="text" name="code" class="search-input" 
                               placeholder="Enter verification code" 
                               value="<?php echo htmlspecialchars($verification_code); ?>">
                        <button type="submit" class="search-btn">Verify</button>
                    </form>
                </div>
                
                <?php if (!empty($verification_code)): ?>
                    <?php if ($certificate): ?>
                        <div class="valid-badge">
                            <i class="fas fa-check-circle"></i> VALID CERTIFICATE
                        </div>
                        
                        <div class="certificate-details">
                            <div class="detail-row">
                                <span class="detail-label">Certificate No:</span>
                                <?php echo htmlspecialchars($certificate['certificate_number']); ?>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Learner Name:</span>
                                <?php echo htmlspecialchars($certificate['user_name']); ?>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Course:</span>
                                <?php echo htmlspecialchars($certificate['course_title']); ?>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Final Score:</span>
                                <?php echo $certificate['final_score']; ?>%
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Issue Date:</span>
                                <?php echo formatDate($certificate['issue_date']); ?>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Issued By:</span>
                                QS Philemon IRUTABYOSE, Super Administrator & Certified Quantity Surveyor
                            </div>
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <a href="<?php echo SITE_URL; ?>exams/generate-certificate.php?course_id=<?php echo $certificate['course_id']; ?>" 
                               class="btn-primary" style="display: inline-block;">View Certificate</a>
                        </div>
                    <?php else: ?>
                        <div class="invalid-badge">
                            <i class="fas fa-times-circle"></i> INVALID CERTIFICATE
                        </div>
                        <p style="margin-top: 20px;">No matching certificate found with the provided code.</p>
                        <p>Please contact BUILD SMART ACADEMY for assistance:</p>
                        <p>Email: irutabyosephilemon78@gmail.com | WhatsApp: +250793000960</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>