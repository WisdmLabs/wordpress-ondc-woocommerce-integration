(function ($) {
    'use strict';
    /**
     * All of the code for your admin-specific JavaScript source
     * should reside in this file.
     *
     * Note that this assume you're going to use jQuery, so it prepares
     * the $ function reference to be used within the scope of this
     * function.
     *
     * From here, you're able to define handlers for when the DOM is
     * ready:
     *
     * $(function() {
     *
     * });
     *
     * Or when the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and so on.
     *
     * Remember that ideally, we should not attach any more than a single DOM-ready or window-load handler
     * for any particular page. Though other scripts in WordPress core, other plugins, and other themes may
     * be doing this, we should try to minimize doing that in our own work.
     */
    
    $(document).ready(function () {
        var selected_category = $('#ondc_product_categories').val();
        selected_category     = selected_category.replace(':', '');
        $('#ondc_product_sub_categories_' + selected_category + '_wrapper').show();
        
        $('#ondc_product_categories').on('change', function () {
            var selected_category = $(this).val();
            selected_category     = selected_category.replace(':', '');
            $('.ondc-sub-categories').not('#ondc_product_sub_categories_' + selected_category + '_wrapper').hide();
            console.log('#ondc_product_sub_categories_' + selected_category + '_wrapper');
            $('#ondc_product_sub_categories_' + selected_category + '_wrapper').show();
        });
    });
})(jQuery);