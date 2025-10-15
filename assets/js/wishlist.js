class Wishlist {
  constructor() {
    this.wishlistKey = 'ecommerce_wishlist';
    this.loadWishlist();
  }

  // Load wishlist from localStorage
  loadWishlist() {
    const wishlistData = localStorage.getItem(this.wishlistKey);
    this.items = wishlistData ? JSON.parse(wishlistData) : [];
  }

  // Save wishlist to localStorage
  saveWishlist() {
    localStorage.setItem(this.wishlistKey, JSON.stringify(this.items));
  }

  // Add item to wishlist
  addItem(item) {
    if (!item.id || !item.name || !item.price) {
      throw new Error('Item must have id, name, and price');
    }

    const existingItem = this.items.find(wishlistItem => wishlistItem.id === item.id);
    if (!existingItem) {
      this.items.push({
        id: item.id,
        name: item.name,
        price: parseFloat(item.price),
        image: item.image || '',
        addedAt: new Date().toISOString()
      });
      this.saveWishlist();
      return { added: true, message: 'Item added to wishlist' };
    } else {
      return { added: false, message: 'Item already in wishlist' };
    }
  }

  // Remove item from wishlist
  removeItem(itemId) {
    this.items = this.items.filter(item => item.id !== itemId);
    this.saveWishlist();
    return this.items;
  }

  // Get total number of items
  getTotalItems() {
    return this.items.length;
  }

  // Get wishlist items
  getItems() {
    return [...this.items];
  }

  // Clear wishlist
  clearWishlist() {
    this.items = [];
    this.saveWishlist();
    return this.items;
  }

  // Check if item is in wishlist
  isInWishlist(itemId) {
    return this.items.some(item => item.id === itemId);
  }

  // Get item by ID
  getItemById(itemId) {
    return this.items.find(item => item.id === itemId) || null;
  }

  // Check if wishlist is empty
  isEmpty() {
    return this.items.length === 0;
  }

  // Get wishlist summary
  getSummary() {
    return {
      items: this.getItems(),
      totalItems: this.getTotalItems(),
      isEmpty: this.isEmpty()
    };
  }
}

