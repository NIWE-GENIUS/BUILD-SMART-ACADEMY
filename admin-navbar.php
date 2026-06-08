<?php
// includes/admin-navbar.php
// ADMIN / SUB ADMIN NAVIGATION BAR
// Dark, professional, management-focused

// Only visible to admins
if (!isAdmin()) {
    return;
}

$is_super_admin = isSuperAdmin();
$unread_messages = 0;

// Get unread messages count
if ($is_super_admin) {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as total FROM chat_messages WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([getCurrentUserId()]);
    $unread_messages = $stmt->fetch()['total'] ?? 0;
}
?>
<nav class="admin-navbar">
    <div class="admin-container">
        <div class="admin-brand">
            <a href="<?php echo SITE_URL; ?>admin/" class="admin-logo">
                <div class="admin-logo-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="admin-logo-text">
                    <span class="admin-title">Admin Portal</span>
                    <span class="admin-subtitle"><?php echo $is_super_admin ? 'Super Administrator' : 'Sub Administrator'; ?></span>
                </div>
            </a>
        </div>
        
        <button class="admin-mobile-toggle" id="adminMobileToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="admin-menu" id="adminMenu">
            <ul class="admin-nav-links">
                <li><a href="<?php echo SITE_URL; ?>admin/" class="admin-nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/courses.php" class="admin-nav-link"><i class="fas fa-book"></i> Courses</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/users.php" class="admin-nav-link"><i class="fas fa-users"></i> Users</a></li>
                <?php if ($is_super_admin): ?>
                    <li><a href="<?php echo SITE_URL; ?>admin/sub-admins.php" class="admin-nav-link"><i class="fas fa-user-shield"></i> Sub Admins</a></li>
                <?php endif; ?>
                <li><a href="<?php echo SITE_URL; ?>admin/announcements.php" class="admin-nav-link"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/payments.php" class="admin-nav-link"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="<?php echo SITE_URL; ?>admin/reports.php" class="admin-nav-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php if ($is_super_admin): ?>
                    <li><a href="<?php echo SITE_URL; ?>admin/database-viewer.php" class="admin-nav-link"><i class="fas fa-database"></i> Database</a></li>
                    <li><a href="<?php echo SITE_URL; ?>admin/settings.php" class="admin-nav-link"><i class="fas fa-cog"></i> Settings</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="admin-actions">
                <!-- Live Chat Button -->
                <button class="admin-chat-btn" id="adminChatBtn">
                    <i class="fas fa-comment-dots"></i>
                    <span>Live Chat</span>
                    <?php if ($unread_messages > 0): ?>
                        <span class="chat-badge"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </button>
                
                <!-- Admin Dropdown -->
                <div class="admin-dropdown">
                    <button class="admin-user-btn" id="adminDropdownBtn">
                        <div class="admin-avatar">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <span class="admin-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="admin-dropdown-menu" id="adminDropdownMenu">
                        <a href="<?php echo SITE_URL; ?>dashboard/"><i class="fas fa-arrow-left"></i> Back to Site</a>
                        <a href="<?php echo SITE_URL; ?>admin/profile.php"><i class="fas fa-user-edit"></i> Admin Profile</a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo SITE_URL; ?>auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Chat Widget for Admin -->
