// Fix for aria-hidden accessibility issue in Bootstrap modals
// Add this JavaScript code to fix the focus management issue

$(document).ready(function() {
    // Fix for aria-hidden accessibility warning
    $('#discountModal').on('show.bs.modal', function (e) {
        // Remove aria-hidden when modal is showing
        $(this).removeAttr('aria-hidden');
    });
    
    $('#discountModal').on('hide.bs.modal', function (e) {
        // Add aria-hidden back when modal is hiding
        $(this).attr('aria-hidden', 'true');
    });
    
    // Ensure proper focus management
    $('#discountModal').on('shown.bs.modal', function (e) {
        // Focus on the first input field when modal is fully shown
        $(this).find('input:first').focus();
    });
    
    // Alternative: Use inert attribute instead of aria-hidden
    // This is a more modern approach that prevents focus issues
    $('#discountModal').on('show.bs.modal', function (e) {
        // Remove inert from modal content
        $(this).find('.modal-content').removeAttr('inert');
        // Add inert to background content
        $('body > *:not(.modal)').attr('inert', '');
    });
    
    $('#discountModal').on('hide.bs.modal', function (e) {
        // Remove inert from background content
        $('body > *').removeAttr('inert');
    });
});
