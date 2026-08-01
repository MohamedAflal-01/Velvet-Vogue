<?php
$page_title = 'Luxury Fashion Clothing';
require_once 'includes/header.php';

$db = new Database();
$conn = $db->getConnection();

// Fetch featured categories (up to 4)
$cat_stmt = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL AND status = 1 LIMIT 4");
$categories = $cat_stmt->fetchAll();

// Fetch featured products (up to 4)
$prod_stmt = $conn->query("SELECT p.*, pi.image_url FROM products p LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1 WHERE p.is_featured = 1 AND p.status = 'active' LIMIT 4");
$featured_products = $prod_stmt->fetchAll();

// Fetch new arrivals (up to 4)
$new_stmt = $conn->query("SELECT * FROM products WHERE is_new = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 4");
$new_arrivals = $new_stmt->fetchAll();
?>

<!-- Hero Section -->
<div class="hero-section" style="background-image: url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=2070&auto=format&fit=crop');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="hero-glass-card p-5 text-center">
                    <span class="text-gold text-uppercase fw-bold tracking-widest d-block mb-3 fade-in-up" style="letter-spacing: 3px;">Prestige & Luxury</span>
                    <h1 class="display-3 fw-bold text-white mb-4 fade-in-up">Velvet Vogue</h1>
                    <p class="lead text-white-50 mb-5 fade-in-up">Discover curated collections that redefine elegant living and style.</p>
                    <a href="shop.php" class="btn btn-luxury px-5 py-3 fs-5 fade-in-up">Explore Collection</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Categories -->
<div class="container my-5 py-4">
    <h2 class="text-center mb-5 text-gold position-relative pb-3">
        Featured Categories
        <span class="position-absolute bottom-0 start-50 translate-middle-x bg-gold" style="width: 50px; height: 2px;"></span>
    </h2>
    <div class="row">
        <?php 
        $category_fallbacks = [
            'men' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?q=80&w=600&auto=format&fit=crop',
            'women' => 'https://images.unsplash.com/photo-1509631179647-0177331693ae?q=80&w=600&auto=format&fit=crop',
            'kids' => 'https://images.unsplash.com/photo-1622290319146-7b63df48a635?q=80&w=600&auto=format&fit=crop',
            'accessories' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?q=80&w=600&auto=format&fit=crop'
        ];
        ?>
        <?php foreach ($categories as $cat): ?>
            <?php 
            $cat_slug = strtolower($cat['slug']);
            $fallback_img = isset($category_fallbacks[$cat_slug]) ? $category_fallbacks[$cat_slug] : 'https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=600&auto=format&fit=crop';
            $img_src = !empty($cat['image']) ? $cat['image'] : $fallback_img;
            ?>
            <div class="col-md-3 mb-4 scroll-reveal">
                <div class="card glass-card text-center overflow-hidden border-0 shadow-sm" style="transition: transform 0.3s;">
                    <div style="height: 250px; overflow:hidden;">
                        <img src="<?php echo htmlspecialchars($img_src); ?>" class="card-img-top h-100 w-100 object-fit-cover" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                    </div>
                    <div class="card-body bg-dark text-white py-3">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($cat['name']); ?></h5>
                        <a href="shop.php?category=<?php echo urlencode($cat['slug']); ?>" class="btn btn-sm btn-link text-gold text-decoration-none mt-2">Shop Now &rarr;</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Promotion Banner -->
<div class="container my-5 scroll-reveal">
    <div class="promo-glass-banner promo-slideshow py-5 px-4 text-center">
        <!-- Cycling Background Images -->
        <div class="promo-slide promo-slide-1"></div>
        <div class="promo-slide promo-slide-2"></div>
        <div class="promo-slide promo-slide-3"></div>
        <div class="promo-slide promo-slide-4"></div>
        <div class="promo-overlay"></div>
        <!-- Content -->
        <div class="promo-content position-relative" style="z-index: 2;">
            <h2 class="text-gold mb-3">Summer Flash Sale</h2>
            <p class="lead mb-4">Get up to 50% off on premium silk wear, luxury accessories, and seasonal collections.</p>
            <a href="sale.php" class="btn btn-luxury px-5 py-3">View Sale Items</a>
        </div>
    </div>
