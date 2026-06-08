<?php
// config/functions.php
// BUILD SMART ACADEMY - Complete Functions File
// Version: 4.0 - With Working Email (PHPMailer)

require_once __DIR__ . '/database.php';

// =============================================
// SESSION & AUTHENTICATION FUNCTIONS
// =============================================

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'sub_admin');
    }
}

if (!function_exists('isSubAdmin')) {
    function isSubAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'sub_admin';
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . SITE_URL . $url);
        exit();
    }
}

if (!function_exists('redirectBack')) {
    function redirectBack() {
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            redirect('');
        }
        exit();
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('getCurrentUserRole')) {
    function getCurrentUserRole() {
        return $_SESSION['role'] ?? null;
    }
}

if (!function_exists('getCurrentUserName')) {
    function getCurrentUserName() {
        return $_SESSION['user_name'] ?? null;
    }
}

if (!function_exists('getCurrentUserEmail')) {
    function getCurrentUserEmail() {
        return $_SESSION['user_email'] ?? null;
    }
}

if (!function_exists('logout')) {
    function logout() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        session_destroy();
    }
}

if (!function_exists('checkRememberMe')) {
    function checkRememberMe() {
        if (isLoggedIn()) {
            return;
        }
        
        if (!isset($_COOKIE['remember_token'])) {
            return;
        }
        
        $token = $_COOKIE['remember_token'];
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT u.id, u.full_name, u.email, u.phone, u.role, u.lifetime_free, u.is_verified,
                   ut.token as stored_token, ut.expires_at
            FROM user_tokens ut
            JOIN users u ON ut.user_id = u.id
            WHERE ut.expires_at > NOW()
        ");
        $stmt->execute();
        $tokens = $stmt->fetchAll();
        
        foreach ($tokens as $record) {
            if (password_verify($token, $record['stored_token'])) {
                if (!$record['is_verified']) {
                    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
                    return;
                }
                
                $_SESSION['user_id'] = $record['id'];
                $_SESSION['user_name'] = $record['full_name'];
                $_SESSION['user_email'] = $record['email'];
                $_SESSION['user_phone'] = $record['phone'];
                $_SESSION['role'] = $record['role'];
                $_SESSION['lifetime_free'] = (bool)$record['lifetime_free'];
                
                $new_expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                $stmt = $db->prepare("UPDATE user_tokens SET expires_at = ? WHERE user_id = ?");
                $stmt->execute([$new_expires, $record['id']]);
                
                break;
            }
        }
    }
}

// =============================================
// SECURITY & VALIDATION FUNCTIONS
// =============================================

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('sanitizeInput', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validatePhone')) {
    function validatePhone($phone) {
        return preg_match('/^\+250[0-9]{9}$/', $phone);
    }
}

if (!function_exists('validatePasswordStrength')) {
    function validatePasswordStrength($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('generateOTP')) {
    function generateOTP($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('checkRateLimit')) {
    function checkRateLimit($key, $limit = 5, $window = 3600) {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$key, $window]);
        $count = $stmt->fetchColumn();
        
        if ($count >= $limit) {
            return false;
        }
        
        $stmt = $db->prepare("INSERT INTO rate_limits (identifier) VALUES (?)");
        $stmt->execute([$key]);
        
        return true;
    }
}

// =============================================
// EMAIL FUNCTION - WORKING WITH PHPMailer
// =============================================

if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $body) {
        // Load PHPMailer classes
        require_once __DIR__ . '/../PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
        
        // Use the PHPMailer namespace
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'irutabyosephilemon78@gmail.com';
            $mail->Password   = 'wlfncanltdwqjfvu'; // Your 16-character App Password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Recipients
            $mail->setFrom('irutabyosephilemon78@gmail.com', SITE_NAME);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
            
            $mail->send();
            error_log("✅ Email sent successfully to: $to");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Email failed to: $to | Error: {$mail->ErrorInfo}");
            
            // For development, extract and log OTP for testing
            preg_match('/<div class="otp-code">(.*?)<\/div>/', $body, $matches);
            $otp = $matches[1] ?? 'N/A';
            error_log("📧 OTP would be: $otp");
            
            return false;
        }
    }
}

// =============================================
// SMS FUNCTION (Log only for development)
// =============================================

if (!function_exists('sendSMS')) {
    function sendSMS($phone, $message) {
        // For development, log SMS to error log
        error_log("=========================================");
        error_log("📱 SMS WOULD BE SENT (Development Mode)");
        error_log("TO: $phone");
        error_log("MESSAGE: $message");
        error_log("=========================================");
        
        // Extract OTP from message for easy viewing
        preg_match('/code is: (\d+)/', $message, $matches);
        $otp = $matches[1] ?? 'N/A';
        error_log("🔐 SMS OTP: $otp");
        
        return true;
        
        // =============================================
        // UNCOMMENT FOR PRODUCTION WITH AFRICA'S TALKING
        // =============================================
        /*
        $username = SMS_USERNAME;
        $api_key = SMS_API_KEY;
        
        $url = 'https://api.africastalking.com/version1/messaging';
        $data = [
            'username' => $username,
            'to' => $phone,
            'message' => $message,
            'from' => SMS_SENDER_ID
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['ApiKey: ' . $api_key]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return true;
        */
    }
}

