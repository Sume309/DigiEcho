<?php
// Simple script to fix the discounts-management.php file

$file = 'discounts-management.php';
$content = file_get_contents($file);

// 1. Add the missing get_discount case
if (strpos($content, "case 'get_discount':") === false) {
    $content = str_replace(
        "case 'get_stats':\n                handleGetStats(\$db);\n                break;",
        "case 'get_stats':\n                handleGetStats(\$db);\n                break;\n            case 'get_discount':\n                handleGetDiscount(\$db);\n                break;",
        $content
    );
}

// 2. Add the missing handleGetDiscount function
if (strpos($content, 'function handleGetDiscount') === false) {
    $newFunction = '
// Handle get single discount for editing
function handleGetDiscount($db) {
    try {
        $discountId = intval($_GET[\'discount_id\'] ?? 0);
        
        if (!$discountId) {
            throw new Exception(\'Invalid discount ID\');
        }
        
        // Get discount details
        $db->where(\'id\', $discountId);
        $discount = $db->getOne(\'product_discounts\');
        
        if (!$discount) {
            throw new Exception(\'Discount not found\');
        }
        
        // Get related items based on applies_to
        $relatedItems = [];
        if ($discount[\'applies_to\'] !== \'all_products\') {
            $db->where(\'discount_id\', $discountId);
            $relations = $db->get(\'product_discount_relations\');
            
            foreach ($relations as $relation) {
                if ($relation[\'product_id\']) {
                    $relatedItems[] = $relation[\'product_id\'];
                } elseif ($relation[\'category_id\']) {
                    $relatedItems[] = $relation[\'category_id\'];
                } elseif ($relation[\'brand_id\']) {
                    $relatedItems[] = $relation[\'brand_id\'];
                }
            }
        }
        
        $discount[\'related_items\'] = $relatedItems;
        
        echo json_encode([
            \'success\' => true, 
            \'data\' => $discount
        ]);
        
    } catch (Exception $e) {
        echo json_encode([\'success\' => false, \'message\' => $e->getMessage()]);
    }
}
';
    
    // Insert before the require statement
    $content = str_replace(
        'require __DIR__ . \'/components/header.php\'; ?>',
        $newFunction . "\n\nrequire __DIR__ . '/components/header.php'; ?>",
        $content
    );
}

// 3. Fix the edit function JavaScript
$oldEditFunction = '/\/\/ TODO: Implement edit functionality[\s\S]*?text: \'Edit functionality is not yet implemented.\'[\s\S]*?\}\);/';
$newEditFunction = '$.ajax({
                url: \'discounts-management.php?action=get_discount&discount_id=\' + discountId,
                type: \'GET\',
                dataType: \'json\',
                success: function(response) {
                    if (response.success) {
                        const discount = response.data;
                        
                        // Set form values
                        $(\'#discountId\').val(discount.id);
                        $(\'#formAction\').val(\'update_discount\');
                        $(\'#discountModalLabel\').text(\'Edit Discount\');
                        $(\'#name\').val(discount.name);
                        $(\'#description\').val(discount.description);
                        $(\'#discount_type\').val(discount.discount_type).trigger(\'change\');
                        $(\'#discount_value\').val(discount.discount_value);
                        $(\'#min_quantity\').val(discount.min_quantity);
                        $(\'#max_quantity\').val(discount.max_quantity);
                        $(\'#min_order_amount\').val(discount.min_order_amount);
                        $(\'#start_date\').val(discount.start_date);
                        $(\'#end_date\').val(discount.end_date);
                        $(\'#is_active\').val(discount.is_active);
                        $(\'#usage_limit\').val(discount.usage_limit);
                        $(\'#applies_to\').val(discount.applies_to).trigger(\'change\');
                        
                        // Set related items after a short delay
                        setTimeout(() => {
                            if (discount.related_items && discount.related_items.length > 0) {
                                if (discount.applies_to === \'specific_products\') {
                                    $(\'#selected_products\').val(discount.related_items).trigger(\'change\');
                                } else if (discount.applies_to === \'categories\') {
                                    $(\'#selected_categories\').val(discount.related_items).trigger(\'change\');
                                } else if (discount.applies_to === \'brands\') {
                                    $(\'#selected_brands\').val(discount.related_items).trigger(\'change\');
                                }
                            }
                        }, 500);
                        
                        $(\'#discountModal\').modal(\'show\');
                    } else {
                        Swal.fire({
                            icon: \'error\',
                            title: \'Error!\',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: \'error\',
                        title: \'Error!\',
                        text: \'Failed to load discount details.\'
                    });
                }
            });';

$content = preg_replace($oldEditFunction, $newEditFunction, $content);

// Save the fixed content
file_put_contents($file, $content);

echo "Discounts management file has been fixed!\n";
echo "Fixed issues:\n";
echo "1. Added missing get_discount case\n";
echo "2. Added handleGetDiscount function\n";
echo "3. Fixed edit functionality\n";
?>
