<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
$page = "About Us";

// Open Graph data for about page
$og_title = "About Us - " . settings()['companyname'];
$og_description = "Learn more about " . settings()['companyname'] . ", your trusted online shopping destination for quality products, excellent service, and fast delivery.";
$og_image = settings()['homepage'] . ltrim(settings()['logo'], '/');
$og_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$og_type = "website";

// Fetch team members and statistics from database
$team_members = [];
$stats = [
    'categories' => 0,
    'brands' => 0,
    'products' => 0,
    'customers' => 0
];

try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch team members
    $stmt = $pdo->query("SELECT * FROM team_members WHERE is_active = 1 ORDER BY sort_order ASC, created_at ASC");
    $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch dynamic statistics
    // Categories count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 1");
        $stats['categories'] = $stmt->fetchColumn();
    } catch(PDOException $e) {
        // Fallback if categories table doesn't exist or has different structure
        $stats['categories'] = 50;
    }
    
    // Brands count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM brands WHERE is_active = 1");
        $stats['brands'] = $stmt->fetchColumn();
    } catch(PDOException $e) {
        // Fallback if brands table doesn't exist or has different structure
        $stats['brands'] = 100;
    }
    
    // Products count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
        $stats['products'] = $stmt->fetchColumn();
    } catch(PDOException $e) {
        // Fallback if products table doesn't exist or has different structure
        $stats['products'] = 1000;
    }
    
    // Customers count (users)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
        $stats['customers'] = $stmt->fetchColumn();
    } catch(PDOException $e) {
        // Fallback if users table doesn't exist or has different structure
        $stats['customers'] = 5000;
    }
    
} catch(PDOException $e) {
    // If there's an error, we'll show fallback values (graceful degradation)
    error_log("Database fetch error: " . $e->getMessage());
    $stats = [
        'categories' => 50,
        'brands' => 100,
        'products' => 1000,
        'customers' => 5000
    ];
}

// Format numbers for display
function formatStatNumber($number) {
    if ($number >= 1000) {
        return number_format($number / 1000, 1) . 'K+';
    } elseif ($number >= 100) {
        return $number . '+';
    } else {
        return $number;
    }
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<style>
.about-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    margin-bottom: 2rem;
}

.about-section {
    padding: 2rem 0;
}

.feature-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
}

.stats-section {
    background: #f8f9fa;
    padding: 3rem 0;
}

.stat-item {
    text-align: center;
    padding: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #dc3545;
}

.team-member {
    text-align: center;
    padding: 1rem;
}

.team-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
    border: 5px solid #fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.team-avatar:hover {
    transform: scale(1.05);
}

.team-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    height: 100%;
    background: white;
}

.team-card:hover {
    transform: translateY(-5px);
}

.team-name {
    color: #333;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.team-position {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.team-description {
    color: #777;
    font-size: 0.85rem;
    line-height: 1.5;
}

