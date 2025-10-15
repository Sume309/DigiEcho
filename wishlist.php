<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
use App\User;
use App\model\Category;
$db = new MysqliDb();

// Page metadata
$page = 'Wishlist';
$og_title = 'My Wishlist - ' . settings()['companyname'];
$og_description = 'View and manage your favorite products in your wishlist';
$og_image = settings()['homepage'] . ltrim(settings()['logo'], '/');

require __DIR__ . '/components/header.php';
?>

<style>
    .wishlist-container {
        min-height: 60vh;
        padding: 2rem 0;
    }
    
    .wishlist-header {
        background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-radius: 10px;
    }
    
    .wishlist-card {
        transition: all 0.3s ease;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .wishlist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border-color: #dc3545;
    }
    
    .product-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .price {
        color: #dc3545;
        font-weight: bold;
        font-size: 1.2rem;
    }
    
    .btn-action {
        transition: all 0.3s ease;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .btn-action:hover {
        transform: scale(1.05);
    }
    
    .empty-wishlist {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
    }
    
    .empty-wishlist i {
        font-size: 5rem;
        margin-bottom: 2rem;
        color: #dc3545;
        opacity: 0.5;
    }
    
    .wishlist-stats {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .stat-item {
        text-align: center;
        padding: 1rem;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #dc3545;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .product-info h5 {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .product-info .text-muted {
        font-size: 0.85rem;
    }
    
    .action-buttons .btn {
        margin-bottom: 0.5rem;
    }
    
    .wishlist-item-row {
        align-items: center;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .product-image {
            width: 80px;
            height: 80px;
        }
        
        .wishlist-item-row {
            text-align: center;
        }
        
        .action-buttons {
            margin-top: 1rem;
        }
    }
</style>

<!-- Wishlist Header -->
<div class="wishlist-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-3">
                    <i class="fas fa-heart me-3"></i>My Wishlist
                </h1>
                <p class="lead mb-0">Keep track of products you love and want to buy later</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="stat-item">
                    <div class="stat-number" id="wishlistHeaderCount">0</div>
                    <div class="stat-label text-white">Items Saved</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container wishlist-container">
    <!-- Wishlist Statistics -->
    <div class="wishlist-stats">
        <div class="row">
            <div class="col-md-4 col-6">
                <div class="stat-item">
                    <div class="stat-number" id="totalWishlistItems">0</div>
                    <div class="stat-label">Total Items</div>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="stat-item">
                    <div class="stat-number" id="totalWishlistValue">৳0</div>
                    <div class="stat-label">Total Value</div>
                </div>
            </div>
            <div class="col-md-4 col-12 mt-3 mt-md-0">
                <div class="stat-item">
                    <button class="btn btn-outline-danger btn-action w-100" onclick="clearAllWishlist()">
                        <i class="fas fa-trash me-2"></i>Clear All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Wishlist Items -->
    <div id="wishlistItems">
        <!-- Items will be loaded here by JavaScript -->
    </div>

    <!-- Empty Wishlist Message -->
    <div id="emptyWishlist" class="empty-wishlist" style="display: none;">
        <i class="fas fa-heart-broken"></i>
        <h3>Your wishlist is empty</h3>
        <p>Start adding products to your wishlist to keep track of items you love!</p>
        <a href="index.php" class="btn btn-primary btn-lg">
            <i class="fas fa-shopping-bag me-2"></i>Start Shopping
        </a>
    </div>

    <!-- Quick Actions -->
    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-primary btn-action me-3">
            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
        </a>
        <button class="btn btn-success btn-action" onclick="addAllToCart()" id="addAllToCartBtn" style="display: none;">
            <i class="fas fa-cart-plus me-2"></i>Add All to Cart
        </button>
    </div>
</div>

<script>
// Initialize wishlist display when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Ensure wishlist is available
    if (typeof wishlist !== 'undefined') {
        updateWishlistPageDisplay();
    } else {
        console.error('Wishlist object not found');
    }
});