</div>

<!-- Featured Products -->
<div class="container my-5">
    <h2 class="text-center text-dark mb-5 text-gold position-relative pb-3">
        Featured Products
        <span class="position-absolute bottom-0 start-50 translate-middle-x bg-gold" style="width: 50px; height: 2px;"></span>
    </h2>
    <div class="row">
        <?php foreach ($featured_products as $product): ?>
            <div class="col-md-3 mb-4 scroll-reveal">
                <div class="card product-card h-100">
                    <div class="product-img-wrap">
                        <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=600&auto=format&fit=crop'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="product-actions">
                            <form action="customer/cart.php" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn-luxury btn-sm" title="Add to Cart"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                            </form>
                            <form action="customer/wishlist.php" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn-luxury btn-sm text-danger" title="Add to Wishlist">
                                    <i class="<?php echo in_array($product['id'], get_customer_wishlist_ids()) ? 'fas' : 'far'; ?> fa-heart"></i>
                                </button>
                            </form>
                            <a href="product-details.php?slug=<?php echo $product['slug']; ?>" class="btn btn-dark btn-sm"><i class="fas fa-eye"></i> View</a>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <h6 class="card-title mb-2 text-truncate"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <?php if ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                            <p class="mb-0 text-muted text-decoration-line-through small"><?php echo format_price($product['price']); ?></p>
                            <p class="text-danger fw-bold mb-0"><?php echo format_price($product['sale_price']); ?></p>
                        <?php else: ?>
                            <p class="text-gold fw-bold mb-0"><?php echo format_price($product['price']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="best-sellers.php" class="btn btn-outline-luxury px-5 py-3">View All <i class="fas fa-arrow-right ms-2"></i></a>
    </div>
</div>

<!-- Brand Logos -->
<div class="container my-5 scroll-reveal">
    <div class="brand-glass-track py-4">
        <div class="row justify-content-center align-items-center text-center opacity-60">
            <div class="col-4 col-md-2"><h5 class="fw-bold tracking-widest mb-0 text-white">GUCCI</h5></div>
            <div class="col-4 col-md-2"><h5 class="fw-bold tracking-widest mb-0 text-white">PRADA</h5></div>
            <div class="col-4 col-md-2"><h5 class="fw-bold tracking-widest mb-0 text-white">VALENTINO</h5></div>
            <div class="col-4 col-md-2"><h5 class="fw-bold tracking-widest mb-0 text-white">VERSACE</h5></div>
            <div class="col-4 col-md-2"><h5 class="fw-bold tracking-widest mb-0 text-white">CHANEL</h5></div>
        </div>
    </div>
</div>

<!-- Testimonials -->
<div class="container my-5 scroll-reveal">
    <div class="card glass-card p-5 shadow-sm text-center position-relative overflow-hidden">
        <i class="fas fa-quote-left text-gold mb-4 fs-1 opacity-25"></i>
        <h2 class="text-gold mb-4">What Our Clients Say</h2>
        <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <p class="fs-5 italic">"The fabrics are incredibly premium. Velvet Vogue has become my absolute go-to for luxury evening wear."</p>
                    <h6 class="text-gold mt-3">- Eleanor V.</h6>
                </div>
                <div class="carousel-item">
                    <p class="fs-5 italic">"Superb customer service and elegant packaging. The suit fits perfectly."</p>
                    <h6 class="text-gold mt-3">- Charles D.</h6>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true" style="filter: invert(1);"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true" style="filter: invert(1);"></span>
            </button>
        </div>
    </div>
</div>

<!-- Newsletter -->
<div class="container my-5 scroll-reveal">
    <div class="card glass-card p-5 text-center">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <h3 class="text-gold mb-3">Join the Club</h3>
                <p class="text-muted mb-4">Subscribe to receive exclusive offers, early access to new collections, and fashion insights.</p>
                <form action="newsletter-subscribe.php" method="POST" class="d-flex">
                    <input type="email" class="form-control me-2" placeholder="Your Email Address" required>
                    <button type="submit" class="btn btn-luxury px-4">Subscribe</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'components/footer.php'; ?>
