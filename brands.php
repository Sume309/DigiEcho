<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
use App\User;
use App\model\Category;
// use App\db;
// $conn = db::connect();
$db = new MysqliDb();
$page = "Brands";

$brands = $db->get('brands');
?>
<?php require __DIR__ . '/components/header.php'; ?>
<style>
    .brand-card {
        cursor: pointer;
    }
    
    .brand-card .card-img-top {
        height: 120px;
        width: 100%;
        object-fit: contain;
        object-position: center;
        padding: 10px;
        background-color: #f8f9fa;
    }
    
    .brand-card .card-body {
        padding: 0.75rem;
    }
    
    .brand-card .card-title {
        font-size: 0.9rem;
        line-height: 1.2;
        margin-bottom: 0;
    }
</style>
<!-- content start -->
<div class="container my-4">
    <h1 class="text-center mb-4">Our Brands</h1>
    <p class="text-center text-muted mb-4">Click on any brand to explore our products</p>
    
    <div class="row g-3">
        <?php foreach ($brands as $brand) : ?>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card h-100 brand-card" data-brandid="<?= $brand['id'] ?>">
                    <img src="assets/brands/<?= $brand['logo'] ?>" class="card-img-top" alt="<?= $brand['name'] ?>">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1"><?= $brand['name'] ?></h5>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Back to Brands Button (hidden initially) -->
    <div class="text-center mt-4" id="backToBrandsBtn" style="display: none;">
        <button class="btn btn-outline-primary" onclick="showBrandsGrid()">
            <i class="fas fa-arrow-left me-2"></i>Back to All Brands
        </button>
    </div>

    <div id="product-container" class="row g-3 mt-4"></div>
    <div id="pagination-container" class="d-flex justify-content-center mt-4"></div>
</div>
<!-- content end -->
<?php require __DIR__ . '/components/footer.php'; ?>
<script>
    $(document).ready(function() {
        let currentPage = 1;
        let currentBrandId = null;

        function loadProducts(brandId, page) {
            currentBrandId = brandId;
            currentPage = page;
            $.ajax({
                url: 'apis/get-brand-products.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    brand_id: brandId,
                    page: page
                },
                success: function(response) {
                    $('#product-container').empty();
                    if (response.products.length > 0) {
                        response.products.forEach(function(product) {
                            var productCard = `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card h-100 d-flex flex-column">
                                        <a href="product-details.php?id=${product.id}" class="text-decoration-none text-dark">
                                            <img src="assets/products/${product.image}" class="card-img-top" alt="${product.name}" onerror="this.onerror=null;this.src='<?= settings()['logo'] ?>';">
                                            <div class="card-body flex-grow-1">
                                                <h5 class="card-title">${product.name}</h5>
                                                <p class="card-text">Price: ৳${product.selling_price}</p>
                                            </div>
                                        </a>
                                        <div class="card-footer bg-transparent border-0">
                                            <div class="d-flex gap-1">
                                                <a href="product-details.php?id=${product.id}" class="btn btn-outline-primary btn-sm flex-fill" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">
                                                    <i class="fas fa-eye me-1"></i>Details
                                                </a>
                                                <button class="btn btn-primary btn-sm flex-fill btn-add-cart" data-product-id="${product.id}" data-product-name="${product.name}" data-product-price="${product.selling_price}" data-product-image="${product.image}" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">
                                                    <i class="fas fa-cart-plus me-1"></i>Cart
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                            $('#product-container').append(productCard);
                        });
                    } else {
                        $('#product-container').html('<p class="text-center">No products found for this brand.</p>');
                    }
                    renderPagination(response.totalPages, response.currentPage);
                },
                error: function(xhr, status, error) {
                    console.error("An error occurred: " + status + " " + error);
                    $('#product-container').html('<p class="text-center">Error loading products.</p>');
                }
            });
        }

        function renderPagination(totalPages, currentPage) {
            $('#pagination-container').empty();
            if (totalPages > 1) {
                let paginationHtml = '<nav><ul class="pagination">';
                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }
                paginationHtml += '</ul></nav>';
                $('#pagination-container').html(paginationHtml);
            }
        }

        $('.brand-card').on('click', function() {
            const brandId = $(this).data('brandid');
            const brandName = $(this).find('.card-title').text().trim();
            
            // Show loading state
            $('#product-container').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading products...</p></div>');
            
            // Load products for this brand
            loadProducts(brandId, 1);
            
            // Update page title to show selected brand
            $('h1').text('Brand: ' + brandName);
            $('p.text-muted').hide(); // Hide the subtitle
            
            // Show back button
            $('#backToBrandsBtn').show();
            
            // Scroll to products section
            setTimeout(() => {
                $('#product-container').get(0).scrollIntoView({ behavior: 'smooth' });
            }, 500);
        });

        // Function to show brands grid again
        window.showBrandsGrid = function() {
            // Reset page title
            $('h1').text('Our Brands');
            $('p.text-muted').show();
            
            // Hide products and back button
            $('#product-container').empty();
            $('#pagination-container').empty();
            $('#backToBrandsBtn').hide();
            
            // Scroll to top
            $('html, body').animate({ scrollTop: 0 }, 500);
            
            // Reset current brand
            currentBrandId = null;
            currentPage = 1;
        };

        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page !== currentPage) {
                loadProducts(currentBrandId, page);
            }
        });
    });
</script>
<?php
$db->disconnect();
?>
