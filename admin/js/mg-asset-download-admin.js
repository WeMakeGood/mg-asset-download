/**
 * Admin JavaScript for MG Asset Download
 * 
 * @author Christopher Frazier <chris.frazier@wemakegood.org>
 * @package MG_Asset_Download
 */
(function($) {
    'use strict';

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Handle the manual process button
        $('.mg-asset-download-manual form').on('submit', function() {
            if (confirm('Are you sure you want to manually process assets now?')) {
                $(this).find('button').prop('disabled', true).text('Processing...');
                return true;
            }
            return false;
        });
    });

})(jQuery);