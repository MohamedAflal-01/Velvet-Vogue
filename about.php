<?php
$page_title = 'About Us';
require_once 'includes/header.php';
?>

<!-- About Hero Section -->
<div class="hero-section" style="background-image: url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=2070&auto=format&fit=crop'); height: 50vh;">
    <div class="hero-overlay" style="background: rgba(0,0,0,0.65);"></div>
    <div class="container hero-content">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="text-gold text-uppercase fw-bold tracking-widest d-block mb-3 fade-in-up" style="letter-spacing: 4px;">Our Heritage</span>
                <h1 class="display-4 fw-bold text-white mb-3 fade-in-up">The Art of Elegance</h1>
                <p class="lead text-white-50 fade-in-up mb-0">Crafting luxury apparel that captures the essence of sophisticated living.</p>
            </div>
        </div>
    </div>
</div>

<!-- Our Journey Section -->
<div class="container my-5 py-5">
    <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0 fade-in-up">
            <span class="text-gold text-uppercase fw-bold d-block mb-2" style="letter-spacing: 2px;">Since 2020</span>
            <h2 class="text-dark mb-4 position-relative pb-3">
                Our Story & Vision
                <span class="position-absolute bottom-0 start-0 bg-gold" style="width: 60px; height: 2px;"></span>
            </h2>
            <p class="lead text-muted mb-4">Velvet Vogue was born out of a desire to create clothing that feels like a second skin, speaking of confidence, luxury, and grace without uttering a word.</p>
            <p class="text-muted mb-4">Every thread, fabric, and silhouette in our collection is curated with absolute precision. We source the finest organic silks from Lyon, cashmere from Mongolia, and premium linen from Belgium. By bridging the gap between traditional haute couture and modern style, we make high-fashion apparel accessible to the discerning modern trendsetter.</p>
            <p class="text-muted">We believe that fashion is a form of self-expression. Our garments are designed to last beyond seasons, serving as timeless cornerstones of a elegant wardrobe.</p>
        </div>
        <div class="col-lg-6 ps-lg-5 fade-in-up">
            <div class="position-relative">
                <!-- Decorative Gold Frame -->
                <div class="position-absolute border border-gold" style="top: 20px; left: 20px; right: -20px; bottom: -20px; z-index: 1; border-color: var(--accent-color) !important; border-width: 2px !important;"></div>
                <!-- Main Image -->
                <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1200&auto=format&fit=crop" class="img-fluid rounded shadow-lg position-relative" style="z-index: 2; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'" alt="Velvet Vogue Tailoring">
            </div>
        </div>
    </div>
</div>

<!-- Brand Core Values -->
<div class="bg-dark text-white py-5 my-5" style="border-top: 4px solid var(--accent-color); border-bottom: 4px solid var(--accent-color);">
    <div class="container py-4">
        <h2 class="text-center text-gold mb-5 position-relative pb-3">
            Our Pillars of Excellence
            <span class="position-absolute bottom-0 start-50 translate-middle-x bg-gold" style="width: 50px; height: 2px;"></span>
        </h2>
        <div class="row g-4">
            <!-- Quality Value -->
            <div class="col-md-4">
                <div class="card glass-card text-center p-4 h-100 border-0 text-white" style="background: rgba(255, 255, 255, 0.05); transition: transform 0.3s ease, background 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.background='rgba(255,255,255,0.05)';" style="border-radius: 15px;">
                    <div class="text-gold mb-4" style="font-size: 2.5rem;">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h5 class="text-gold text-uppercase fw-bold mb-3">Exquisite Quality</h5>
                    <p class="text-white-50 mb-0">We never compromise. Each fabric is hand-selected and thoroughly tested to meet the highest standards of durability, feel, and luxury draping.</p>
                </div>
            </div>
            <!-- Craftsmanship Value -->
            <div class="col-md-4">
                <div class="card glass-card text-center p-4 h-100 border-0 text-white" style="background: rgba(255, 255, 255, 0.05); transition: transform 0.3s ease, background 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.background='rgba(255,255,255,0.05)';" style="border-radius: 15px;">
                    <div class="text-gold mb-4" style="font-size: 2.5rem;">
                        <i class="fas fa-scissors"></i>
                    </div>
                    <h5 class="text-gold text-uppercase fw-bold mb-3">Master Craftsmanship</h5>
                    <p class="text-white-50 mb-0">Bridging generations of traditional stitching techniques with contemporary cuts, our master tailors craft every piece with unmatched meticulousness.</p>
                </div>
            </div>
            <!-- Sustainability Value -->
            <div class="col-md-4">
                <div class="card glass-card text-center p-4 h-100 border-0 text-white" style="background: rgba(255, 255, 255, 0.05); transition: transform 0.3s ease, background 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.background='rgba(255,255,255,0.05)';" style="border-radius: 15px;">
                    <div class="text-gold mb-4" style="font-size: 2.5rem;">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h5 class="text-gold text-uppercase fw-bold mb-3">Conscious Luxury</h5>
                    <p class="text-white-50 mb-0">We are committed to ethical manufacturing. From reducing water waste to using organic, biodegradable fabrics, we protect the planet we adore.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="container my-5 py-4">
    <div class="row text-center g-4 justify-content-center">
        <div class="col-6 col-md-3">
            <div class="p-3">
                <h2 class="display-5 fw-bold text-gold mb-1">5+</h2>
                <p class="text-muted text-uppercase small tracking-wider mb-0" style="letter-spacing: 1px;">Years of Heritage</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3">
                <h2 class="display-5 fw-bold text-gold mb-1">50k+</h2>
                <p class="text-muted text-uppercase small tracking-wider mb-0" style="letter-spacing: 1px;">Discerning Clients</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3">
                <h2 class="display-5 fw-bold text-gold mb-1">15+</h2>
                <p class="text-muted text-uppercase small tracking-wider mb-0" style="letter-spacing: 1px;">Global Showrooms</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3">
                <h2 class="display-5 fw-bold text-gold mb-1">100%</h2>
                <p class="text-muted text-uppercase small tracking-wider mb-0" style="letter-spacing: 1px;">Organic Fabrics</p>
            </div>
        </div>
    </div>
</div>

<!-- Director Quote & CTA -->
<div class="container my-5 py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card glass-card p-5 border-0 shadow-sm position-relative overflow-hidden" style="background: rgba(255,255,255,0.85);">
                <div class="text-gold-50 position-absolute" style="top: -20px; left: 10px; font-size: 8rem; opacity: 0.1; font-family: Georgia, serif;">&ldquo;</div>
                <p class="fs-4 italic text-dark mb-4 position-relative" style="z-index: 2; font-family: Georgia, serif;">"True luxury is not about being noticed, it is about being remembered. We design for the individual who writes their own history."</p>
                <h6 class="text-gold fw-bold text-uppercase tracking-widest mb-4" style="letter-spacing: 2px;">- Creative Director, Velvet Vogue</h6>
                <div>
                    <a href="shop.php" class="btn btn-luxury px-5 py-3">Explore Our Collection</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'components/footer.php'; ?>
