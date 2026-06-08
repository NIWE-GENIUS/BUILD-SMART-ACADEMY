<?php
// contact.php
// Contact Page with WhatsApp Integration

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'All fields are required.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Send email to admin
            $admin_email = 'irutabyosephilemon78@gmail.com';
            $email_subject = "Contact Form: $subject";
            $email_body = "
                <h3>New Contact Message</h3>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br($message) . "</p>
            ";
            
            sendEmail($admin_email, $email_subject, $email_body);
            
            // Send auto-reply to user
            $auto_reply = "
                <h3>Thank you for contacting BUILD SMART ACADEMY</h3>
                <p>Dear $name,</p>
                <p>We have received your message and will get back to you within 48 hours.</p>
                <p>For urgent inquiries, please contact us on WhatsApp: +250793000960</p>
                <br>
                <p>Best regards,<br>QS Philemon IRUTABYOSE</p>
            ";
            sendEmail($email, "We received your message", $auto_reply);
            
            $success = 'Your message has been sent successfully. We will get back to you soon.';
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'Contact Us';
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
        .contact-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .contact-info {
            background: var(--blue);
            color: white;
            border-radius: 20px;
            padding: 40px;
        }
        
        .contact-info h2 {
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 25px 0;
        }
        
        .info-item i {
            font-size: 24px;
            width: 40px;
        }
        
        .whatsapp-btn {
            display: inline-block;
            background: #25d366;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            margin-top: 20px;
        }
        
        .contact-form {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .btn-submit {
            background: var(--orange);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .error {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .social-links a {
            color: white;
            font-size: 24px;
        }
        
        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="contact-container">
        <div class="contact-info">
            <h2><i class="fas fa-envelope"></i> Get in Touch</h2>
            <p>Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Email</strong><br>
                    irutabyosephilemon78@gmail.com
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-phone-alt"></i>
                <div>
                    <strong>Phone / WhatsApp</strong><br>
                    +250 793 000 960
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <strong>Location</strong><br>
                    Kigali, Rwanda
                </div>
            </div>
            
            <a href="https://wa.me/250793000960" target="_blank" class="whatsapp-btn">
                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
            </a>
            
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
        
        <div class="contact-form">
            <h2>Send a Message</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="name">Your Name *</label>
                    <input type="text" id="name" name="name" required value="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>