<?php
require_once __DIR__ . '/../src/SettingsHelper.php';

$hotDealsBanners = SettingsHelper::getBanners(true, 'hot-deals'); // Get only active hot-deals banners
?>

<?php if (!empty($hotDealsBanners)): ?>
<div id="hotDealsBannerCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
    <!-- Indicators -->
    <div class="carousel-indicators">
        <?php foreach ($hotDealsBanners as $index => $banner): ?>
            <button type="button" data-bs-target="#hotDealsBannerCarousel" data-bs-slide-to="<?= $index ?>" 
                    class="<?= $index === 0 ? 'active' : '' ?>" 
                    aria-current="<?= $index === 0 ? 'true' : 'false' ?>" 
                    aria-label="Slide <?= $index + 1 ?>"></button>
        <?php endforeach; ?>
    </div>

    <!-- Slides -->
    <div class="carousel-inner">
        <?php foreach ($hotDealsBanners as $index => $banner): ?>
            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <div class="banner-slide position-relative">
                    <img src="<?= htmlspecialchars($banner['image']) ?>" 
                         class="d-block w-100 banner-image" 
                         alt="<?= htmlspecialchars($banner['title']) ?>">
                    
                    <!-- Hot Deals Banner Content Overlay -->
                    <div class="carousel-caption d-none d-md-block banner-content hot-deals-banner">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-8 mx-auto text-center">
                                    <div class="hot-deals-badge mb-3">
                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                            <i class="fas fa-fire me-2"></i>HOT DEALS
                                        </span>
                                    </div>
                                    
                                    <h1 class="banner-title display-3 fw-bold mb-3 text-white">
                                        <?= htmlspecialchars($banner['title']) ?>
                                    </h1>
                                    
                                    <?php if (!empty($banner['subtitle'])): ?>
                                        <h2 class="banner-subtitle h2 mb-3 text-warning">
                                            <?= htmlspecialchars($banner['subtitle']) ?>
                                        </h2>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($banner['description'])): ?>
                                        <p class="banner-description lead mb-4 text-white">
                                            <?= htmlspecialchars($banner['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($banner['button_text']) && !empty($banner['button_link'])): ?>
                                        <a href="<?= htmlspecialchars($banner['button_link']) ?>" 
                                           class="btn btn-danger btn-lg px-5 py-3 fw-bold text-uppercase">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            <?= htmlspecialchars($banner['button_text']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hot Deals Overlay Effect -->
                    <div class="hot-deals-overlay"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#hotDealsBannerCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#hotDealsBannerCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<style>
.hot-deals-banner .banner-title {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
    animation: pulse 2s infinite;
}

.hot-deals-banner .banner-subtitle {
    text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
    animation: glow 2s ease-in-out infinite alternate;
}

.hot-deals-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
  
    pointer-events: none;
}

.hot-deals-badge .badge {
    animation: bounce 2s infinite;
    box-shadow: 0 4px 8px rgba(220,53,69,0.4);
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes glow {
    from { text-shadow: 1px 1px 2px rgba(0,0,0,0.8), 0 0 10px rgba(255,193,7,0.5); }
    to { text-shadow: 1px 1px 2px rgba(0,0,0,0.8), 0 0 20px rgba(255,193,7,0.8); }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.hot-deals-banner .btn-danger {
    background: linear-gradient(45deg, #dc3545, #fd7e14);
    border: none;
    box-shadow: 0 4px 15px rgba(220,53,69,0.4);
    transition: all 0.3s ease;
}

.hot-deals-banner .btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220,53,69,0.6);
    background: linear-gradient(45deg, #c82333, #e66100);
}

#hotDealsBannerCarousel .banner-image {
    height: 400px;
    object-fit: cover;
    filter: brightness(0.8);
}

@media (max-width: 768px) {
    #hotDealsBannerCarousel .banner-image {
        height: 250px;
    }
    
    .hot-deals-banner .banner-title {
        font-size: 2rem !important;
    }
    
    .hot-deals-banner .banner-subtitle {
        font-size: 1.2rem !important;
    }
}
</style>

<?php else: ?>
<!-- Default Hot Deals Header when no banners are set -->
<div class="hot-deals-header bg-gradient-danger text-white py-5 mb-4">
    <div class="container text-center">
        <div class="hot-deals-badge mb-3">
            <span class="badge bg-warning text-dark fs-5 px-4 py-2">
                <i class="fas fa-fire me-2"></i>HOT DEALS
            </span>
        </div>
        <h1 class="display-4 fw-bold mb-3">🔥 Amazing Hot Deals 🔥</h1>
        <p class="lead">Don't miss out on these incredible offers!</p>
    </div>
</div>

<style>
.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545, #fd7e14);
}
</style>
<?php endif; ?>
