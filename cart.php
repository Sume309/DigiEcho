<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
use App\User;
use App\model\Category;
$db = new MysqliDb();

// Page metadata
$page = 'Shopping Cart';
$og_title = 'Shopping Cart - ' . settings()['companyname'];
$og_description = 'Review and manage items in your shopping cart';
$og_image = settings()['homepage'] . ltrim(settings()['logo'], '/');

require __DIR__ . '/components/header.php';
?>

<style>
.cart-container {
    min-height: 60vh;
    padding: 2rem 0;
}

.cart-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
    border-radius: 10px;
}

.cart-card {
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.cart-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border-color: #007bff;
}

.product-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.price {
    color: #28a745;
    font-weight: bold;
    font-size: 1.2rem;
}

.total-price {
    color: #007bff;
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

.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-cart i {
    font-size: 5rem;
    margin-bottom: 2rem;
    color: #007bff;
    opacity: 0.5;
}

.cart-stats {
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
    color: #007bff;
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

.cart-item-row {
    align-items: center;
}

.qty-input {
    width: 80px;
    text-align: center;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.5rem;
    font-size: 1rem;
}

.qty-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.summary-card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.summary-card .card-body {
    padding: 1.5rem;
}

.checkout-btn {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
    font-weight: 500;
    border-radius: 8px;
}

.continue-btn {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
    border-radius: 8px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .product-image {
        width: 80px;
        height: 80px;
    }
    
    .cart-item-row {
        text-align: center;
    }
    
    .action-buttons {
        margin-top: 1rem;
    }
}
</style>

<!-- Cart Header -->
<div class="cart-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-3">
                    <i class="fas fa-shopping-cart me-3"></i>Shopping Cart
                </h1>
                <p class="lead mb-0">Review your selected items and proceed to checkout</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="stat-item">
                    <div class="stat-number" id="cartHeaderCount">0</div>
                    <div class="stat-label text-white">Items in Cart</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container cart-container">
    <!-- Cart Statistics -->
    <div class="cart-stats">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number" id="totalCartItems">0</div>
                    <div class="stat-label">Total Items</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number" id="totalCartValue">৳0</div>
                    <div class="stat-label">Total Value</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <button class="btn btn-outline-danger btn-action w-100" onclick="clearCartPage()">
                        <i class="fas fa-trash me-2"></i>Clear Cart
                    </button>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <a href="wishlist.php" class="btn btn-outline-primary btn-action w-100">
                        <i class="fas fa-heart me-2"></i>View Wishlist
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Items -->
    <div class="cart-card">
        <div class="card">
            <div class="card-body">
                <div id="cartEmptyMessage" class="empty-cart" style="display: none;">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add some products to your cart to see them here!</p>
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                    </a>
                </div>
                
                <div id="cartItems">
                    <!-- Cart items will be loaded here by JavaScript -->
                </div>
                
                <!-- Cart Summary -->
                <div id="cartSummary" class="mt-4" style="display: none;">
                    <div class="row">
                    
                        <div class="col-md-12">
                            <div class="d-grid gap-3">
                                <a href="place_order.php" class="btn btn-success checkout-btn">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                </a>
                                <a href="index.php" class="btn btn-outline-primary continue-btn">
                                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize cart display when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Ensure cart is available
    if (typeof cart !== 'undefined') {
        updateCartPageDisplay();
    } else {
        console.error('Cart object not found');
    }
});

// Function to update cart display on the cart page
function updateCartPageDisplay() {
    // Use the cart object from cart.js
    const cartSummary = cart.getSummary();
    const totalItems = cart.getTotalItems();
    const totalPrice = cart.getTotalPrice();
    
    // Update header counts
    document.getElementById('cartHeaderCount').textContent = totalItems;
    document.getElementById('totalCartItems').textContent = totalItems;
    document.getElementById('totalCartValue').textContent = '৳' + parseFloat(totalPrice).toFixed(2);
    
    // Update cart items display
    const cartItems = document.getElementById('cartItems');
    const cartEmptyMessage = document.getElementById('cartEmptyMessage');
    const cartSummaryElement = document.getElementById('cartSummary');
    
    if (cartSummary.isEmpty) {
        cartItems.style.display = 'none';
        cartSummaryElement.style.display = 'none';
        cartEmptyMessage.style.display = 'block';
    } else {
        cartItems.style.display = 'block';
        cartSummaryElement.style.display = 'block';
        cartEmptyMessage.style.display = 'none';
        
        // Render items
        cartItems.innerHTML = '';
        cartSummary.items.forEach(item => {
            // Ensure image path is complete
            let imagePath = item.image || 'assets/img/placeholder.png';
            if (imagePath && !imagePath.startsWith('assets/') && !imagePath.startsWith('http')) {
                imagePath = 'assets/products/' + imagePath;
            }
            
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-card mb-3';
            itemElement.innerHTML = `
                <div class="card">
                    <div class="card-body">
                        <div class="row cart-item-row">
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
                                <div class="mt-2">
                                    <input type="number" class="form-control qty-input" value="${item.quantity}" min="1" 
                                           onchange="updateCartItemQuantity(${item.id}, this.value)" data-id="${item.id}">
                                </div>
                            </div>
                            <div class="col-md-2 col-12 mb-3 mb-md-0 text-md-center">
                                <div class="total-price">৳${(item.price * item.quantity).toFixed(2)}</div>
                            </div>
                            <div class="col-md-2 col-12 action-buttons text-md-end">
                                <button class="btn btn-danger btn-action w-100 w-md-auto" onclick="removeCartItem(${item.id})">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            cartItems.appendChild(itemElement);
        });
        
        // Update summary
        document.getElementById('subtotal').textContent = '৳' + parseFloat(totalPrice).toFixed(2);
        document.getElementById('discount').textContent = '-৳0.00';
        document.getElementById('shipping').textContent = '৳0.00';
        document.getElementById('tax').textContent = '৳0.00';
        document.getElementById('total').textContent = '৳' + parseFloat(totalPrice).toFixed(2);
    }
}

// Function to update cart item quantity
function updateCartItemQuantity(id, quantity) {
    if (quantity <= 0) {
        removeCartItem(id);
        return;
    }
    
    cart.editItem(id, quantity);
    updateCartPageDisplay();
}

// Function to remove item from cart
function removeCartItem(id) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        cart.removeItem(id);
        updateCartPageDisplay();
    }
}

// Function to clear entire cart
function clearCartPage() {
    if (confirm('Are you sure you want to clear your cart?')) {
        cart.clearCart();
        updateCartPageDisplay();
    }
}
</script>

<?php require __DIR__ . '/components/footer.php'; ?>