<div class="admin-chat-widget" id="adminChatWidget">
    <div class="chat-header">
        <span><i class="fas fa-comment-dots"></i> Live Support Chat</span>
        <button id="closeChatBtn">&times;</button>
    </div>
    <div class="chat-conversations" id="chatConversations"></div>
    <div class="chat-messages-area" id="chatMessagesArea">
        <div class="chat-placeholder">Select a conversation to start chatting</div>
    </div>
    <div class="chat-input-area">
        <input type="text" id="chatInput" placeholder="Type your message...">
        <button id="sendChatMsgBtn"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<style>
    /* ============================================ */
    /* ADMIN NAVBAR STYLES - DARK THEME */
    /* ============================================ */
    .admin-navbar {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid rgba(255,107,53,0.3);
    }
    
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 65px;
    }
    
    /* Admin Brand */
    .admin-brand .admin-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
    }
    
    .admin-logo-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #FF6B35, #FF8C5A);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(255,107,53,0.4); }
        50% { box-shadow: 0 0 0 5px rgba(255,107,53,0); }
    }
    
    .admin-logo-icon i {
        font-size: 20px;
        color: white;
    }
    
    .admin-logo-text {
        display: flex;
        flex-direction: column;
    }
    
    .admin-title {
        font-size: 1rem;
        font-weight: 700;
        color: white;
        letter-spacing: 0.5px;
    }
    
    .admin-subtitle {
        font-size: 0.7rem;
        color: #FF6B35;
        font-weight: 500;
    }
    
    /* Admin Navigation Links */
    .admin-menu {
        display: flex;
        align-items: center;
        gap: 30px;
    }
    
    .admin-nav-links {
        display: flex;
        gap: 8px;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .admin-nav-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        text-decoration: none;
        color: #a0a0b0;
        font-weight: 500;
        font-size: 0.85rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .admin-nav-link i {
        font-size: 0.9rem;
    }
    
    .admin-nav-link:hover {
        background: rgba(255,107,53,0.15);
        color: #FF6B35;
    }
    
    .admin-nav-link.active {
        background: #FF6B35;
        color: white;
    }
    
    /* Admin Actions */
    .admin-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-chat-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,107,53,0.15);
        border: none;
        padding: 8px 18px;
        border-radius: 50px;
        color: #FF6B35;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .admin-chat-btn:hover {
        background: #FF6B35;
        color: white;
    }
    
    .chat-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #E74C3C;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 10px;
    }
    
    /* Admin Dropdown */
    .admin-dropdown {
        position: relative;
    }
    
    .admin-user-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        padding: 6px 15px;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: white;
    }
    
    .admin-user-btn:hover {
        background: rgba(255,107,53,0.2);
        border-color: #FF6B35;
    }
    
    .admin-avatar {
        width: 30px;
        height: 30px;
        background: linear-gradient(135deg, #FF6B35, #FF8C5A);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    
    .admin-name {
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .admin-dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 10px;
        background: #1a1a2e;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        min-width: 200px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
        border: 1px solid rgba(255,107,53,0.2);
    }
    
    .admin-dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .admin-dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        text-decoration: none;
        color: #a0a0b0;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }
    
    .admin-dropdown-menu a i {
        width: 18px;
    }
    
    .admin-dropdown-menu a:hover {
        background: rgba(255,107,53,0.1);
        color: #FF6B35;
    }
    
    /* Chat Widget */
    .admin-chat-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 380px;
        height: 500px;
        background: #1a1a2e;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        display: flex;
        flex-direction: column;
        z-index: 1001;
        transform: translateY(20px);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,107,53,0.2);
    }
    
    .admin-chat-widget.active {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
    }
    
    .chat-header {
        background: linear-gradient(135deg, #FF6B35, #FF8C5A);
        padding: 15px;
        border-radius: 15px 15px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        font-weight: 600;
    }
    
    .chat-header button {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
    }
    
    .chat-conversations {
        max-height: 120px;
        overflow-y: auto;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        background: #0f0f1a;
    }
    
    .chat-messages-area {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        background: #16213e;
    }
    
    .chat-placeholder {
        text-align: center;
        color: #666;
        padding: 40px;
        font-size: 13px;
    }
    
    .chat-input-area {
        display: flex;
        padding: 12px;
        gap: 10px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .chat-input-area input {
        flex: 1;
        padding: 10px;
        background: #0f0f1a;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 25px;
        color: white;
        outline: none;
    }
    
    .chat-input-area button {
        background: #FF6B35;
        border: none;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .chat-input-area button:hover {
        background: #FF8C5A;
        transform: scale(1.05);
    }
    
    .chat-message {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
    }
    
    .chat-message.sent {
        align-items: flex-end;
    }
    
    .chat-message.received {
        align-items: flex-start;
    }
    
    .message-bubble {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 18px;
        font-size: 13px;
    }
    
    .chat-message.sent .message-bubble {
        background: #FF6B35;
        color: white;
    }
    
    .chat-message.received .message-bubble {
        background: #0f0f1a;
        color: #a0a0b0;
    }
    
    .message-time {
        font-size: 10px;
        color: #666;
        margin-top: 5px;
    }
    
    /* Mobile Toggle */
    .admin-mobile-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: white;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .admin-nav-links {
            gap: 5px;
        }
        .admin-nav-link {
            padding: 8px 12px;
            font-size: 0.75rem;
        }
    }
    
    @media (max-width: 900px) {
        .admin-mobile-toggle {
            display: block;
        }
        
        .admin-menu {
            position: absolute;
            top: 65px;
            left: 0;
            right: 0;
            background: #1a1a2e;
            flex-direction: column;
            padding: 20px;
            gap: 20px;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
            z-index: 999;
            border-bottom: 1px solid rgba(255,107,53,0.3);
        }
        
        .admin-menu.active {
            transform: translateY(0);
        }
        
        .admin-nav-links {
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        
        .admin-nav-link {
            width: 100%;
            justify-content: center;
        }
        
        .admin-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .admin-chat-btn {
            width: 100%;
            justify-content: center;
        }
        
        .admin-dropdown {
            width: 100%;
        }
        
        .admin-user-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    // Admin dropdown toggle
    const adminDropdownBtn = document.getElementById('adminDropdownBtn');
    const adminDropdownMenu = document.getElementById('adminDropdownMenu');
    
    if (adminDropdownBtn) {
        adminDropdownBtn.addEventListener('click', () => {
            adminDropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', (e) => {
            if (!adminDropdownBtn.contains(e.target)) {
                adminDropdownMenu.classList.remove('show');
            }
        });
    }
    
    // Mobile menu toggle
    const adminMobileToggle = document.getElementById('adminMobileToggle');
    const adminMenu = document.getElementById('adminMenu');
    
    if (adminMobileToggle) {
        adminMobileToggle.addEventListener('click', () => {
            adminMenu.classList.toggle('active');
        });
    }
    
    // Chat widget
    const adminChatBtn = document.getElementById('adminChatBtn');
    const adminChatWidget = document.getElementById('adminChatWidget');
    const closeChatBtn = document.getElementById('closeChatBtn');
    
    if (adminChatBtn) {
        adminChatBtn.addEventListener('click', () => {
            adminChatWidget.classList.toggle('active');
        });
    }
    
    if (closeChatBtn) {
        closeChatBtn.addEventListener('click', () => {
            adminChatWidget.classList.remove('active');
        });
    }
</script>