// Function to update wishlist display on the wishlist page
function updateWishlistPageDisplay() {
    // Use the wishlist object from wishlist.js
    const wishlistSummary = wishlist.getSummary();
    const totalItems = wishlist.getTotalItems();
    
    // Calculate total value
    const totalValue = wishlistSummary.items.reduce((total, item) => total + parseFloat(item.price), 0);
    
    // Update statistics
    document.getElementById('wishlistHeaderCount').textContent = totalItems;
    document.getElementById('totalWishlistItems').textContent = totalItems;
    document.getElementById('totalWishlistValue').textContent = '৳' + totalValue.toFixed(2);
    
    const wishlistItems = document.getElementById('wishlistItems');
    const emptyWishlist = document.getElementById('emptyWishlist');
    const addAllToCartBtn = document.getElementById('addAllToCartBtn');
    
    if (wishlistSummary.isEmpty) {
        wishlistItems.style.display = 'none';
        emptyWishlist.style.display = 'block';
        addAllToCartBtn.style.display = 'none';
    } else {
        wishlistItems.style.display = 'block';
        emptyWishlist.style.display = 'none';
        addAllToCartBtn.style.display = 'inline-block';
        
        // Render items
        wishlistItems.innerHTML = '';
        wishlistSummary.items.forEach(item => {
            // Ensure image path is complete
            let imagePath = item.image || 'assets/img/placeholder.png';
            if (imagePath && !imagePath.startsWith('assets/') && !imagePath.startsWith('http')) {
                imagePath = 'assets/products/' + imagePath;
            }
            
            const itemElement = document.createElement('div');
            itemElement.className = 'wishlist-card';
            itemElement.innerHTML = `
                <div class="card">
                    <div class="card-body">
                        <div class="row wishlist-item-row">
                            <div class="col-md-2 col-12 mb-3 mb-md-0 text-center">
                                <img src="${imagePath}" alt="${item.name}" class="product-image mx-auto" onerror="this.src='assets/img/placeholder.png'">
                            </div>
                            <div class="col-md-4 col-12 mb-3 mb-md-0">
                                <div class="product-info">
                                    <h5 class="card-title">${item.name}</h5>
                                    <p class="card-text text-muted">SKU: ${item.sku || 'N/A'}</p>
                                    <p class="card-text text-muted">ID: ${item.id}</p>
                                </div>
                            </div>
                            <div class="col-md-2 col-12 mb-3 mb-md-0 text-md-center">
                                <div class="price">৳${parseFloat(item.price).toFixed(2)}</div>
                            </div>
                            <div class="col-md-4 col-12 action-buttons text-md-end">
                                <button class="btn btn-success btn-action w-100 w-md-auto me-md-2 mb-2" onclick="addToCartFromWishlist(${item.id})">
                                    <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                </button>
                                <button class="btn btn-outline-danger btn-action w-100 w-md-auto mb-2" onclick="removeFromWishlist(${item.id})">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            wishlistItems.appendChild(itemElement);
        });
    }
}

// Function to add item from wishlist to cart
function addToCartFromWishlist(itemId) {
    const wishlistItem = wishlist.getItemById(itemId);
    
    if (wishlistItem) {
        try {
            cart.addItem({
                id: wishlistItem.id,
                name: wishlistItem.name,
                price: wishlistItem.price,
                quantity: 1,
                image: wishlistItem.image,
                sku: wishlistItem.sku
            });
            
            // Remove from wishlist
            wishlist.removeItem(itemId);
            updateWishlistPageDisplay();
            updateCartDisplay(); // Update cart display in header/offcanvas
            
            Swal.fire({
                icon: 'success',
                title: 'Added to Cart!',
                text: `${wishlistItem.name} has been added to your cart`,
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end'
            });
        } catch (error) {
            console.error('Error adding to cart:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to add item to cart'
            });
        }
    }
}

// Function to remove item from wishlist
function removeFromWishlist(itemId) {
    if (confirm('Are you sure you want to remove this item from your wishlist?')) {
        wishlist.removeItem(itemId);
        updateWishlistPageDisplay();
    }
}

// Function to clear all items from wishlist
function clearAllWishlist() {
    Swal.fire({
        title: 'Clear Wishlist?',
        text: "This will remove all items from your wishlist!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, clear it!'
    }).then((result) => {
        if (result.isConfirmed) {
            wishlist.clearWishlist();
            updateWishlistPageDisplay();
            Swal.fire({
                icon: 'success',
                title: 'Cleared!',
                text: 'Your wishlist has been cleared.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

// Function to add all wishlist items to cart
function addAllToCart() {
    const wishlistSummary = wishlist.getSummary();
    
    if (wishlistSummary.isEmpty) return;
    
    wishlistSummary.items.forEach(item => {
        cart.addItem({
            id: item.id,
            name: item.name,
            price: item.price,
            quantity: 1,
            image: item.image,
            sku: item.sku
        });
    });
    
    wishlist.clearWishlist();
    updateWishlistPageDisplay();
    updateCartDisplay(); // Update cart display in header/offcanvas
    
    Swal.fire({
        icon: 'success',
        title: 'Added to Cart!',
        text: 'All wishlist items have been added to your cart',
        timer: 2000,
        showConfirmButton: false,
        position: 'top-end'
    });
}
</script>

<?php require __DIR__ . '/components/footer.php'; ?>