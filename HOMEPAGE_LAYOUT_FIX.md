# Homepage Layout Fix - Clean Original Design

## Current Issue
The homepage layout has conflicting CSS rules and needs to be restored to the clean, professional layout that was working before.

## Original Professional Layout Requirements
1. **Simple, clean product cards** with standard Bootstrap styling
2. **Compact spacing** using `g-2` for efficient product display
3. **Readable fonts** (0.8rem for titles, 0.9rem for prices)
4. **Standard card structure** with proper image, body, and footer sections
5. **Clean background** and proper section spacing

## What Should Work
- **4 products per row** on desktop (col-lg-3)
- **3 products per row** on tablet (col-md-4)  
- **2 products per row** on mobile (col-6)
- **Standard card hover effects** with translateY(-5px)
- **Professional color scheme** with Bootstrap defaults
- **Simple, efficient layout** without complex overlays

## Test Your Homepage
Visit: `http://localhost/Family-Haat-Bazar/`

Expected to see:
- Clean product grid with proper spacing
- Readable product titles and prices
- Working "Details" and "Add to Cart" buttons
- Smooth hover animations
- Professional appearance

## CSS Rules Applied
```css
.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.product-image {
    height: 120px;
    object-fit: cover;
    border-radius: 8px 8px 0 0;
}

.products-section {
    padding: 30px 0;
    background: #f8f9fa;
}
```

## HTML Structure
```html
<div class="col-6 col-md-4 col-lg-3">
    <div class="card product-card h-100 shadow-sm d-flex flex-column">
        <img src="..." class="card-img-top product-image" alt="...">
        <div class="card-body flex-grow-1 p-2">
            <h6 class="card-title mb-2" style="font-size: 0.8rem;">Product Name</h6>
            <div class="mb-2">
                <span class="price" style="font-size: 0.9rem;">৳Price</span>
                <small class="original-price ms-1">Stock</small>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="...">Details</a>
        </div>
        <div class="card-footer bg-transparent border-0">
            <button class="btn btn-primary btn-sm btn-add-cart w-100 cartBtn" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">Add to Cart</button>
        </div>
    </div>
</div>
```

This is the clean, working layout that should display properly now.