<?php
/**
 * The main plugin class
 * 
 * @author Christopher Frazier <chris.frazier@wemakegood.org>
 * @package MG_Asset_Download
 */
class MG_Asset_Download {

    /**
     * Instance of the admin class
     *
     * @var MG_Asset_Download_Admin
     */
    protected $admin;

    /**
     * The processed posts meta key
     */
    const PROCESSED_META_KEY = '_mg_asset_download_processed';

    /**
     * The last run option key
     */
    const LAST_RUN_OPTION = 'mg_asset_download_last_run';
    
    /**
     * The manual processing lock option key
     */
    const MANUAL_PROCESSING_LOCK = 'mg_asset_download_manual_processing';

    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies
     */
    private function load_dependencies() {
        $this->admin = new MG_Asset_Download_Admin($this);
    }

    /**
     * Set the locale for internationalization
     */
    private function set_locale() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Define the admin-specific hooks
     */
    private function define_admin_hooks() {
        // Register the cron event
        add_action('mg_asset_download_cron_event', array($this, 'process_batch'));

        // Add the last run option
        add_option(self::LAST_RUN_OPTION, '');
    }

    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'mg-asset-download',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Run the plugin
     */
    public function run() {
        $this->admin->run();
    }

    /**
     * Activation hook
     */
    public static function activate() {
        // Schedule the cron event
        if (!wp_next_scheduled('mg_asset_download_cron_event')) {
            wp_schedule_event(time(), 'hourly', 'mg_asset_download_cron_event');
        }
    }

    /**
     * Deactivation hook
     */
    public static function deactivate() {
        // Clear the scheduled cron event
        $timestamp = wp_next_scheduled('mg_asset_download_cron_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'mg_asset_download_cron_event');
        }
    }

    /**
     * Process a batch of posts/pages
     */
    public function process_batch() {
        // Check if manual processing is active
        $manual_processing = get_option(self::MANUAL_PROCESSING_LOCK, false);
        if ($manual_processing) {
            // Skip processing if manual processing is active
            return;
        }
        
        // Set time limit to avoid timeout
        @set_time_limit(300);

        // Get unprocessed posts/pages (limited to 5 per batch)
        $posts = $this->get_unprocessed_posts(5);

        if (empty($posts)) {
            // No more posts to process
            update_option(self::LAST_RUN_OPTION, current_time('mysql') . ' (Completed)');
            return;
        }

        foreach ($posts as $post) {
            $this->process_post($post);
        }

        // Update the last run time
        update_option(self::LAST_RUN_OPTION, current_time('mysql'));
    }

