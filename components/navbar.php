<?php
$current_page = basename($_SERVER['PHP_SELF']);
$active_category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
?>
<!-- Navbar Component -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand text-uppercase fw-bold text-gold" href="<?php echo BASE_URL; ?>home.php">Velvet Vogue</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'home.php' || $current_page === 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>home.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'shop.php' && empty($active_category)) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>shop.php">Shop</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (!empty($active_category)) ? 'active' : ''; ?>" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Categories
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                        <li><a class="dropdown-item <?php echo ($active_category === 'men') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>shop.php?category=men">Men</a></li>
                        <li><a class="dropdown-item <?php echo ($active_category === 'women') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>shop.php?category=women">Women</a></li>
                        <li><a class="dropdown-item <?php echo ($active_category === 'kids') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>shop.php?category=kids">Kids</a></li>
                        <li><a class="dropdown-item <?php echo ($active_category === 'accessories') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>shop.php?category=accessories">Accessories</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>contact.php">Contact</a>
                </li>
            </ul>
            <form class="d-flex me-3" action="<?php echo BASE_URL; ?>shop.php" method="GET">
                <div class="search-wrapper">
                    <input class="search-input" type="search" name="search" placeholder="Search..." required value="<?php echo isset($_GET['search']) ? htmlspecialchars(sanitize_input($_GET['search'])) : ''; ?>">
                    <button class="search-btn" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </form>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>customer/cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">
                            <?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>customer/wishlist.php">
                        <i class="fas fa-heart"></i>
                    </a>
                </li>
                <?php if (is_logged_in()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> Account
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>customer/profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>customer/orders.php">Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>customer/logout.php">Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>customer/login.php">Login</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
