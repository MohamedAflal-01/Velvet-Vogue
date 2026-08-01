/**
 * Velvet Vogue - Customer Front-end JavaScript
 * Author: Antigravity AI
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Initialize Tooltips & Popovers
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 1. Image Gallery Thumbnail Switcher with fade-in animation & active border states
    const thumbnails = document.querySelectorAll('.thumbnail.img-thumbnail');
    const mainProductImg = document.getElementById('main-product-img');

    if (mainProductImg && thumbnails.length > 0) {
        // Set first thumbnail active by default
        thumbnails[0].classList.add('border-dark', 'border-2');

        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function () {
                // Remove active class from all thumbnails
                thumbnails.forEach(t => t.classList.remove('border-dark', 'border-2'));

                // Add active state to clicked thumbnail
                this.classList.add('border-dark', 'border-2');

                // Apply a smooth fade transition to the main image
                mainProductImg.style.opacity = 0;
                setTimeout(() => {
                    mainProductImg.src = this.src;
                    mainProductImg.style.opacity = 1;
                }, 150);
            });
        });
        // Set smooth transition styles
        mainProductImg.style.transition = 'opacity 0.2s ease-in-out';
    }

    // 2. Client-side Form Validations
    // Customer Sign Up Form Validation
    const signupForm = document.querySelector('form[action*="register.php"]');
    if (signupForm) {
        signupForm.addEventListener('submit', function (event) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            if (password && confirmPassword && password.value !== confirmPassword.value) {
                event.preventDefault();
                alert('Passwords do not match. Please verify and try again.');
                confirmPassword.focus();
            }
        });
    }

    // Contact Form Validation
    const contactForm = document.querySelector('form[action*="contact.php"]');
    if (contactForm) {
        contactForm.addEventListener('submit', function (event) {
            const email = document.querySelector('input[type="email"]');
            if (email && !validateEmail(email.value)) {
                event.preventDefault();
                alert('Please enter a valid email address.');
                email.focus();
            }
        });
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }

    // 3. Scroll to Top Utility
    const scrollBtn = document.createElement('button');
    scrollBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    scrollBtn.className = 'btn btn-dark btn-scroll-to-top shadow';
    Object.assign(scrollBtn.style, {
        position: 'fixed',
        bottom: '30px',
        right: '30px',
        width: '45px',
        height: '45px',
        borderRadius: '50%',
        display: 'none',
        zIndex: '1050',
        transition: 'all 0.3s ease',
        backgroundColor: '#fff',
        color: '#db2777',
        border: '1px solid #db2777'
    });
    document.body.appendChild(scrollBtn);

    window.addEventListener('scroll', function () {
        if (window.scrollY > 300) {
            scrollBtn.style.display = 'block';
        } else {
            scrollBtn.style.display = 'none';
        }
    });

    scrollBtn.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // 4. Subtle Micro-animations on Buttons
    const luxuryButtons = document.querySelectorAll('.btn-luxury, .btn-outline-luxury');
    luxuryButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 5px 15px rgba(219, 39, 119, 0.2)';
        });
        btn.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
        btn.style.transition = 'all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1)';
    });

    // 5. Toast Notification System
    function showToast(message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `luxury-toast toast-${type}`;
        toast.innerHTML = `
            <span>${message}</span>
            <button class="btn-close btn-close-white ms-2" style="font-size: 0.75rem;" onclick="this.parentElement.remove()"></button>
        `;

        container.appendChild(toast);

        // Remove toast from DOM after animation completes (3.1s covers both slide-in and fade-out)
        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) {
                container.remove();
            }
        }, 3100);
    }

    // 6. AJAX Wishlist Toggling (No-refresh like button)
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[action*="wishlist.php"]');
        if (!form) return;

        const actionInput = form.querySelector('input[name="action"]');
        const action = actionInput ? actionInput.value : '';

        // Only intercept the "add" (toggle) action on product card wishlist triggers
        if (action === 'add') {
            event.preventDefault();

            const productIdInput = form.querySelector('input[name="product_id"]');
            if (!productIdInput) return;

            const productId = productIdInput.value;
            const button = form.querySelector('button[type="submit"]');
            const icon = button ? button.querySelector('i') : null;

            // Determine relative endpoint paths based on current URL structure
            const currentPath = window.location.pathname;
            const isCustomerFolder = currentPath.includes('/customer/');
            const apiUrl = isCustomerFolder ? '../api/toggle-wishlist.php' : 'api/toggle-wishlist.php';
            const loginUrl = isCustomerFolder ? 'login.php' : 'customer/login.php';

            const formData = new FormData();
            formData.append('product_id', productId);

            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (response.status === 401) {
                        window.location.href = loginUrl;
                        throw new Error('Unauthorized');
                    }
                    if (!response.ok) {
                        throw new Error('Network error');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'added') {
                        if (icon) {
                            icon.className = 'fas fa-heart';
                        }
                        showToast(data.message, 'success');
                    } else if (data.status === 'removed') {
                        if (icon) {
                            icon.className = 'far fa-heart';
                        }
                        showToast(data.message, 'info');
                    } else {
                        showToast(data.message || 'Error updating wishlist.', 'error');
                    }
                })
                .catch(error => {
                    if (error.message !== 'Unauthorized') {
                        console.error('Error:', error);
                        showToast('An error occurred. Please try again.', 'error');
                    }
                });
        }
    });

    // 7. Scroll-Reveal Animation Engine using Intersection Observer
    const revealElements = document.querySelectorAll('.scroll-reveal, .product-card, .glass-card, .container h2, .bg-dark.py-5.my-5');
    
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                if (el.classList.contains('scroll-reveal')) {
                    el.classList.add('revealed');
                } else {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }
                observer.unobserve(el); // Only animate once
                
                // Clean up will-change to free GPU memory after transition completes
                setTimeout(() => {
                    el.style.willChange = 'auto';
                }, 1000);
            }
        });
    }, {
        threshold: 0.05, // Trigger when 5% of the element is visible
        rootMargin: '0px 0px -40px 0px' // Trigger slightly before it hits the viewport
    });
    
    revealElements.forEach(el => {
        // For backwards compatibility with older items
        if (!el.classList.contains('scroll-reveal')) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.8s cubic-bezier(0.25, 1, 0.5, 1), transform 0.8s cubic-bezier(0.25, 1, 0.5, 1)';
            el.style.willChange = 'opacity, transform';
        }
        revealObserver.observe(el);
    });
});