    /**
     * Get unprocessed posts and pages
     *
     * @param int $limit The number of posts to retrieve
     * @return array Array of posts
     */
    public function get_unprocessed_posts($limit = 5) {
        $args = array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => $limit,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => self::PROCESSED_META_KEY,
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => self::PROCESSED_META_KEY,
                    'value' => 'failed',
                    'compare' => '=',
                ),
            ),
        );

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Process a single post/page
     *
     * @param WP_Post $post The post to process
     */
    public function process_post($post) {
        $content = $post->post_content;
        
        // Initially mark as processing to prevent duplicate processing
        update_post_meta($post->ID, self::PROCESSED_META_KEY, 'processing');
        
        try {
            // Process external images and files
            $content = $this->process_external_assets($content, $post->ID);
            
            // Update the post content
            wp_update_post(array(
                'ID' => $post->ID,
                'post_content' => $content
            ));
            
            // Mark as processed
            update_post_meta($post->ID, self::PROCESSED_META_KEY, 'completed');
        } catch (Exception $e) {
            // Mark as failed if there's an error
            update_post_meta($post->ID, self::PROCESSED_META_KEY, 'failed');
        }
    }

    /**
     * Process external assets in content
     *
     * @param string $content The post content
     * @param int $post_id The post ID
     * @return string Modified content
     */
    private function process_external_assets($content, $post_id) {
        // Process img tags
        $content = $this->process_images($content, $post_id);
        
        // Process anchor tags with file extensions
        $content = $this->process_file_links($content, $post_id);
        
        return $content;
    }

    /**
     * Process external images in content
     *
     * @param string $content The post content
     * @param int $post_id The post ID
     * @return string Modified content
     */
    private function process_images($content, $post_id) {
        // Regex pattern to find img tags with src attribute
        $pattern = '/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i';
        
        // Find all img tags
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $index => $img_tag) {
                $src = $matches[1][$index];
                
                // Skip if it's already a local URL
                if (strpos($src, site_url()) === 0) {
                    continue;
                }
                
                // Download and attach the image
                $attachment_id = $this->download_and_attach_file($src, $post_id);
                
                if ($attachment_id) {
                    // Get the new image URL
                    $new_img_url = wp_get_attachment_url($attachment_id);
                    
                    // Replace the old src with the new one
                    $new_img_tag = str_replace($src, $new_img_url, $img_tag);
                    $content = str_replace($img_tag, $new_img_tag, $content);
                }
            }
        }
        
        return $content;
    }

    /**
     * Process external file links in content
     *
     * @param string $content The post content
     * @param int $post_id The post ID
     * @return string Modified content
     */
    private function process_file_links($content, $post_id) {
        // Regex pattern to find anchor tags with href to common file types
        $pattern = '/<a[^>]+href=[\'"]([^\'"]+\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar))[\'"][^>]*>(.*?)<\/a>/i';
        
        // Find all file links
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $index => $link_tag) {
                $href = $matches[1][$index];
                
                // Skip if it's already a local URL
                if (strpos($href, site_url()) === 0) {
                    continue;
                }
                
                // Download and attach the file
                $attachment_id = $this->download_and_attach_file($href, $post_id);
                
                if ($attachment_id) {
                    // Get the new file URL
                    $new_file_url = wp_get_attachment_url($attachment_id);
                    
                    // Replace the old href with the new one
                    $new_link_tag = str_replace($href, $new_file_url, $link_tag);
                    $content = str_replace($link_tag, $new_link_tag, $content);
                }
            }
        }
        
        return $content;
    }

    /**
     * Download an external file and attach it to a post
     *
     * @param string $url The external file URL
     * @param int $post_id The post ID to attach to
     * @return int|false The attachment ID or false on failure
     */
    private function download_and_attach_file($url, $post_id) {
        // Check if the URL is already in the media library
        $existing_attachment = $this->get_attachment_by_url($url);
        if ($existing_attachment) {
            return $existing_attachment;
        }
        
        // Get the file data
        $response = wp_remote_get($url, array(
            'timeout' => 60,
            'sslverify' => false
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        // Get file content
        $file_content = wp_remote_retrieve_body($response);
        if (empty($file_content)) {
            return false;
        }
        
        // Get the file name from the URL
        $file_name = basename(parse_url($url, PHP_URL_PATH));
        
        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();
        
        // Generate a unique file name
        $unique_file_name = wp_unique_filename($upload_dir['path'], $file_name);
        $upload_file = $upload_dir['path'] . '/' . $unique_file_name;
        
        // Save the file
        file_put_contents($upload_file, $file_content);
        
        // Check the file type
        $wp_filetype = wp_check_filetype($file_name, null);
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($file_name),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insert the attachment
        $attachment_id = wp_insert_attachment($attachment, $upload_file, $post_id);
        
        if (!is_wp_error($attachment_id)) {
            // Include image.php for media handling functions
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            // Generate attachment metadata
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            
            // Save the original URL as meta
            update_post_meta($attachment_id, '_mg_original_url', esc_url_raw($url));
            
            return $attachment_id;
        }
        
        return false;
    }

    /**
     * Check if an external URL already exists in the media library
     *
     * @param string $url The external URL
     * @return int|false The attachment ID or false
     */
    private function get_attachment_by_url($url) {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'meta_key' => '_mg_original_url',
            'meta_value' => esc_url_raw($url),
            'posts_per_page' => 1,
        ));
        
        if (!empty($attachments)) {
            return $attachments[0]->ID;
        }
        
        return false;
    }

    /**
     * Get the total count of posts/pages
     *
     * @return array Array with total and processed counts
     */
    public function get_post_counts() {
        $total_args = array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        $processed_args = array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => self::PROCESSED_META_KEY,
                    'value' => 'completed',
                    'compare' => '=',
                ),
            ),
        );
        
        $total_query = new WP_Query($total_args);
        $processed_query = new WP_Query($processed_args);
        
        return array(
            'total' => $total_query->found_posts,
            'processed' => $processed_query->found_posts,
        );
    }
}