// =============================================
// WELCOME EMAIL FUNCTION
// =============================================

if (!function_exists('sendWelcomeEmail')) {
    function sendWelcomeEmail($email, $name, $isLifetimeFree = false) {
        $subject = "Welcome to " . SITE_NAME . "!";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Welcome to " . SITE_NAME . "</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #FF6B35, #1A5F7A); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 10px 10px; }
                .button { background: #FF6B35; color: white; padding: 12px 25px; text-decoration: none; border-radius: 30px; display: inline-block; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .promo-box { background: linear-gradient(135deg, #F39C12, #e67e22); color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>" . SITE_NAME . "</h2>
                    <p>Empowering Quantity Surveyors for the Future of Construction</p>
                </div>
                <div class='content'>
                    <h3>Welcome, " . htmlspecialchars($name) . "! 🎉</h3>
                    <p>Thank you for joining BUILD SMART ACADEMY. We're excited to help you advance your quantity surveying career.</p>";
                    
        if ($isLifetimeFree) {
            $body .= "<div class='promo-box'>
                        <strong>🎁 SPECIAL OFFER!</strong><br>
                        You are one of our first 20 users! You have <strong>LIFETIME FREE ACCESS</strong> to all courses.
                      </div>";
        }
        
        $body .= "
                    <p><strong>📚 Getting Started:</strong></p>
                    <ul>
                        <li>✅ Complete your profile</li>
                        <li>✅ Browse our courses</li>
                        <li>✅ Join the community forum</li>
                        <li>✅ Earn certificates</li>
                    </ul>
                    <p style='text-align: center;'>
                        <a href='" . SITE_URL . "dashboard/' class='button'>Go to Dashboard →</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                    <p>Contact: irutabyosephilemon78@gmail.com | WhatsApp: +250793000960</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return sendEmail($email, $subject, $body);
    }
}

// =============================================
// NOTIFICATION FUNCTIONS
// =============================================

if (!function_exists('createNotification')) {
    function createNotification($user_id, $title, $message, $type = 'general') {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $title, $message, $type]);
    }
}

if (!function_exists('getUnreadNotificationCount')) {
    function getUnreadNotificationCount($user_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
}

if (!function_exists('getUserNotifications')) {
    function getUserNotifications($user_id, $limit = 20) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('markNotificationRead')) {
    function markNotificationRead($notification_id, $user_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications SET is_read = TRUE 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notification_id, $user_id]);
    }
}

if (!function_exists('markAllNotificationsRead')) {
    function markAllNotificationsRead($user_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications SET is_read = TRUE 
            WHERE user_id = ? AND is_read = FALSE
        ");
        return $stmt->execute([$user_id]);
    }
}

// =============================================
// USER PROFILE FUNCTIONS
// =============================================

if (!function_exists('getUserById')) {
    function getUserById($user_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, full_name, email, phone, profile_picture, professional_title, 
                   years_experience, country, bio, role, lifetime_free, is_verified, created_at, last_login
            FROM users WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
}

if (!function_exists('getUserByEmail')) {
    function getUserByEmail($email) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}

if (!function_exists('hasLifetimeFree')) {
    function hasLifetimeFree($user_id = null) {
        if ($user_id === null && isLoggedIn()) {
            $user_id = $_SESSION['user_id'];
        }
        if (!$user_id) return false;
        
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT lifetime_free FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? (bool)$result['lifetime_free'] : false;
    }
}

if (!function_exists('isProfileComplete')) {
    function isProfileComplete($user_id) {
        $user = getUserById($user_id);
        if (!$user) return false;
        
        $required_fields = ['full_name', 'email', 'phone', 'professional_title', 'years_experience', 'country'];
        
        foreach ($required_fields as $field) {
            if (empty($user[$field])) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('updateUserProfile')) {
    function updateUserProfile($user_id, $data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE users SET 
                full_name = ?,
                professional_title = ?,
                years_experience = ?,
                country = ?,
                bio = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['full_name'],
            $data['professional_title'],
            $data['years_experience'],
            $data['country'],
            $data['bio'],
            $user_id
        ]);
    }
}

if (!function_exists('updateProfilePicture')) {
    function updateProfilePicture($user_id, $file_path) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        return $stmt->execute([$file_path, $user_id]);
    }
}

