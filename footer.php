<?php
// includes/footer.php
// Website Footer - Loads after page content
?>
<footer style="background: var(--dark); color: white; padding: 60px 0 30px; margin-top: 50px;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
            <!-- About Column -->
            <div>
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;"><?php echo SITE_NAME; ?></h3>
                <p style="color: #aaa; line-height: 1.6;">Empowering Quantity Surveyors for the Future of Construction. Professional online courses, certificates, and career development.</p>
                <div style="margin-top: 20px; display: flex; gap: 15px;">
                    <a href="#" style="color: white; font-size: 20px; transition: var(--transition);"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="color: white; font-size: 20px; transition: var(--transition);"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: white; font-size: 20px; transition: var(--transition);"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" style="color: white; font-size: 20px; transition: var(--transition);"><i class="fab fa-youtube"></i></a>
                    <a href="https://wa.me/250793000960" style="color: white; font-size: 20px; transition: var(--transition);"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <!-- Quick Links Column -->
            <div>
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;">Quick Links</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>" style="color: #aaa; text-decoration: none; transition: var(--transition);">Home</a></li>
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>courses/" style="color: #aaa; text-decoration: none; transition: var(--transition);">Courses</a></li>
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>forum/" style="color: #aaa; text-decoration: none; transition: var(--transition);">Forum</a></li>
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>events/" style="color: #aaa; text-decoration: none; transition: var(--transition);">Events</a></li>
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>blog/" style="color: #aaa; text-decoration: none; transition: var(--transition);">Blog</a></li>
                </ul>
            </div>
            
            <!-- Support Column -->
            <div>
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;">Support</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>contact.php" style="color: #aaa; text-decoration: none; transition: var(--transition);">Contact Us</a></li>
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>faq.php" style="color: #aaa; text-decoration: none; transition: var(--transition);">FAQ</a></li>
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>privacy-policy.php" style="color: #aaa; text-decoration: none; transition: var(--transition);">Privacy Policy</a></li>
                    <li style="margin-bottom: 10px;"><a href="<?php echo SITE_URL; ?>terms.php" style="color: #aaa; text-decoration: none; transition: var(--transition);">Terms of Service</a></li>
                </ul>
            </div>
            
            <!-- Contact Column -->
            <div>
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;">Contact Info</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <i class="fas fa-envelope" style="color: var(--orange);"></i>
                        <span style="color: #aaa;">irutabyosephilemon78@gmail.com</span>
                    </li>
                    <li style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <i class="fas fa-phone-alt" style="color: var(--orange);"></i>
                        <span style="color: #aaa;">+250 793 000 960</span>
                    </li>
                    <li style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <i class="fab fa-whatsapp" style="color: #25d366;"></i>
                        <span style="color: #aaa;">+250 793 000 960</span>
                    </li>
                    <li style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <i class="fas fa-map-marker-alt" style="color: var(--orange);"></i>
                        <span style="color: #aaa;">Kigali, Rwanda</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div style="border-top: 1px solid #444; padding-top: 30px; text-align: center; color: #888; font-size: 14px;">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p style="margin-top: 10px;">Designed and developed with <i class="fas fa-heart" style="color: var(--orange);"></i> for Quantity Surveyors</p>
        </div>
    </div>
</footer>

<script>
    // Add active class to current nav link
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-links a').forEach(link => {
        const href = link.getAttribute('href');
        if (href && (currentPath === href || (currentPath === '/' && href === '<?php echo SITE_URL; ?>') || 
            (currentPath.includes(href) && href !== '#'))) {
            link.style.color = 'var(--orange)';
        }
    });
    
    // Smooth scroll to top button (optional)
    const scrollTopBtn = document.createElement('button');
    scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollTopBtn.style.position = 'fixed';
    scrollTopBtn.style.bottom = '30px';
    scrollTopBtn.style.right = '30px';
    scrollTopBtn.style.width = '50px';
    scrollTopBtn.style.height = '50px';
    scrollTopBtn.style.borderRadius = '50%';
    scrollTopBtn.style.background = 'var(--orange)';
    scrollTopBtn.style.color = 'white';
    scrollTopBtn.style.border = 'none';
    scrollTopBtn.style.cursor = 'pointer';
    scrollTopBtn.style.display = 'none';
    scrollTopBtn.style.zIndex = '999';
    scrollTopBtn.style.boxShadow = 'var(--shadow-md)';
    scrollTopBtn.style.transition = 'var(--transition)';
    
    scrollTopBtn.onclick = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    document.body.appendChild(scrollTopBtn);
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollTopBtn.style.display = 'block';
        } else {
            scrollTopBtn.style.display = 'none';
        }
    });
</script>
</body>
</html>