.team-social .btn {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.team-social .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>

<!-- Hero Section -->
<div class="about-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">About <?= htmlspecialchars(settings()['companyname']) ?></h1>
                <p class="lead mb-4">Your trusted partner in online shopping, delivering quality products and exceptional service since our inception.</p>
                <div class="d-flex gap-3">
                    <a href="index.php" class="btn btn-light btn-lg">Shop Now</a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg">Contact Us</a>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <img src="./admin/assets/img/logo.jpg" alt="<?= htmlspecialchars(settings()['companyname']) ?>" class="img-fluid" style="max-height: 200px;">
            </div>
        </div>
    </div>
</div>

<!-- Our Story Section -->
<div class="about-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h2 class="mb-4">Our Story</h2>
                <p class="mb-3">
                    <?= htmlspecialchars(settings()['companyname']) ?> was founded with a simple mission: to make quality products accessible to everyone through the convenience of online shopping. What started as a small venture has grown into a trusted e-commerce platform serving customers across Bangladesh.
                </p>
                <p class="mb-3">
                    We believe that shopping should be easy, secure, and enjoyable. That's why we've built a platform that combines the best products from trusted suppliers with a seamless shopping experience that puts our customers first.
                </p>
                <p>
                    Today, we're proud to offer thousands of products across multiple categories, from electronics and garments to automobiles and household items, all backed by our commitment to quality and customer satisfaction.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="bg-light p-3 rounded text-center">
                            <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                            <h6>Easy Shopping</h6>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light p-3 rounded text-center">
                            <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                            <h6>Secure Payments</h6>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light p-3 rounded text-center">
                            <i class="fas fa-truck fa-2x text-warning mb-2"></i>
                            <h6>Fast Delivery</h6>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light p-3 rounded text-center">
                            <i class="fas fa-headset fa-2x text-info mb-2"></i>
                            <h6>24/7 Support</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="about-section bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose Us?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary text-white">
                            <i class="fas fa-star"></i>
                        </div>
                        <h5 class="card-title">Quality Products</h5>
                        <p class="card-text">We carefully select every product to ensure it meets our high standards for quality and value.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success text-white">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h5 class="card-title">Fast Shipping</h5>
                        <p class="card-text">Quick and reliable delivery service to get your orders to you as soon as possible.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info text-white">
                            <i class="fas fa-customer-service"></i>
                        </div>
                        <h5 class="card-title">Customer Support</h5>
                        <p class="card-text">Our dedicated support team is here to help you with any questions or concerns.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?= formatStatNumber($stats['categories']) ?></div>
                    <h6>Categories</h6>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?= formatStatNumber($stats['brands']) ?></div>
                    <h6>Brands</h6>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?= formatStatNumber($stats['products']) ?></div>
                    <h6>Products</h6>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?= formatStatNumber($stats['customers']) ?></div>
                    <h6>Happy Customers</h6>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mission & Vision -->
<div class="about-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle p-3 me-3">
                                <i class="fas fa-bullseye fa-2x"></i>
                            </div>
                            <h3>Our Mission</h3>
                        </div>
                        <p>
                            To provide an exceptional online shopping experience by offering high-quality products, 
                            competitive prices, and outstanding customer service. We strive to make online shopping 
                            convenient, secure, and enjoyable for everyone.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success text-white rounded-circle p-3 me-3">
                                <i class="fas fa-eye fa-2x"></i>
                            </div>
                            <h3>Our Vision</h3>
                        </div>
                        <p>
                            To become the leading e-commerce platform in Bangladesh, known for our commitment to 
                            quality, innovation, and customer satisfaction. We envision a future where online 
                            shopping is the preferred choice for all consumers.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($team_members)): ?>
<!-- Our Team Section -->
<div class="about-section bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="mb-3">Meet Our Team</h2>
            <p class="lead text-muted">The dedicated professionals behind <?= htmlspecialchars(settings()['companyname']) ?></p>
        </div>
        <div class="row g-4">
            <?php foreach ($team_members as $member): ?>
            <div class="col-lg-3 col-md-6">
                <div class="card team-card">
                    <div class="card-body text-center p-4">
                        <?php 
                        $image_path = $member['image'];
                        $image_exists = $image_path && file_exists($image_path);
                        ?>
                        <?php if ($image_exists): ?>
                            <img src="<?= htmlspecialchars($image_path) ?>" 
                                 alt="<?= htmlspecialchars($member['name']) ?>" 
                                 class="team-avatar"
                                 onerror="this.src='placeholder-image.php?w=150&h=150&text=<?= urlencode(substr($member['name'], 0, 2)) ?>&bg=6c757d&color=ffffff';">
                        <?php else: ?>
                            <img src="placeholder-image.php?w=150&h=150&text=<?= urlencode(substr($member['name'], 0, 2)) ?>&bg=6c757d&color=ffffff" 
                                 alt="<?= htmlspecialchars($member['name']) ?>" 
                                 class="team-avatar">
                        <?php endif; ?>
                        
                        <h5 class="team-name"><?= htmlspecialchars($member['name']) ?></h5>
                        <p class="team-position"><?= htmlspecialchars($member['position']) ?></p>
                        
                        <?php if (!empty($member['description'])): ?>
                            <p class="team-description">
                                <?= htmlspecialchars($member['description']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($member['linkedin']) || !empty($member['twitter']) || !empty($member['facebook'])): ?>
                            <div class="team-social mt-3">
                                <?php if (!empty($member['linkedin'])): ?>
                                    <a href="<?= htmlspecialchars($member['linkedin']) ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary me-1" 
                                       title="LinkedIn">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($member['twitter'])): ?>
                                    <a href="<?= htmlspecialchars($member['twitter']) ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-info me-1" 
                                       title="Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($member['facebook'])): ?>
                                    <a href="<?= htmlspecialchars($member['facebook']) ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary me-1" 
                                       title="Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($member['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($member['email']) ?>" 
                                       class="btn btn-sm btn-outline-secondary" 
                                       title="Email">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/components/footer.php'; ?>
</body>
</html>