<?php
/**
 * The admin-specific functionality of the plugin
 * 
 * @author Christopher Frazier <chris.frazier@wemakegood.org>
 * @package MG_Asset_Download
 */
class MG_Asset_Download_Admin {

    /**
     * The main plugin instance
     *
     * @var MG_Asset_Download
     */
    private $plugin;

    /**
     * Initialize the class
     *
     * @param MG_Asset_Download $plugin The main plugin instance
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Register the admin menu page
     */
    public function add_admin_menu() {
        add_management_page(
            __('MG Asset Download', 'mg-asset-download'),
            __('MG Asset Download', 'mg-asset-download'),
            'manage_options',
            'mg-asset-download',
            array($this, 'display_admin_page')
        );
    }

    /**
     * Register the settings
     */
    public function register_settings() {
        register_setting('mg_asset_download_settings', 'mg_asset_download_settings');
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'mg-asset-download-admin',
            MG_ASSET_DOWNLOAD_URL . 'admin/css/mg-asset-download-admin.css',
            array(),
            MG_ASSET_DOWNLOAD_VERSION,
            'all'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'mg-asset-download-admin',
            MG_ASSET_DOWNLOAD_URL . 'admin/js/mg-asset-download-admin.js',
            array('jquery'),
            MG_ASSET_DOWNLOAD_VERSION,
            false
        );
    }

    /**
     * Display the admin page
     */
    public function display_admin_page() {
        // Get post counts
        $counts = $this->plugin->get_post_counts();
        
        // Get last run time
        $last_run = get_option(MG_Asset_Download::LAST_RUN_OPTION, 'Never');
        
        // Check if process is complete
        $is_complete = $counts['total'] > 0 && $counts['processed'] >= $counts['total'];
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <?php _e('This plugin looks for external assets in your posts and pages, downloads them to your media library, and updates the links.', 'mg-asset-download'); ?>
                </p>
                <p>
                    <?php _e('The process runs automatically in the background as long as the plugin is active.', 'mg-asset-download'); ?>
                </p>
                <p>
                    <strong><?php _e('Important:', 'mg-asset-download'); ?></strong> 
                    <?php _e('Once all posts have been processed, please deactivate this plugin to prevent unnecessary background processes.', 'mg-asset-download'); ?>
                </p>
            </div>
            
            <div class="mg-asset-download-stats">
                <h2><?php _e('Progress', 'mg-asset-download'); ?></h2>
                
                <div class="mg-progress-bar-container">
                    <?php 
                    $percentage = $counts['total'] > 0 ? round(($counts['processed'] / $counts['total']) * 100) : 0;
                    ?>
                    <div class="mg-progress-bar" style="width: <?php echo esc_attr($percentage); ?>%;">
                        <span><?php echo esc_html($percentage); ?>%</span>
                    </div>
                </div>
                
                <p>
                    <strong><?php _e('Posts/Pages Processed:', 'mg-asset-download'); ?></strong> 
                    <?php echo esc_html($counts['processed']); ?> / <?php echo esc_html($counts['total']); ?>
                </p>
                
                <p>
                    <strong><?php _e('Last Run:', 'mg-asset-download'); ?></strong> 
                    <?php echo esc_html($last_run); ?>
                </p>
                
                <?php if ($is_complete): ?>
                <div class="notice notice-success">
                    <p>
                        <strong><?php _e('Process Complete!', 'mg-asset-download'); ?></strong> 
                        <?php _e('All posts and pages have been processed. You can now deactivate this plugin.', 'mg-asset-download'); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-primary">
                            <?php _e('Go to Plugins Page', 'mg-asset-download'); ?>
                        </a>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="mg-asset-download-manual">
                <h2><?php _e('Manual Process', 'mg-asset-download'); ?></h2>
                <p>
                    <?php _e('If you want to run the process manually, click the button below:', 'mg-asset-download'); ?>
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field('mg_asset_download_manual_run', 'mg_asset_download_nonce'); ?>
                    <input type="hidden" name="action" value="mg_asset_download_manual_run">
                    <button type="submit" class="button button-secondary">
                        <?php _e('Process Assets Now', 'mg-asset-download'); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Process manual run request
     */
    public function process_manual_run() {
        if (
            isset($_POST['action']) && 
            $_POST['action'] === 'mg_asset_download_manual_run' && 
            isset($_POST['mg_asset_download_nonce']) && 
            wp_verify_nonce($_POST['mg_asset_download_nonce'], 'mg_asset_download_manual_run')
        ) {
            // Run the process
            $this->plugin->process_batch();
            
            // Redirect back to the settings page with a success message
            add_settings_error(
                'mg_asset_download',
                'mg_asset_download_manual_run',
                __('Asset processing has been initiated.', 'mg-asset-download'),
                'success'
            );
        }
    }

    /**
     * Run the admin class
     */
    public function run() {
        // Add admin menu page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enqueue styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Process manual run
        add_action('admin_init', array($this, 'process_manual_run'));
    }
}