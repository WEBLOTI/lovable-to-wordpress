<?php
/**
 * Plugin Name: Lovable to WordPress
 * Plugin URI: https://github.com/WEBLOTI/lovable-to-wordpress
 * Description: Export Lovable designs to WordPress with full Elementor compatibility. Supports animations, dynamic content, and custom post types.
 * Version: 1.0.0
 * Author: WEBLOTI
 * Author URI: https://github.com/WEBLOTI
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lovable-to-wordpress
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LOVABLE_TO_WORDPRESS_VERSION', '1.0.0');
define('LOVABLE_TO_WORDPRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOVABLE_TO_WORDPRESS_DIR', plugin_dir_path(__FILE__)); // Alias for v2 classes
define('LOVABLE_TO_WORDPRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LOVABLE_TO_WORDPRESS_PLUGIN_FILE', __FILE__);

/**
 * Main Lovable to WordPress Class
 */
class L2WP_Main {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_editor_assets'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Register Elementor widgets
        add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
        
        // Add Elementor dynamic tags support
        add_action('elementor/dynamic_tags/register', array($this, 'register_dynamic_tags'));
        
        // Register admin post handler for import
        add_action('admin_post_lovable_import_project', array($this, 'handle_import_submission'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load functions
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'functions.php';

        // Load includes (v1 - JSON based)
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-api-bridge.php';
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-export-engine.php';
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-elementor-mapper.php';
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-asset-loader.php';
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-dynamic-tags.php';

        // Load v2 includes (ZIP-based system with intelligent detection)
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-zip-analyzer.php';
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-component-detector.php';
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-plugin-recommender.php';
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-css-extractor.php';
        require_once LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'includes/class-elementor-builder.php';

        // Initialize classes
        $this->init_classes();
    }
    
    /**
     * Initialize plugin classes
     */
    private function init_classes() {
        // Initialize Export Engine (handles the form submission)
        new Lovable_Export_Engine();
        
        // Initialize other classes as needed
        // new Lovable_Asset_Loader();
        // new Lovable_Elementor_Mapper();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        add_option('lovable_to_wordpress_version', LOVABLE_TO_WORDPRESS_VERSION);
        add_option('lovable_to_wordpress_settings', array(
            'animation_enabled' => true,
            'lazyload_enabled' => true,
            'critical_css_enabled' => true,
        ));

        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Enqueue CSS
        wp_enqueue_style(
            'lovable-to-wordpress-styles',
            LOVABLE_TO_WORDPRESS_PLUGIN_URL . 'assets/css/lovable.css',
            array(),
            LOVABLE_TO_WORDPRESS_VERSION,
            'all'
        );

        // Enqueue JS (with defer)
        wp_enqueue_script(
            'lovable-to-wordpress-animations',
            LOVABLE_TO_WORDPRESS_PLUGIN_URL . 'assets/js/lovable-animations.js',
            array(),
            LOVABLE_TO_WORDPRESS_VERSION,
            true
        );

        // Add defer attribute
        add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);

        // Localize script with settings
        wp_localize_script('lovable-to-wordpress-animations', 'lovableSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lovable_to_wordpress_nonce'),
            'animationEnabled' => get_option('lovable_to_wordpress_settings')['animation_enabled'],
        ));
    }
    
    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_style(
            'lovable-to-wordpress-editor',
            LOVABLE_TO_WORDPRESS_PLUGIN_URL . 'assets/css/lovable.css',
            array(),
            LOVABLE_TO_WORDPRESS_VERSION
        );
    }
    
    /**
     * Add defer attribute to scripts
     */
    public function add_defer_attribute($tag, $handle) {
        if ('lovable-to-wordpress-animations' === $handle) {
            return str_replace(' src', ' defer src', $tag);
        }
        return $tag;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Lovable to WordPress', 'lovable-to-wordpress'),
            __('Lovable to WordPress', 'lovable-to-wordpress'),
            'manage_options',
            'lovable-to-wordpress',
            array($this, 'render_admin_page'),
            'dashicons-download',
            30
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        include LOVABLE_TO_WORDPRESS_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $api_bridge = new Lovable_API_Bridge();
        $api_bridge->register_routes();
    }
    
    /**
     * Register Elementor widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        // Register custom Elementor widgets if needed
        // Example: $widgets_manager->register(new Lovable_Custom_Widget());
    }
    
    /**
     * Register dynamic tags
     */
    public function register_dynamic_tags($dynamic_tags_manager) {
        $dynamic_tags = new Lovable_Dynamic_Tags();
        $dynamic_tags->register($dynamic_tags_manager);
    }
    
    /**
     * Handle import form submission
     */
    public function handle_import_submission() {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'lovable_import_with_plugins')) {
            wp_die(__('Security check failed', 'lovable-to-wordpress'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'lovable-to-wordpress'));
        }
        
        // Get analysis data from transient
        $analysis_result = get_transient('lovable_analysis_' . get_current_user_id());
        $detections = get_transient('lovable_detections_' . get_current_user_id());
        
        if (!$analysis_result) {
            wp_die(__('No analysis data found. Please upload your ZIP file again.', 'lovable-to-wordpress'));
        }
        
        // Get selected plugins
        $plugin_choices = isset($_POST['plugin_choice']) ? $_POST['plugin_choice'] : array();
        $install_plugins = isset($_POST['install_plugins']) && $_POST['install_plugins'] === '1';
        $import_assets = isset($_POST['import_assets']) && $_POST['import_assets'] === '1';
        $apply_css = isset($_POST['apply_css']) && $_POST['apply_css'] === '1';
        $remember_preferences = isset($_POST['remember_preferences']) && $_POST['remember_preferences'] === '1';
        
        // Save preferences if requested
        if ($remember_preferences && !empty($plugin_choices)) {
            $recommender = new Lovable_Plugin_Recommender();
            $recommender->save_preferences($plugin_choices);
        }
        
        // TODO: Implement actual import logic
        // For now, just redirect with a success message
        $redirect_url = add_query_arg(
            array(
                'page' => 'lovable-to-wordpress',
                'import_status' => 'success',
                'message' => urlencode('Import functionality is under development. Your selections have been saved.')
            ),
            admin_url('admin.php')
        );
        
        wp_redirect($redirect_url);
        exit;
    }
}

/**
 * Initialize the plugin
 */
function l2wp_init() {
    return L2WP_Main::get_instance();
}

// Start the plugin
l2wp_init();
