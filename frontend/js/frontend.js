

(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     *
     *
     */
    

    jQuery(document).on('click', '.wfea-search__submit, .wfea-search_cal__submit', function (e) {
        if (e.target.matches('input.wfea-search_cal__submit')) {
            e.preventDefault();

            // Get the closest form to the clicked submit button
            var form = $(this).closest('form');

            // Check if the form exists in the DOM
            if (form.length) {
                var searchVal = form.find('.wfea-search__input').val();
                var section = form.parent('div').nextAll('div').find('section.wfea').first();
                console.log(section);
                section.attr('data-wfea-search', searchVal); // Add data attribute to adjacent section

                // Render the calendar
                wfea_render_calendar();
            }
            return;
        }
        e.preventDefault();
        const url = new URL(window.location.href);
        const searchInput = jQuery('.wfea-search__input').val();
        const searchParams = {
            wfea_s: searchInput,
            wfea_s_target: jQuery('input[name="wfea_s_target"]').val(),
            _wfea_nonce: jQuery('input[name="_wfea_nonce"]').val(),
        };

        for (const [key, value] of Object.entries(searchParams)) {
            if (searchInput && value) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        }

        // Redirect to the modified URL
        window.location.href = url.toString();
    });

})(jQuery);
