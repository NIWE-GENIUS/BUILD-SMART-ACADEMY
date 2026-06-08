<?php
// dashboard/my-certificates.php
// Display user's certificates

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$user_id = getCurrentUserId();
$certificates = getUserCertificates($user_id);

$page_title = 'My Certificates';
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
        .certificates-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .certificate-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .certificate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .certificate-info {
            flex: 1;
        }
        
        .certificate-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .certificate-meta {
            color: #666;
            font-size: 13px;
            margin-top: 8px;
        }
        
        .certificate-meta i {
            margin-right: 5px;
        }
        
        .certificate-score {
            background: #e8f5e9;
            color: #27ae60;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-top: 8px;
        }
        
        .certificate-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-view {
            background: var(--orange);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .btn-verify {
            background: var(--blue);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 15px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .certificate-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="certificates-container">
            <div class="page-header">
                <h1><i class="fas fa-certificate"></i> My Certificates</h1>
                <p>Your earned certificates from completed courses</p>
            </div>
            
            <?php if (count($certificates) > 0): ?>
                <?php foreach ($certificates as $cert): ?>
                    <div class="certificate-card">
                        <div class="certificate-info">
                            <div class="certificate-title">
                                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($cert['course_title']); ?>
                            </div>
                            <div class="certificate-meta">
                                <i class="fas fa-id-card"></i> Certificate No: <?php echo htmlspecialchars($cert['certificate_number']); ?>
                            </div>
                            <div class="certificate-meta">
                                <i class="fas fa-calendar-alt"></i> Issued: <?php echo formatDate($cert['issue_date']); ?>
                            </div>
                            <div class="certificate-score">
                                <i class="fas fa-chart-line"></i> Final Score: <?php echo $cert['final_score']; ?>%
                            </div>
                        </div>
                        <div class="certificate-actions">
                            <a href="<?php echo SITE_URL; ?>exams/generate-certificate.php?course_id=<?php echo $cert['course_id']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="<?php echo SITE_URL; ?>verify-certificate.php?code=<?php echo $cert['verification_code']; ?>" target="_blank" class="btn-verify">
                                <i class="fas fa-shield-alt"></i> Verify
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-certificate"></i>
                    <h3>No Certificates Yet</h3>
                    <p>Complete courses and pass the final exam to earn certificates.</p>
                    <a href="<?php echo SITE_URL; ?>courses/" class="btn-primary" style="display: inline-block; margin-top: 20px;">
                        Browse Courses
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>