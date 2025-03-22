<?php
/**
 * Plugin Name: MG Asset Download
 * Plugin URI: https://wemakegood.org
 * Description: Downloads external assets from posts/pages, adds them to the Media Library, and updates links.
 * Version: 1.0.1
 * Author: Christopher Frazier
 * Author URI: https://wemakegood.org
 * Text Domain: mg-asset-download
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MG_ASSET_DOWNLOAD_VERSION', '1.0.1');
define('MG_ASSET_DOWNLOAD_PATH', plugin_dir_path(__FILE__));
define('MG_ASSET_DOWNLOAD_URL', plugin_dir_url(__FILE__));

// Include required files
require_once MG_ASSET_DOWNLOAD_PATH . 'includes/class-mg-asset-download.php';
require_once MG_ASSET_DOWNLOAD_PATH . 'admin/class-mg-asset-download-admin.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('MG_Asset_Download', 'activate'));
register_deactivation_hook(__FILE__, array('MG_Asset_Download', 'deactivate'));

// Initialize the plugin
function run_mg_asset_download() {
    global $mg_asset_download;
    $mg_asset_download = new MG_Asset_Download();
    $mg_asset_download->run();
}
run_mg_asset_download();