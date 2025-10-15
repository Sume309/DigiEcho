// Fixed edit function
function editDiscount(discountId) {
    $.ajax({
        url: 'discounts-management.php?action=get_discount&discount_id=' + discountId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const discount = response.data;
                
                // Set form values
                $('#discountId').val(discount.id);
                $('#formAction').val('update_discount');
                $('#discountModalLabel').text('Edit Discount');
                $('#name').val(discount.name);
                $('#description').val(discount.description);
                $('#discount_type').val(discount.discount_type).trigger('change');
                $('#discount_value').val(discount.discount_value);
                $('#min_quantity').val(discount.min_quantity);
                $('#max_quantity').val(discount.max_quantity);
                $('#min_order_amount').val(discount.min_order_amount);
                $('#start_date').val(discount.start_date);
                $('#end_date').val(discount.end_date);
                $('#is_active').val(discount.is_active);
                $('#usage_limit').val(discount.usage_limit);
                $('#applies_to').val(discount.applies_to).trigger('change');
                
                // Set related items after a short delay to ensure dropdowns are loaded
                setTimeout(() => {
                    if (discount.related_items && discount.related_items.length > 0) {
                        if (discount.applies_to === 'specific_products') {
                            $('#selected_products').val(discount.related_items).trigger('change');
                        } else if (discount.applies_to === 'categories') {
                            $('#selected_categories').val(discount.related_items).trigger('change');
                        } else if (discount.applies_to === 'brands') {
                            $('#selected_brands').val(discount.related_items).trigger('change');
                        }
                    }
                }, 500);
                
                $('#discountModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to load discount details.'
            });
        }
    });
}
