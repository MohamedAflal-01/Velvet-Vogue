<?php
$page_title = 'Welcome';
require_once 'includes/header.php';
?>

<style>
    /* Absolute reset for splash screen view */
    .navbar { display: none !important; }
    
    body { 
        padding-top: 0 !important;
        margin: 0;
        overflow: hidden;
        height: 100vh;
        width: 100vw;
        position: relative;
        background-color: #f3e8ff;
    }
    
    .welcome-wrapper {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        height: 100vh;
        width: 100vw;
        z-index: 1;
        transition: transform 0.9s cubic-bezier(0.85, 0, 0.15, 1), opacity 0.9s ease;
    }
    
    .navigating-out .welcome-wrapper {
        transform: translateY(-100vh);
        opacity: 0;
    }
    
    .welcome-bg {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=2070&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        filter: brightness(0.6) contrast(1.0);
        z-index: 1;
        transition: transform 10s ease;
    }
    
    body:hover .welcome-bg {
        transform: scale(1.05);
    }

    .welcome-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.45) 0%, rgba(243, 232, 255, 0.7) 100%);
        z-index: 2;
    }

    .welcome-container {
        position: relative;
        z-index: 3;
        max-width: 650px;
        width: 90%;
        text-align: center;
        padding: 55px 45px !important;
        animation: welcomeFadeInUp 1.2s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        background: rgba(255, 255, 255, 0.65) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border: 1px solid rgba(219, 39, 119, 0.15) !important;
        border-radius: 24px !important;
        box-shadow: 0 30px 60px rgba(147, 51, 234, 0.08) !important;
    }

    @keyframes welcomeFadeInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .welcome-brand {
        font-size: 3rem;
        font-weight: 700;
        letter-spacing: 8px;
        text-transform: uppercase;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #1e1b4b 30%, #db2777 65%, #9333ea 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: textTracking 2s cubic-bezier(0.25, 1, 0.5, 1) forwards;
    }

    @keyframes textTracking {
        from {
            letter-spacing: 2px;
            opacity: 0;
        }
        to {
            letter-spacing: 8px;
            opacity: 1;
        }
    }

    .welcome-subtitle {
        color: var(--accent-color);
        font-size: 0.9rem;
        font-weight: 600;
        letter-spacing: 4px;
        text-transform: uppercase;
        margin-bottom: 30px;
    }

    .welcome-desc {
        color: #1f2937;
        font-size: 1.05rem;
        line-height: 1.8;
        margin-bottom: 40px;
        font-weight: 400;
    }

    .welcome-buttons {
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    .welcome-buttons .btn {
        padding: 14px 35px !important;
        font-size: 0.9rem !important;
    }

    /* Ambient background lights */
    .ambient-dot-1 {
        position: absolute;
        top: 20%;
        left: 15%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(217, 70, 239, 0.12) 0%, rgba(217, 70, 239, 0) 70%);
        z-index: 2;
        pointer-events: none;
    }
    
    .ambient-dot-2 {
        position: absolute;
        bottom: 15%;
        right: 10%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(168, 85, 247, 0.09) 0%, rgba(168, 85, 247, 0) 70%);
        z-index: 2;
        pointer-events: none;
    }

    /* Scroll Down Indicator */
    .scroll-down-indicator {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
        z-index: 10;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    
    .scroll-down-indicator:hover {
        transform: translateX(-50%) translateY(-3px);
    }

    .scroll-text {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 3px;
        color: #1e1b4b;
        margin-bottom: 12px;
        opacity: 0.6;
        font-weight: 600;
        transition: opacity 0.3s ease;
    }
    
    .scroll-down-indicator:hover .scroll-text {
        opacity: 0.9;
    }

    .scroll-mouse {
        width: 22px;
        height: 36px;
        border: 2px solid #1e1b4b;
        border-radius: 12px;
        position: relative;
        opacity: 0.6;
        transition: opacity 0.3s ease;
    }
    
    .scroll-down-indicator:hover .scroll-mouse {
        opacity: 0.9;
    }

    .scroll-wheel {
        width: 4px;
        height: 8px;
        background-color: var(--accent-color);
        border-radius: 2px;
        position: absolute;
        top: 6px;
        left: 50%;
        transform: translateX(-50%);
        animation: scroll-wheel-anim 1.6s infinite ease-in-out;
    }

    @keyframes scroll-wheel-anim {
        0% {
            top: 6px;
            opacity: 1;
        }
        50% {
            top: 18px;
            opacity: 0;
        }
        100% {
            top: 6px;
            opacity: 1;
        }
    }
</style>

<div class="welcome-wrapper">
    <div class="welcome-bg"></div>
    <div class="welcome-overlay"></div>
    <div class="ambient-dot-1"></div>
    <div class="ambient-dot-2"></div>

    <div class="welcome-container">
        <h1 class="welcome-brand">Velvet Vogue</h1>
        <div class="welcome-subtitle">Prestige & Luxury</div>
        <p class="welcome-desc">
            Welcome to a curated sanctuary of fine fashion. Explore signature collections, high-fashion apparel, and bespoke accessories crafted for those who define elegance.
        </p>
        <div class="welcome-buttons">
            <a href="home.php" class="btn btn-luxury">Shop Now</a>
            <a href="about.php" class="btn btn-outline-luxury">Our Legacy</a>
        </div>
    </div>

    <!-- Scroll Down Indicator -->
    <div class="scroll-down-indicator" onclick="navigateToHome()">
        <span class="scroll-text">Scroll to Explore</span>
        <div class="scroll-mouse">
            <div class="scroll-wheel"></div>
        </div>
    </div>
</div>

<script>
let isRedirecting = false;

function navigateToHome() {
    if (isRedirecting) return;
    isRedirecting = true;
    
    // Add class to body to trigger transition animation
    document.body.classList.add('navigating-out');
    
    // Redirect after animation (900ms)
    setTimeout(() => {
        window.location.href = 'home.php';
    }, 900);
}

// Wheel (Mouse Scroll) detection
window.addEventListener('wheel', function(event) {
    if (event.deltaY > 0) { // Scroll down
        navigateToHome();
    }
}, { passive: true });

// Touch event swipe-up detection for mobile devices
let touchStartY = 0;
window.addEventListener('touchstart', function(event) {
    touchStartY = event.touches[0].clientY;
}, { passive: true });

window.addEventListener('touchmove', function(event) {
    let touchEndY = event.touches[0].clientY;
    if (touchStartY - touchEndY > 50) { // Swipe up (equivalent to scroll down)
        navigateToHome();
    }
}, { passive: true });
</script>

<?php 
// We close the layout tags manually since we bypassed the standard footer component
?>
</body>
</html>
