<?php
// includes/navbar.php
// REGULAR USER / GUEST NAVIGATION BAR
// Light, modern, educational-focused

// Get unread notification count for logged in users
$unread_count = 0;
if (isLoggedIn() && !isAdmin()) {
    $unread_count = getUnreadNotificationCount(getCurrentUserId());
}
?>
<nav class="user-navbar">
    <div class="container">
        <div class="navbar-brand">
            <a href="<?php echo SITE_URL; ?>" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="logo-text">BUILD SMART ACADEMY</span>
            </a>
        </div>
        
        <button class="mobile-toggle" id="mobileToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="navbar-menu" id="navbarMenu">
            <ul class="nav-links">
                <li><a href="<?php echo SITE_URL; ?>" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>courses/" class="nav-link"><i class="fas fa-book-open"></i> Courses</a></li>
                <li><a href="<?php echo SITE_URL; ?>forum/" class="nav-link"><i class="fas fa-comments"></i> Forum</a></li>
                <li><a href="<?php echo SITE_URL; ?>events/" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="<?php echo SITE_URL; ?>blog/" class="nav-link"><i class="fas fa-newspaper"></i> Blog</a></li>
                <li><a href="<?php echo SITE_URL; ?>contact.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a></li>
            </ul>
            
            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <div class="user-dropdown">
                        <button class="user-btn" id="userDropdownBtn">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <a href="<?php echo SITE_URL; ?>dashboard/"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            <a href="<?php echo SITE_URL; ?>dashboard/my-courses.php"><i class="fas fa-book"></i> My Courses</a>
                            <a href="<?php echo SITE_URL; ?>dashboard/my-certificates.php"><i class="fas fa-certificate"></i> Certificates</a>
                            <a href="<?php echo SITE_URL; ?>dashboard/my-badges.php"><i class="fas fa-medal"></i> Badges</a>
                            <a href="<?php echo SITE_URL; ?>dashboard/notifications.php">
                                <i class="fas fa-bell"></i> Notifications
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo SITE_URL; ?>dashboard/edit-profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo SITE_URL; ?>auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>auth/login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?php echo SITE_URL; ?>auth/register.php" class="btn-register"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
    /* ============================================ */
    /* REGULAR USER NAVBAR STYLES */
    /* ============================================ */
    .user-navbar {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255,107,53,0.1);
    }
    
    .user-navbar .container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 70px;
    }
    
    /* Brand */
    .navbar-brand .logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    
    .logo-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #FF6B35, #1A5F7A);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }
    
    .logo-icon i {
        font-size: 20px;
        color: white;
    }
    
    .logo-text {
        font-size: 1.2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #FF6B35, #1A5F7A);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    .navbar-brand:hover .logo-icon {
        transform: scale(1.05);
    }
    
    /* Navigation Links */
    .navbar-menu {
        display: flex;
        align-items: center;
        gap: 30px;
    }
    
    .nav-links {
        display: flex;
        gap: 25px;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .nav-link {
        text-decoration: none;
        color: #2C3E50;
        font-weight: 500;
        font-size: 0.95rem;
        padding: 8px 0;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .nav-link i {
        margin-right: 6px;
        font-size: 0.9rem;
        color: #FF6B35;
    }
    
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #FF6B35, #1A5F7A);
        transition: width 0.3s ease;
        border-radius: 2px;
    }
    
    .nav-link:hover::after {
        width: 100%;
    }
    
    .nav-link:hover {
        color: #FF6B35;
    }
    
    /* Action Buttons */
    .nav-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .btn-login {
        padding: 8px 20px;
        background: transparent;
        border: 2px solid #FF6B35;
        color: #FF6B35;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .btn-login:hover {
        background: #FF6B35;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255,107,53,0.3);
    }
    
    .btn-register {
        padding: 8px 20px;
        background: linear-gradient(135deg, #FF6B35, #FF8C5A);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(255,107,53,0.2);
    }
    
    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255,107,53,0.4);
    }
    
    /* User Dropdown */
    .user-dropdown {
        position: relative;
    }
    
    .user-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f0f2f5;
        border: none;
        padding: 6px 15px;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .user-btn:hover {
        background: #e8eaed;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #FF6B35, #1A5F7A);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
    }
    
    .user-name {
        font-weight: 500;
        color: #2C3E50;
        font-size: 0.9rem;
    }
    
    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 10px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        min-width: 220px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
    }
    
    .dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        text-decoration: none;
        color: #2C3E50;
        font-size: 0.85rem;
        transition: background 0.3s ease;
    }
    
    .dropdown-menu a i {
        width: 20px;
        color: #FF6B35;
    }
    
    .dropdown-menu a:hover {
        background: #f8f9fa;
    }
    
    .dropdown-divider {
        height: 1px;
        background: #e0e0e0;
        margin: 5px 0;
    }
    
    .logout-link {
        color: #E74C3C !important;
    }
    
    .logout-link i {
        color: #E74C3C !important;
    }
    
    .badge {
        background: #FF6B35;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 10px;
        margin-left: auto;
    }
    
    /* Mobile Toggle */
    .mobile-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #2C3E50;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .mobile-toggle {
            display: block;
        }
        
        .navbar-menu {
            position: absolute;
            top: 70px;
            left: 0;
            right: 0;
            background: white;
            flex-direction: column;
            padding: 20px;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .navbar-menu.active {
            transform: translateX(0);
        }
        
        .nav-links {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .nav-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .btn-login, .btn-register {
            width: 100%;
            text-align: center;
        }
        
        .user-dropdown {
            width: 100%;
        }
        
        .user-btn {
            width: 100%;
            justify-content: center;
        }
        
        .dropdown-menu {
            position: static;
            box-shadow: none;
            margin-top: 10px;
            opacity: 1;
            visibility: visible;
            transform: none;
            display: none;
        }
        
        .dropdown-menu.show {
            display: block;
        }
    }
</style>

<script>
    // User dropdown toggle
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userDropdownBtn) {
        userDropdownBtn.addEventListener('click', () => {
            userDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!userDropdownBtn.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
    }
    
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const navbarMenu = document.getElementById('navbarMenu');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            navbarMenu.classList.toggle('active');
        });
    }
</script>