if (!function_exists('getProfileCompletionPercentage')) {
    function getProfileCompletionPercentage($user_id) {
        $user = getUserById($user_id);
        if (!$user) return 0;
        
        $fields = ['full_name', 'email', 'phone', 'professional_title', 'years_experience', 'country', 'profile_picture'];
        
        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($user[$field])) {
                $filled++;
            }
        }
        return round(($filled / count($fields)) * 100);
    }
}

// =============================================
// COURSE & PROGRESS FUNCTIONS
// =============================================

if (!function_exists('isEnrolled')) {
    function isEnrolled($user_id, $course_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        return $stmt->fetch() !== false;
    }
}

if (!function_exists('getCourseProgress')) {
    function getCourseProgress($user_id, $course_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT cu.id) as total_units,
                COUNT(DISTINCT CASE WHEN upp.status = 'completed' THEN upp.unit_id END) as completed_units
            FROM course_units cu
            LEFT JOIN user_unit_progress upp ON cu.id = upp.unit_id AND upp.user_id = ?
            WHERE cu.course_id = ?
        ");
        $stmt->execute([$user_id, $course_id]);
        $result = $stmt->fetch();
        
        if ($result && $result['total_units'] > 0) {
            return round(($result['completed_units'] / $result['total_units']) * 100);
        }
        return 0;
    }
}

if (!function_exists('getEnrolledCourses')) {
    function getEnrolledCourses($user_id, $limit = null) {
        $db = Database::getConnection();
        $sql = "
            SELECT c.*, e.enrolled_at, e.is_completed, e.completed_at
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = ?
            ORDER BY e.enrolled_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id, $limit]);
        } else {
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id]);
        }
        return $stmt->fetchAll();
    }
}

if (!function_exists('getNextUnit')) {
    function getNextUnit($course_id, $current_unit_number) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, unit_number, title FROM course_units 
            WHERE course_id = ? AND unit_number > ? 
            ORDER BY unit_number ASC LIMIT 1
        ");
        $stmt->execute([$course_id, $current_unit_number]);
        return $stmt->fetch();
    }
}

if (!function_exists('getPreviousUnit')) {
    function getPreviousUnit($course_id, $current_unit_number) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, unit_number, title FROM course_units 
            WHERE course_id = ? AND unit_number < ? 
            ORDER BY unit_number DESC LIMIT 1
        ");
        $stmt->execute([$course_id, $current_unit_number]);
        return $stmt->fetch();
    }
}

if (!function_exists('getUserBadges')) {
    function getUserBadges($user_id, $limit = null) {
        $db = Database::getConnection();
        $sql = "
            SELECT * FROM user_badges 
            WHERE user_id = ? 
            ORDER BY awarded_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id, $limit]);
        } else {
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id]);
        }
        return $stmt->fetchAll();
    }
}

