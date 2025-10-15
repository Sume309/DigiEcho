<?php
require_once __DIR__ . '/../src/SettingsHelper.php';

$banners = SettingsHelper::getBanners(true, 'homepage'); // Get only active homepage banners
?>

<?php if (!empty($banners)): ?>
<div id="bannerCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
    <!-- Indicators -->
    <div class="carousel-indicators">
        <?php foreach ($banners as $index => $banner): ?>
            <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="<?= $index ?>" 
                    class="<?= $index === 0 ? 'active' : '' ?>" 
                    aria-current="<?= $index === 0 ? 'true' : 'false' ?>" 
                    aria-label="Slide <?= $index + 1 ?>"></button>
        <?php endforeach; ?>
    </div>

    <!-- Slides -->
    <div class="carousel-inner">
        <?php foreach ($banners as $index => $banner): ?>
            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <div class="banner-slide position-relative">
                    <img src="<?= htmlspecialchars($banner['image']) ?>" 
                         class="d-block w-100 banner-image" 
                         alt="<?= htmlspecialchars($banner['title']) ?>">
                    
                    <!-- Banner Content Overlay -->
                    <div class="carousel-caption d-none d-md-block banner-content">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-6">
                                    <h1 class="banner-title display-4 fw-bold mb-3">
                                        <?= htmlspecialchars($banner['title']) ?>
                                    </h1>
                                    
                                    <?php if (!empty($banner['subtitle'])): ?>
                                        <h2 class="banner-subtitle h3 mb-3">
                                            <?= htmlspecialchars($banner['subtitle']) ?>
                                        </h2>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($banner['description'])): ?>
                                        <p class="banner-description lead mb-4">
                                            <?= htmlspecialchars($banner['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($banner['button_text']) && !empty($banner['button_link'])): ?>
                                        <a href="<?= htmlspecialchars($banner['button_link']) ?>" 
                                           class="btn btn-primary btn-lg banner-btn">
                                            <i class="fas fa-shopping-bag me-2"></i>
                                            <?= htmlspecialchars($banner['button_text']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Content -->
                    <div class="d-md-none mobile-banner-content">
                        <div class="container text-center py-4">
                            <h2 class="h4 fw-bold mb-2 text-white">
                                <?= htmlspecialchars($banner['title']) ?>
                            </h2>
                            
                            <?php if (!empty($banner['subtitle'])): ?>
                                <p class="mb-3 text-white">
                                    <?= htmlspecialchars($banner['subtitle']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($banner['button_text']) && !empty($banner['button_link'])): ?>
                                <a href="<?= htmlspecialchars($banner['button_link']) ?>" 
                                   class="btn btn-primary">
                                    <?= htmlspecialchars($banner['button_text']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Controls -->
    <?php if (count($banners) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    <?php endif; ?>
</div>

<style>
.banner-slide {
    position: relative;
    height: 500px;
    overflow: hidden;
}

.banner-image {
    height: 500px;
    object-fit: cover;
    object-position: center;
}

.banner-content {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    text-align: left;
    background: linear-gradient(90deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 70%, transparent 100%);
    padding: 2rem 0;
}

.banner-title {
    color: #fff;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    animation: slideInLeft 1s ease-out;
}

.banner-subtitle {
    color: #f8f9fa;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    animation: slideInLeft 1s ease-out 0.2s both;
}

.banner-description {
    color: #e9ecef;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    animation: slideInLeft 1s ease-out 0.4s both;
}

.banner-btn {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
    padding: 0.75rem 2rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
    transition: all 0.3s ease;
    animation: slideInLeft 1s ease-out 0.6s both;
}

.banner-btn:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,123,255,0.4);
}

.mobile-banner-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
}

.carousel-control-prev,
.carousel-control-next {
    width: 5%;
    opacity: 0.8;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 1;
}

.carousel-indicators {
    bottom: 20px;
}

.carousel-indicators button {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin: 0 5px;
}

/* Animations */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .banner-slide {
        height: 300px;
    }
    
    .banner-image {
        height: 300px;
    }
    
    .banner-content {
        padding: 1rem 0;
    }
    
    .banner-title {
        font-size: 1.5rem;
    }
    
    .banner-subtitle {
        font-size: 1.1rem;
    }
    
    .banner-description {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .banner-slide {
        height: 250px;
    }
    
    .banner-image {
        height: 250px;
    }
}
</style>

<?php else: ?>
<!-- Default banner if no banners are configured -->
<div class="default-banner bg-primary text-white text-center py-5 mb-4">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Welcome to <?= SettingsHelper::get('site_name', 'DigiEcho') ?></h1>
        <p class="lead mb-4"><?= SettingsHelper::get('site_description', 'Your trusted online marketplace') ?></p>
        <a href="#products" class="btn btn-light btn-lg">
            <i class="fas fa-shopping-bag me-2"></i>Start Shopping
        </a>
    </div>
</div>
<?php endif; ?>