// Global functions for wishlist display
function populateWishlistItems(items, tableId) {
    $(tableId).html("");
    if (items.length === 0) {
        $(tableId).append(`
            <tr>
                <td colspan="3" class="text-center text-muted py-4">
                    <i class="fas fa-heart fa-3x mb-3 text-muted"></i>
                    <p>Your wishlist is empty</p>
                    <a href="index.php" class="btn btn-primary btn-sm">Start Shopping</a>
                </td>
            </tr>
        `);
        return;
    }
    
    items.forEach(item => {
        $(tableId).append(`
            <tr>
                <td class="align-middle">
                    <div class="d-flex align-items-center">
                        <img src="${item.image ? (item.image.startsWith('assets/') ? item.image : 'assets/products/' + item.image) : 'assets/images/product-placeholder.svg'}" alt="${item.name}" 
                             class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                        <div>
                            <small class="fw-bold">${item.name}</small>
                        </div>
                    </div>
                </td>
                <td class="align-middle">৳${parseFloat(item.price).toFixed(2)}</td>
                <td class="align-middle">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-add-to-cart-from-wishlist" data-id="${item.id}">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                        <button class="btn btn-outline-danger remove-wishlist-item" data-id="${item.id}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

const wishlist = new Wishlist(); // Initialize wishlist globally

console.log("Defining window.updateWishlistDisplay function...");
window.updateWishlistDisplay = function() {
    console.log("🖤 updateWishlistDisplay called");
    let allitems = wishlist.getSummary();
    const totalItems = wishlist.getTotalItems();
    
    console.log("🖤 Wishlist summary:", allitems);
    console.log("🔢 Total wishlist items:", totalItems);
    
    // Update wishlist count elements
    const wishlistCountElement = $("#wishlistCountButton");
    
    // Animate count change
    wishlistCountElement.css('transform', 'scale(1.2)');
    setTimeout(() => wishlistCountElement.css('transform', 'scale(1)'), 200);
    
    wishlistCountElement.text(totalItems);
    
    // Change colors based on wishlist status
    if (totalItems > 0) {
        wishlistCountElement.css({
            'background': '#dc3545',
            'color': '#fff'
        });
    } else {
        wishlistCountElement.css({
            'background': '#dc3545',
            'color': '#fff'
        });
    }
    
    console.log("✅ Updated wishlistCountButton");
    
    populateWishlistItems(allitems.items, "#wishlistContent table tbody");
};

// Update existing cart display function to use professional styling
window.updateCartDisplay = function() {
    console.log("🛒 updateCartDisplay called");
    let allitems = cart.getSummary();
    const totalItems = cart.getTotalItems();
    
    console.log("📊 Cart summary:", allitems);
    console.log("🔢 Total cart items:", totalItems);
    
    // Update cart count elements with professional styling
    const cartCountElement = $("#cartCountButton");
    
    // Animate count change
    cartCountElement.css('transform', 'scale(1.2)');
    setTimeout(() => cartCountElement.css('transform', 'scale(1)'), 200);
    
    cartCountElement.text(totalItems);
    
    // Professional cart badge styling
    if (totalItems > 0) {
        cartCountElement.css({
            'background': '#dc3545',
            'color': '#fff'
        });
    } else {
        cartCountElement.css({
            'background': '#dc3545',
            'color': '#fff'
        });
    }
    
    console.log("✅ Updated cartCountButton");
    
    $("#grandTotal").text('৳' + parseFloat(cart.getTotalPrice()).toFixed(2));
    populateItems(allitems.items, "#cartTable");
    populateItems(allitems.items, "#cartContent table tbody");

    // Update offcanvas cart content
    let cartItemsOffCanvas = '';
    allitems.items.forEach(item => {
        cartItemsOffCanvas += `
            <tr>
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>${item.price}</td>
                <td>৳${(item.quantity * item.price).toFixed(2)}</td>
                <td><a href="#" class="remove-item" data-id="${item.id}"><i class="fas fa-times"></i></a></td>
            </tr>
        `;
    });
    $('#cartContent table tbody').html(cartItemsOffCanvas);
    $('#grandTotalCanvas').text('৳' + parseFloat(cart.getTotalPrice()).toFixed(2));
};

$(document).ready(function() {
    console.log("wishlist.js: Document ready. Calling updateWishlistDisplay()...");
    // Initial wishlist load
    updateWishlistDisplay();

    // Wishlist event handlers
    $(document).on("click", ".remove-wishlist-item", function() {
        let id = $(this).data('id');
        wishlist.removeItem(id);
        updateWishlistDisplay();
        Swal.fire({
            icon: 'success',
            title: 'Item Removed',
            text: 'The item has been removed from your wishlist.',
            timer: 1500,
            showConfirmButton: false
        });
    });

    $(document).on("click", ".btn-add-wishlist", function() {
        console.log("🖤 Add to wishlist button clicked.");
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        const productPrice = $(this).data('product-price');
        const productImage = $(this).data('product-image');
        
        console.log("🖤 Product data:", { productId, productName, productPrice, productImage });
        
        // Ensure image path is complete
        const fullImagePath = productImage && !productImage.startsWith('assets/') ? 
            'assets/products/' + productImage : productImage;
            
        const result = wishlist.addItem({
            id: productId,
            name: productName,
            price: productPrice,
            image: fullImagePath
        });
        
        console.log("✅ Wishlist operation result:", result);
        updateWishlistDisplay();

        // Update button state
        const button = $(this);
        if (result.added) {
            button.removeClass('btn-outline-danger').addClass('btn-danger');
            button.find('i').removeClass('far').addClass('fas');
            Swal.fire({
                position: "top-end",
                icon: "success",
                title: "Added to wishlist",
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            Swal.fire({
                position: "top-end",
                icon: "info",
                title: "Already in wishlist",
                showConfirmButton: false,
                timer: 1500
            });
        }
    });

    $(document).on("click", ".btn-add-to-cart-from-wishlist", function() {
        const itemId = $(this).data('id');
        const wishlistItem = wishlist.getItemById(itemId);
        
        if (wishlistItem) {
            cart.addItem({
                id: wishlistItem.id,
                name: wishlistItem.name,
                price: wishlistItem.price,
                quantity: 1,
                image: wishlistItem.image
            });
            
            updateCartDisplay();
            
            Swal.fire({
                position: "top-end",
                icon: "success",
                title: "Added to cart from wishlist",
                showConfirmButton: false,
                timer: 1500
            });
        }
    });

    // Update offcanvas wishlist when shown
    $('#offcanvasWishlist').on('show.bs.offcanvas', function () {
        updateWishlistDisplay();
    });
});