<?php
// dashboard/edit-profile.php
// User Profile Edit Page

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$user_id = getCurrentUserId();
$user = getUserById($user_id);

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        $data = [
            'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
            'professional_title' => sanitizeInput($_POST['professional_title'] ?? ''),
            'years_experience' => intval($_POST['years_experience'] ?? 0),
            'country' => sanitizeInput($_POST['country'] ?? 'Rwanda'),
            'bio' => sanitizeInput($_POST['bio'] ?? '')
        ];
        
        if (empty($data['full_name'])) {
            $error = 'Full name is required.';
        } else {
            if (updateUserProfile($user_id, $data)) {
                $success = 'Profile updated successfully!';
                // Refresh user data
                $user = getUserById($user_id);
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $upload_result = uploadFile(
        $_FILES['profile_picture'],
        PROFILE_PICTURE_PATH,
        ['jpg', 'jpeg', 'png', 'gif'],
        2097152 // 2MB
    );
    
    if ($upload_result['success']) {
        // Delete old profile picture if not default
        if ($user['profile_picture'] && $user['profile_picture'] !== 'default-avatar.png') {
            $old_file = PROFILE_PICTURE_PATH . $user['profile_picture'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        updateProfilePicture($user_id, $upload_result['filename']);
        $success = 'Profile picture updated successfully!';
        $user = getUserById($user_id);
    } else {
        $error = $upload_result['message'];
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'Edit Profile';
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
        .edit-profile-container {
            max-width: 800px;
            margin: 30px auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid var(--orange);
        }
        
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin: 0 auto 15px;
        }
        
        .upload-form {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .upload-btn {
            background: var(--blue);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-save {
            background: var(--orange);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #27ae60;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .completion-status {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .completion-bar {
            background: #ddd;
            border-radius: 10px;
            height: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .completion-fill {
            background: var(--orange);
            height: 100%;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container">
        <div class="edit-profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
                    <p>Update your personal information</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Profile Completion Status -->
                <div class="completion-status">
                    <strong>Profile Completion Status</strong>
                    <div class="completion-bar">
                        <div class="completion-fill" style="width: <?php echo getProfileCompletionPercentage($user_id); ?>%"></div>
                    </div>
                    <p class="info-text" style="margin-top: 8px;">
                        Complete your profile to unlock all features
                    </p>
                </div>
                
                <!-- Profile Picture Upload -->
                <div class="upload-form">
                    <h3>Profile Picture</h3>
                    <?php if ($user['profile_picture'] && $user['profile_picture'] !== 'default-avatar.png'): ?>
                        <img src="<?php echo SITE_URL; ?>uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" alt="Profile" class="profile-avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" style="display: none;">
                        <label for="profile_picture" class="upload-btn">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                    </form>
                    <p class="info-text">Accepted formats: JPG, PNG, GIF. Max size: 2MB</p>
                </div>
                
                <!-- Profile Edit Form -->
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f5f5f5;">
                        <p class="info-text">Email cannot be changed. Contact support for assistance.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled style="background: #f5f5f5;">
                        <p class="info-text">Phone number cannot be changed. Contact support for assistance.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="professional_title">Professional Title *</label>
                        <select id="professional_title" name="professional_title" required>
                            <option value="">Select your title</option>
                            <option value="Student" <?php echo $user['professional_title'] == 'Student' ? 'selected' : ''; ?>>Student</option>
                            <option value="Junior Quantity Surveyor" <?php echo $user['professional_title'] == 'Junior Quantity Surveyor' ? 'selected' : ''; ?>>Junior Quantity Surveyor</option>
                            <option value="Quantity Surveyor" <?php echo $user['professional_title'] == 'Quantity Surveyor' ? 'selected' : ''; ?>>Quantity Surveyor</option>
                            <option value="Senior Quantity Surveyor" <?php echo $user['professional_title'] == 'Senior Quantity Surveyor' ? 'selected' : ''; ?>>Senior Quantity Surveyor</option>
                            <option value="Estimator" <?php echo $user['professional_title'] == 'Estimator' ? 'selected' : ''; ?>>Estimator</option>
                            <option value="Project Manager" <?php echo $user['professional_title'] == 'Project Manager' ? 'selected' : ''; ?>>Project Manager</option>
                            <option value="Construction Manager" <?php echo $user['professional_title'] == 'Construction Manager' ? 'selected' : ''; ?>>Construction Manager</option>
                            <option value="Consultant" <?php echo $user['professional_title'] == 'Consultant' ? 'selected' : ''; ?>>Consultant</option>
                            <option value="Other" <?php echo $user['professional_title'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="years_experience">Years of Experience *</label>
                        <input type="number" id="years_experience" name="years_experience" min="0" max="50" required
                               value="<?php echo $user['years_experience']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Country *</label>
                        <select id="country" name="country" required>
                            <option value="Rwanda" <?php echo $user['country'] == 'Rwanda' ? 'selected' : ''; ?>>Rwanda</option>
                            <option value="Uganda" <?php echo $user['country'] == 'Uganda' ? 'selected' : ''; ?>>Uganda</option>
                            <option value="Kenya" <?php echo $user['country'] == 'Kenya' ? 'selected' : ''; ?>>Kenya</option>
                            <option value="Tanzania" <?php echo $user['country'] == 'Tanzania' ? 'selected' : ''; ?>>Tanzania</option>
                            <option value="Burundi" <?php echo $user['country'] == 'Burundi' ? 'selected' : ''; ?>>Burundi</option>
                            <option value="DRC" <?php echo $user['country'] == 'DRC' ? 'selected' : ''; ?>>DRC</option>
                            <option value="Other" <?php echo $user['country'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio / Professional Summary</label>
                        <textarea id="bio" name="bio" placeholder="Tell us about your professional background..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit profile picture on file select
        document.getElementById('profile_picture').addEventListener('change', function() {
            if (this.files.length > 0) {
                this.form.submit();
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>