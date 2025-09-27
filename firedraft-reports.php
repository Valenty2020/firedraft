<?php
/**
 * Plugin Name: Firedraft
 * Description: Generate professional WordPress site reports with PDF export capabilities.
 * Version: 3.1.0
 * Author: Firedraft
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: firedraft
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FIREDRAFT_VERSION', '3.1.0');
define('FIREDRAFT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FIREDRAFT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FIREDRAFT_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class Firedraft_Reports_Plugin {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize the plugin
     */
    private function init() {
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Include required files
        $this->include_files();

        // Initialize components
        $this->init_components();
    }

    /**
     * Include required files
     */
    private function include_files() {
        require_once FIREDRAFT_PLUGIN_PATH . 'includes/class-data-collector.php';
        require_once FIREDRAFT_PLUGIN_PATH . 'includes/class-ajax-handler.php';
        require_once FIREDRAFT_PLUGIN_PATH . 'includes/class-admin.php';
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize admin interface
        if (is_admin()) {
            new Firedraft_Reports_Admin();
        }

        // Initialize AJAX handler
        new Firedraft_Reports_Ajax_Handler();
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('Firedraft_Reports_Plugin', 'get_instance'));

// Activation hook
register_activation_hook(__FILE__, 'firedraft_reports_activate');
function firedraft_reports_activate() {
    // Set default options if needed
    if (!get_option('firedraft_report_data')) {
        update_option('firedraft_report_data', '');
    }
    if (!get_option('firedraft_selected_template')) {
        update_option('firedraft_selected_template', 'website_handover_report');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'firedraft_reports_deactivate');
function firedraft_reports_deactivate() {
    // Clean up if needed
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'firedraft_reports_uninstall');
function firedraft_reports_uninstall() {
    delete_option('firedraft_report_data');
    delete_option('firedraft_selected_template');
}