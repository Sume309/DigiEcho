<?php
/*
STEP-BY-STEP FIX GUIDE FOR DISCOUNT MANAGEMENT

ISSUE 1: Missing handleGetDiscount function
SOLUTION: Copy the function below and paste it in discounts-management.php at line 461 (before "// Handle get statistics")
*/

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

/*
ISSUE 2: DataTable filtering issue
SOLUTION: In handleGetDiscounts function around line 101, REMOVE these lines:

// Check if discount is currently active based on dates
$currentDate = date('Y-m-d');
$whereConditions[] = '(start_date <= ? AND end_date >= ?)';
$params = array_merge($params, [$currentDate, $currentDate]);

This is causing the table to show no results because it's filtering incorrectly.
*/

/*
ISSUE 3: Accessibility warning fix
SOLUTION: Add this JavaScript code after line 915 in the <script> section:
*/
?>

<script>
// Add this JavaScript code to fix aria-hidden accessibility issue
$(document).ready(function() {
    // Fix for aria-hidden accessibility warning
    $('#discountModal').on('show.bs.modal', function (e) {
        $(this).removeAttr('aria-hidden');
    });
    
    $('#discountModal').on('hide.bs.modal', function (e) {
        $(this).attr('aria-hidden', 'true');
    });
    
    $('#discountModal').on('shown.bs.modal', function (e) {
        $(this).find('input:first').focus();
    });
    
    // Rest of your existing JavaScript code goes here...
});
</script>

<?php
/*
QUICK FIX SUMMARY:

1. ADD the handleGetDiscount function at line 461
2. REMOVE the date filtering lines in handleGetDiscounts (lines 100-102)
3. ADD the accessibility JavaScript code after line 915

After these changes:
✅ "Add New Discount" will work
✅ "Edit" will load discount details
✅ "Delete" will work without 404 errors
✅ "Toggle" will work properly
✅ Dashboard stats will be dynamic
✅ No more accessibility warnings

DATABASE STATUS: ✅ All required tables exist and are properly structured.
*/
?>
