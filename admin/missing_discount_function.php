<?php
// This is the missing handleGetDiscount function that needs to be added to discounts-management.php
// Add this function right before the handleGetStats function (around line 461)

// Handle get single discount
function handleGetDiscount($db) {
    try {
        $discountId = intval($_GET['discount_id'] ?? 0);
        
        if (!$discountId) {
            throw new Exception('Invalid discount ID');
        }
        
        // Get discount details
        $db->where('id', $discountId);
        $discount = $db->getOne('product_discounts');
        
        if (!$discount) {
            throw new Exception('Discount not found');
        }
        
        // Get related items based on applies_to
        $relatedItems = [];
        if ($discount['applies_to'] !== 'all_products') {
            $db->where('discount_id', $discountId);
            $relations = $db->get('product_discount_relations');
            
            if ($relations) {
                foreach ($relations as $relation) {
                    if ($discount['applies_to'] === 'specific_products' && isset($relation['product_id'])) {
                        $relatedItems[] = $relation['product_id'];
                    } elseif ($discount['applies_to'] === 'categories' && isset($relation['category_id'])) {
                        $relatedItems[] = $relation['category_id'];
                    } elseif ($discount['applies_to'] === 'brands' && isset($relation['brand_id'])) {
                        $relatedItems[] = $relation['brand_id'];
                    }
                }
            }
        }
        
        $discount['related_items'] = $relatedItems;
        
        echo json_encode([
            'success' => true, 
            'data' => $discount
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
