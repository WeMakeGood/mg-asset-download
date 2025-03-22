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
        // Set up a warning when leaving the page during manual processing
        var isProcessing = false;
        
        window.addEventListener('beforeunload', function(e) {
            if (isProcessing) {
                // Show confirmation dialog
                var confirmationMessage = 'Manual processing is currently running. If you leave, the process will be interrupted and might leave the lock active. Are you sure you want to leave?';
                e.returnValue = confirmationMessage;
                return confirmationMessage;
            }
        });
        
        // Handle the manual process button
        $('.mg-asset-download-manual form').on('submit', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to manually process assets now?')) {
                var $button = $(this).find('button');
                var $progressArea = $('.mg-ajax-progress');
                var $progressCount = $('.mg-ajax-progress-count');
                var $progressBar = $('.mg-ajax-progress-bar');
                var $progressStatus = $('.mg-ajax-progress-status');
                
                // Disable button and show progress area
                $button.prop('disabled', true).text('Processing...');
                $progressArea.show();
                $progressBar.width('0%');
                $progressCount.text('0');
                $progressStatus.text('Starting...');
                
                // Set the processing flag
                isProcessing = true;
                
                // Process the first post
                processNextPost(0, $button, $progressArea, $progressCount, $progressBar, $progressStatus);
            }
            
            return false;
        });
        
        /**
         * Process the next post via AJAX
         */
        function processNextPost(processed, $button, $progressArea, $progressCount, $progressBar, $progressStatus) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mg_asset_download_process_post',
                    security: mg_asset_download_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        processed++;
                        
                        // Update progress
                        $progressCount.text(processed);
                        
                        // Calculate percentage
                        var percentage = Math.min(Math.round((processed / response.data.total) * 100), 100);
                        $progressBar.width(percentage + '%');
                        $progressBar.find('span').text(percentage + '%');
                        
                        // Update status
                        if (response.data.post_title) {
                            $progressStatus.text('Processed: ' + response.data.post_title);
                        }
                        
                        // Check if we're done or should continue
                        if (response.data.complete) {
                            $progressStatus.text('Processing complete!');
                            $button.prop('disabled', false).text('Process Assets Now');
                            
                            // Clear the processing flag
                            isProcessing = false;
                            
                            // Reload page after a short delay to show updated stats
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            // Process next post
                            setTimeout(function() {
                                processNextPost(processed, $button, $progressArea, $progressCount, $progressBar, $progressStatus);
                            }, 500);
                        }
                    } else {
                        $progressStatus.text('Error: ' + (response.data.message || 'Unknown error'));
                        $button.prop('disabled', false).text('Process Assets Now');
                        isProcessing = false;
                    }
                },
                error: function(xhr, status, error) {
                    $progressStatus.text('AJAX Error: ' + error);
                    $button.prop('disabled', false).text('Process Assets Now');
                    isProcessing = false;
                }
            });
        }
    });

})(jQuery);