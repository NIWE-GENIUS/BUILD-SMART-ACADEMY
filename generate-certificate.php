<?php
// exams/generate-certificate.php
// Generate PDF certificate for completed course

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$user_id = getCurrentUserId();

if (!$course_id) {
    redirect('dashboard/my-certificates.php');
}

// Get certificate
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT c.*, cr.title as course_title, u.full_name as user_name
    FROM certificates c
    JOIN courses cr ON c.course_id = cr.id
    JOIN users u ON c.user_id = u.id
    WHERE c.user_id = ? AND c.course_id = ?
");
$stmt->execute([$user_id, $course_id]);
$certificate = $stmt->fetch();

if (!$certificate) {
    redirect('dashboard/my-certificates.php');
}

// For now, display HTML certificate (PDF generation will be added later)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion - <?php echo htmlspecialchars($certificate['course_title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .certificate {
            max-width: 900px;
            width: 100%;
            background: white;
            border: 15px double var(--orange, #FF6B35);
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .certificate:before {
            content: "";
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 1px solid #ddd;
            pointer-events: none;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: var(--orange, #FF6B35);
            margin-bottom: 20px;
        }
        
        .academy-name {
            font-size: 1.5rem;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        
        .tagline {
            color: #666;
            font-size: 0.8rem;
            margin-bottom: 30px;
        }
        
        .certificate-title {
            font-size: 2rem;
            margin: 30px 0;
            color: #2C3E50;
        }
        
        .recipient {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 20px 0;
            color: var(--orange, #FF6B35);
        }
        
        .course-name {
            font-size: 1.3rem;
            margin: 20px 0;
            color: #2C3E50;
        }
        
        .completion-text {
            font-size: 1rem;
            margin: 20px 0;
            line-height: 1.6;
        }
        
        .score {
            font-size: 1rem;
            margin: 10px 0;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 40px;
        }
        
        .signature {
            text-align: center;
        }
        
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin: 10px 0;
        }
        
        .verification {
            margin-top: 30px;
            font-size: 0.7rem;
            color: #999;
        }
        
        .certificate-number {
            font-size: 0.7rem;
            color: #999;
            margin-top: 20px;
        }
        
        .btn-download {
            display: inline-block;
            margin-top: 20px;
            background: var(--orange, #FF6B35);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-family: Arial, sans-serif;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .btn-download {
                display: none;
            }
            .certificate {
                box-shadow: none;
                border: 15px double #333;
            }
        }
    </style>
</head>
<body>
    <div>
        <div class="certificate">
            <div class="logo">
                <i class="fas fa-hard-hat"></i> <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="academy-name">BUILD SMART ACADEMY</div>
            <div class="tagline">Empowering Quantity Surveyors for the Future of Construction</div>
            
            <div class="certificate-title">CERTIFICATE OF COMPLETION</div>
            
            <div>This certifies that</div>
            <div class="recipient"><?php echo htmlspecialchars($certificate['user_name']); ?></div>
            
            <div>has successfully completed the course</div>
            <div class="course-name"><?php echo htmlspecialchars($certificate['course_title']); ?></div>
            
            <div class="completion-text">
                with a final examination score of <strong><?php echo $certificate['final_score']; ?>%</strong>
            </div>
            
            <div>Awarded on <?php echo date('F d, Y', strtotime($certificate['issue_date'])); ?></div>
            
            <div class="signature-section">
                <div></div>
                <div class="signature">
                    <div class="signature-line"></div>
                    <div><strong>QS Philemon IRUTABYOSE</strong></div>
                    <div>Super Administrator & Certified Quantity Surveyor</div>
                </div>
            </div>
            
            <div class="verification">
                Verify at: <?php echo SITE_URL; ?>verify-certificate.php?code=<?php echo $certificate['verification_code']; ?>
            </div>
            
            <div class="certificate-number">
                Certificate No: <?php echo $certificate['certificate_number']; ?> | Verification Code: <?php echo $certificate['verification_code']; ?>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" class="btn-download">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <a href="<?php echo SITE_URL; ?>dashboard/my-certificates.php" class="btn-download" style="background: #2C3E50;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>