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
         * 
         * @param {number} processed The number of posts processed so far
         * @param {jQuery} $button The submit button element
         * @param {jQuery} $progressArea The progress area element
         * @param {jQuery} $progressCount The progress count element
         * @param {jQuery} $progressBar The progress bar element
         * @param {jQuery} $progressStatus The progress status element
         * @param {number} retries The number of consecutive retries attempted (default 0)
         */
        function processNextPost(processed, $button, $progressArea, $progressCount, $progressBar, $progressStatus, retries = 0) {
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
                            var statusText = 'Processed: ' + response.data.post_title;
                            
                            // Add warning indicator if there were issues
                            if (response.data.has_warnings) {
                                statusText += ' (with warnings)';
                            }
                            
                            $progressStatus.text(statusText);
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
                            // Reset retry counter on success
                            setTimeout(function() {
                                processNextPost(processed, $button, $progressArea, $progressCount, $progressBar, $progressStatus, 0);
                            }, 500);
                        }
                    } else {
                        $progressStatus.text('Error: ' + (response.data.message || 'Unknown error'));
                        $button.prop('disabled', false).text('Process Assets Now');
                        isProcessing = false;
                    }
                },
                error: function(xhr, status, error) {
                    var statusCode = xhr.status;
                    
                    // Check if it's a server error (5xx status code) or other temporary error
                    // Limit to 5 consecutive retries
                    var maxRetries = 5;
                    
                    if ((statusCode >= 500 || status === 'timeout' || status === 'parsererror') && retries < maxRetries) {
                        // Increment retry counter
                        retries++;
                        
                        // Calculate backoff time (increases with each retry)
                        var backoffTime = 3000 + (retries * 2000);
                        
                        // Update status to show retrying
                        $progressStatus.text('Server error (' + statusCode + '): ' + error + '. Retry ' + retries + '/' + maxRetries + ' in ' + (backoffTime/1000) + ' seconds...');
                        
                        // Wait and retry with increasing backoff
                        setTimeout(function() {
                            $progressStatus.text('Retrying (attempt ' + retries + '/' + maxRetries + ')...');
                            processNextPost(processed, $button, $progressArea, $progressCount, $progressBar, $progressStatus, retries);
                        }, backoffTime);
                        
                        // Don't clear processing flag as we're going to retry
                    } else {
                        // Handle max retries reached or non-server errors
                        if (retries >= maxRetries) {
                            $progressStatus.text('Max retries reached after server errors. Processing halted. You can try again later.');
                        } else {
                            // For other errors (like 4xx), don't retry
                            $progressStatus.text('AJAX Error: ' + error + ' (Status: ' + statusCode + '). Processing halted.');
                        }
                        
                        // Add a helpful message about the lock
                        $progressStatus.append('<br><br><em>Note: You may need to clear the manual processing lock from the admin page if you restart.</em>');
                        
                        $button.prop('disabled', false).text('Process Assets Now');
                        isProcessing = false;
                    }
                }
            });
        }
    });

})(jQuery);