if (!function_exists('awardBadge')) {
    function awardBadge($user_id, $badge_name, $course_id, $unit_id = null) {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT id FROM user_badges 
            WHERE user_id = ? AND badge_name = ? AND course_id = ?
        ");
        $stmt->execute([$user_id, $badge_name, $course_id]);
        if ($stmt->fetch()) {
            return false;
        }
        
        $badge_image_url = SITE_URL . "assets/images/badges/" . strtolower(str_replace(' ', '-', $badge_name)) . ".png";
        
        $stmt = $db->prepare("
            INSERT INTO user_badges (user_id, badge_name, course_id, unit_id, badge_image_url) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([$user_id, $badge_name, $course_id, $unit_id, $badge_image_url]);
        
        if ($result) {
            createNotification($user_id, 'New Badge Earned!', "You earned the badge: $badge_name", 'badge');
        }
        
        return $result;
    }
}

// =============================================
// CERTIFICATE FUNCTIONS
// =============================================

if (!function_exists('generateCertificateNumber')) {
    function generateCertificateNumber() {
        return 'BSA-' . date('Y') . '-' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generateVerificationCode')) {
    function generateVerificationCode() {
        return strtoupper(bin2hex(random_bytes(6)));
    }
}

if (!function_exists('getUserCertificates')) {
    function getUserCertificates($user_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.*, cr.title as course_title
            FROM certificates c
            JOIN courses cr ON c.course_id = cr.id
            WHERE c.user_id = ?
            ORDER BY c.issue_date DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

// =============================================
// UNIT TEST FUNCTIONS
// =============================================

if (!function_exists('getRandomUnitQuestions')) {
    function getRandomUnitQuestions($unit_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, question_text, option_a, option_b, option_c, option_d, points
            FROM unit_questions 
            WHERE unit_id = ?
            ORDER BY RAND()
            LIMIT 15
        ");
        $stmt->execute([$unit_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('checkUnitTestAnswers')) {
    function checkUnitTestAnswers($unit_id, $user_answers) {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT id, correct_answer, points, explanation 
            FROM unit_questions 
            WHERE unit_id = ?
        ");
        $stmt->execute([$unit_id]);
        $questions = $stmt->fetchAll();
        
        $total_points = 0;
        $earned_points = 0;
        $results = [];
        
        foreach ($questions as $q) {
            $total_points += $q['points'];
            $user_answer = $user_answers[$q['id']] ?? null;
            $is_correct = ($user_answer === $q['correct_answer']);
            
            if ($is_correct) {
                $earned_points += $q['points'];
            }
            
            $results[] = [
                'question_id' => $q['id'],
                'question_text' => $q['question_text'],
                'user_answer' => $user_answer,
                'correct_answer' => $q['correct_answer'],
                'is_correct' => $is_correct,
                'explanation' => $q['explanation']
            ];
        }
        
        $score_percent = ($total_points > 0) ? round(($earned_points / $total_points) * 100) : 0;
        $passed = ($score_percent >= UNIT_TEST_PASSING_SCORE);
        
        return [
            'score' => $score_percent,
            'passed' => $passed,
            'total_points' => $total_points,
            'earned_points' => $earned_points,
            'results' => $results
        ];
    }
}

// =============================================
// DATE & TIME FUNCTIONS
// =============================================

if (!function_exists('formatDate')) {
    function formatDate($timestamp, $format = 'M d, Y') {
        if (!$timestamp) return 'N/A';
        return date($format, strtotime($timestamp));
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime($timestamp, $format = 'M d, Y H:i') {
        if (!$timestamp) return 'N/A';
        return date($format, strtotime($timestamp));
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($timestamp) {
        if (!$timestamp) return 'Unknown';
        
        $time_ago = strtotime($timestamp);
        $current_time = time();
        $time_difference = $current_time - $time_ago;
        
        $minutes = round($time_difference / 60);
        $hours = round($time_difference / 3600);
        $days = round($time_difference / 86400);
        
        if ($time_difference <= 60) {
            return "Just now";
        } elseif ($minutes <= 60) {
            return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
        } elseif ($hours <= 24) {
            return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
        } elseif ($days <= 7) {
            return ($days == 1) ? "yesterday" : "$days days ago";
        } else {
            return date('M d, Y', strtotime($timestamp));
        }
    }
}

// =============================================
// FILE UPLOAD FUNCTIONS
// =============================================

if (!function_exists('uploadFile')) {
    function uploadFile($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'], $max_size = 2097152) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload failed'];
        }
        
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'File too large. Max size: ' . ($max_size / 1048576) . 'MB'];
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types)];
        }
        
        $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $destination = $target_dir . $new_filename;
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'filename' => $new_filename, 'path' => $destination];
        }
        
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

// =============================================
// MISC UTILITY FUNCTIONS
// =============================================

if (!function_exists('debug')) {
    function debug($data, $die = false) {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            echo '<pre style="background: #f4f4f4; padding: 15px; border: 1px solid #ddd; margin: 10px; border-radius: 5px; overflow: auto;">';
            print_r($data);
            echo '</pre>';
            if ($die) die();
        }
    }
}

if (!function_exists('getRoleBadge')) {
    function getRoleBadge($role) {
        switch ($role) {
            case 'super_admin':
                return '<span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Super Admin</span>';
            case 'sub_admin':
                return '<span style="background: #3498db; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Sub Admin</span>';
            default:
                return '<span style="background: #95a5a6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">User</span>';
        }
    }
}

if (!function_exists('getLifetimeFreeBadge')) {
    function getLifetimeFreeBadge($user_id = null) {
        if (hasLifetimeFree($user_id)) {
            return '<span class="badge-gold" style="background: #F39C12; color: #2C3E50; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block;">🎁 Lifetime Free</span>';
        }
        return '';
    }
}

if (!function_exists('emailExists')) {
    function emailExists($email) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
}

if (!function_exists('phoneExists')) {
    function phoneExists($phone) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch() !== false;
    }
}

if (!function_exists('getCourseById')) {
    function getCourseById($course_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        return $stmt->fetch();
    }
}

if (!function_exists('getUnitById')) {
    function getUnitById($unit_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM course_units WHERE id = ?");
        $stmt->execute([$unit_id]);
        return $stmt->fetch();
    }
}

if (!function_exists('areAllUnitsCompleted')) {
    function areAllUnitsCompleted($user_id, $course_id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as total_units,
                   SUM(CASE WHEN upp.status = 'completed' THEN 1 ELSE 0 END) as completed_units
            FROM course_units cu
            LEFT JOIN user_unit_progress upp ON cu.id = upp.unit_id AND upp.user_id = ?
            WHERE cu.course_id = ?
        ");
        $stmt->execute([$user_id, $course_id]);
        $result = $stmt->fetch();
        
        return $result && $result['total_units'] > 0 && $result['total_units'] == $result['completed_units'];
    }
}
?>