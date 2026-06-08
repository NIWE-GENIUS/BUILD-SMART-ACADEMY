<?php
// includes/header.php
// Professional Website Header - Loads before page content
// Version: 3.0 - Interactive & SEO Optimized

// Set default page title if not defined
if (!isset($page_title)) {
    $page_title = 'Home';
}

// Set default meta description if not defined
if (!isset($meta_description)) {
    $meta_description = SITE_TAGLINE . ' Professional online courses, certificates, and career development for quantity surveyors in Africa.';
}

// Set default meta keywords if not defined
if (!isset($meta_keywords)) {
    $meta_keywords = 'quantity surveying, construction management, cost estimation, online courses, QS training, building construction, quantity surveyor certification, Rwanda, East Africa';
}

// Set canonical URL
$canonical_url = SITE_URL . ltrim($_SERVER['REQUEST_URI'], '/');

// Set og image
$og_image = isset($og_image) ? $og_image : SITE_URL . 'assets/images/og-image.jpg';

// Set current year for copyright
$current_year = date('Y');

// Check if user has unread notifications for dynamic badge
$unread_count = 0;
if (isLoggedIn()) {
    $unread_count = getUnreadNotificationCount(getCurrentUserId());
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <!-- ============================================ -->
    <!-- Basic Meta Tags -->
    <!-- ============================================ -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B35">
    <meta name="color-scheme" content="light">
    <meta name="msapplication-TileColor" content="#FF6B35">
    <meta name="msapplication-TileImage" content="<?php echo SITE_URL; ?>assets/images/ms-icon-144x144.png">
    
    <!-- ============================================ -->
    <!-- SEO Meta Tags -->
    <!-- ============================================ -->
    <title><?php echo SITE_NAME; ?> | <?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <meta name="author" content="QS Philemon IRUTABYOSE">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="revisit-after" content="7 days">
    <link rel="canonical" href="<?php echo $canonical_url; ?>">
    
    <!-- ============================================ -->
    <!-- Open Graph / Facebook Meta Tags -->
    <!-- ============================================ -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $canonical_url; ?>">
    <meta property="og:title" content="<?php echo SITE_NAME; ?> | <?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:image" content="<?php echo $og_image; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    <meta property="og:locale" content="en_RW">
    
    <!-- ============================================ -->
    <!-- Twitter Card Meta Tags -->
    <!-- ============================================ -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo SITE_NAME; ?> | <?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo $og_image; ?>">
    <meta name="twitter:site" content="@BuildSmartAcademy">
    
    <!-- ============================================ -->
    <!-- Favicon & App Icons -->
    <!-- ============================================ -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo SITE_URL; ?>assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>assets/images/apple-touch-icon.png">
    <link rel="manifest" href="<?php echo SITE_URL; ?>assets/site.webmanifest">
    
    <!-- ============================================ -->
    <!-- Preload Critical Assets -->
    <!-- ============================================ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="preload" as="script" href="<?php echo SITE_URL; ?>assets/js/main.js">
    <link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- ============================================ -->
    <!-- Stylesheets -->
    <!-- ============================================ -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- ============================================ -->
    <!-- Structured Data / JSON-LD for SEO -->
    <!-- ============================================ -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "EducationalOrganization",
        "name": "<?php echo SITE_NAME; ?>",
        "url": "<?php echo SITE_URL; ?>",
        "logo": "<?php echo SITE_URL; ?>assets/images/logo.png",
        "description": "<?php echo htmlspecialchars($meta_description); ?>",
        "email": "irutabyosephilemon78@gmail.com",
        "telephone": "+250793000960",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Kigali",
            "addressCountry": "RW"
        },
        "sameAs": [
            "https://www.facebook.com/buildsmartacademy",
            "https://www.twitter.com/buildsmartacademy",
            "https://www.linkedin.com/school/buildsmartacademy",
            "https://www.youtube.com/c/buildsmartacademy"
        ]
    }
    </script>
    
    <!-- ============================================ -->
    <!-- Inline Critical CSS (Above Fold) -->
    <!-- ============================================ -->
    <style>
        /* Critical CSS - Above the fold styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: #f5f6fa;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        body.loaded {
            opacity: 1;
        }
        
        :root {
            --orange: #FF6B35;
            --blue: #1A5F7A;
            --dark: #2C3E50;
            --light: #ECF0F1;
            --white: #FFFFFF;
            --gray: #7F8C8D;
            --green: #27AE60;
            --red: #E74C3C;
            --yellow: #F39C12;
            --purple: #9B59B6;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-md: 0 5px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
            --transition: all 0.3s ease;
        }
        
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        /* Skip to content link for accessibility */
        .skip-to-content {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--orange);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            z-index: 9999;
            transition: top 0.3s;
        }
        
        .skip-to-content:focus {
            top: 0;
        }
        
        /* Loader */
        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        
        .loader-wrapper.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .loader {
            text-align: center;
        }
        
        .loader-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f0f0f0;
            border-top-color: var(--orange);
            border-right-color: var(--blue);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 15px;
        }
        
        .loader-text {
            color: var(--orange);
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Notification Toast */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9998;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .toast {
            padding: 14px 20px;
            border-radius: 12px;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-lg);
            animation: slideInRight 0.3s ease;
            min-width: 280px;
            max-width: 380px;
        }
        
        .toast-success { background: var(--green); }
        .toast-error { background: var(--red); }
        .toast-warning { background: var(--yellow); color: var(--dark); }
        .toast-info { background: var(--blue); }
        
        .toast i { font-size: 20px; }
        .toast-close {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        .toast-close:hover { opacity: 1; }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }
            .toast {
                min-width: auto;
                max-width: calc(100vw - 40px);
                padding: 12px 16px;
            }
        }
    </style>
    
    <!-- ============================================ -->
    <!-- Global Site Tag (gtag.js) - Google Analytics -->
    <!-- ============================================ -->
    <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'production'): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
    </script>
    <?php endif; ?>
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-to-content">Skip to main content</a>
    
    <!-- Loader -->
    <div class="loader-wrapper" id="loaderWrapper">
        <div class="loader">
            <div class="loader-spinner"></div>
            <div class="loader-text">BUILD SMART ACADEMY</div>
            <p style="font-size: 12px; color: #888; margin-top: 10px;">Loading...</p>
        </div>
    </div>
    
    <!-- Toast Container for Notifications -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- ============================================ -->
    <!-- Navigation Bar -->
    <!-- ============================================ -->
    <nav class="navbar" id="mainNavbar" style="background: white; box-shadow: var(--shadow-sm); position: sticky; top: 0; z-index: 1000; transition: var(--transition);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px;">
            <!-- Logo -->
            <a href="<?php echo SITE_URL; ?>" class="logo" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                <div class="logo-icon" style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--orange), var(--blue)); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-graduation-cap" style="color: white; font-size: 20px;"></i>
                </div>
                <span style="font-size: 1.3rem; font-weight: 800; background: linear-gradient(135deg, var(--orange), var(--blue)); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    BUILD SMART ACADEMY
                </span>
            </a>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu" style="display: none; background: none; border: none; font-size: 24px; cursor: pointer; color: var(--dark);">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Navigation Links -->
            <ul class="nav-links" id="navLinks" style="display: flex; gap: 28px; list-style: none; margin: 0; padding: 0;">
                <li><a href="<?php echo SITE_URL; ?>" class="nav-link" data-page="home"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>courses/" class="nav-link" data-page="courses"><i class="fas fa-book-open"></i> Courses</a></li>
                <li><a href="<?php echo SITE_URL; ?>forum/" class="nav-link" data-page="forum"><i class="fas fa-comments"></i> Forum</a></li>
                <li><a href="<?php echo SITE_URL; ?>events/" class="nav-link" data-page="events"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="<?php echo SITE_URL; ?>blog/" class="nav-link" data-page="blog"><i class="fas fa-newspaper"></i> Blog</a></li>
                <li><a href="<?php echo SITE_URL; ?>contact.php" class="nav-link" data-page="contact"><i class="fas fa-envelope"></i> Contact</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li>
                        <a href="<?php echo SITE_URL; ?>dashboard/" class="nav-link" data-page="dashboard">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-badge" style="background: var(--orange); color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; margin-left: 5px;"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>auth/logout.php" class="nav-link logout-link" style="color: var(--red);">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>auth/login.php" class="nav-link" data-page="login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>auth/register.php" class="btn-register" style="background: var(--orange); color: white; padding: 8px 22px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: var(--transition);"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <!-- Progress Bar for Page Loading -->
    <div id="progressBar" style="position: fixed; top: 0; left: 0; width: 0%; height: 3px; background: linear-gradient(90deg, var(--orange), var(--blue)); z-index: 10000; transition: width 0.3s ease; box-shadow: 0 0 5px var(--orange);"></div>
    
    <!-- Main Content Start -->
    <main id="main-content">