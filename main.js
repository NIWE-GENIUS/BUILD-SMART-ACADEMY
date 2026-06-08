// assets/js/main.js
// BUILD SMART ACADEMY - Main JavaScript File

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    console.log('BUILD SMART ACADEMY - Ready');
    
    // Initialize all components
    initLoader();
    initMobileMenu();
    initNotifications();
    initFormValidation();
});

// Loader
function initLoader() {
    const loader = document.getElementById('loader');
    if (loader) {
        setTimeout(() => {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }, 500);
    }
}

// Mobile Menu
function initMobileMenu() {
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    
    if (mobileBtn && navLinks) {
        mobileBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            if (navLinks.style.display === 'flex') {
                navLinks.style.display = 'none';
            } else {
                navLinks.style.display = 'flex';
                navLinks.style.flexDirection = 'column';
                navLinks.style.position = 'absolute';
                navLinks.style.top = '70px';
                navLinks.style.left = '0';
                navLinks.style.right = '0';
                navLinks.style.background = 'white';
                navLinks.style.padding = '20px';
                navLinks.style.boxShadow = '0 5px 20px rgba(0,0,0,0.1)';
                navLinks.style.zIndex = '999';
            }
        });
    }
}

// Notifications
function initNotifications() {
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notif => {
        setTimeout(() => {
            notif.style.opacity = '0';
            setTimeout(() => {
                notif.remove();
            }, 500);
        }, 5000);
    });
}

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#e74c3c';
                    showError(input, 'This field is required');
                } else {
                    input.style.borderColor = '#ddd';
                    hideError(input);
                }
                
                // Email validation
                if (input.type === 'email' && input.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value)) {
                        isValid = false;
                        input.style.borderColor = '#e74c3c';
                        showError(input, 'Please enter a valid email address');
                    }
                }
                
                // Phone validation for Rwanda
                if (input.id === 'phone' && input.value.trim()) {
                    const phoneRegex = /^\+250[0-9]{9}$/;
                    if (!phoneRegex.test(input.value)) {
                        isValid = false;
                        input.style.borderColor = '#e74c3c';
                        showError(input, 'Phone must be in format +250XXXXXXXXX');
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

function showError(input, message) {
    let errorDiv = input.parentElement.querySelector('.error-message');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '5px';
        input.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function hideError(input) {
    const errorDiv = input.parentElement.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Toast notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    toast.style.position = 'fixed';
    toast.style.bottom = '20px';
    toast.style.right = '20px';
    toast.style.backgroundColor = type === 'success' ? '#27ae60' : '#e74c3c';
    toast.style.color = 'white';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '8px';
    toast.style.zIndex = '9999';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '10px';
    toast.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.remove();
        }, 500);
    }, 3000);
}