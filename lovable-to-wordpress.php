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
define('L2WP_VERSION', '1.0.0');
define('L2WP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('L2WP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('L2WP_PLUGIN_FILE', __FILE__);

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
        require_once L2WP_PLUGIN_DIR . 'functions.php';

        // Load includes (v1 - JSON based)
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-api-bridge.php';
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-export-engine.php';
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-elementor-mapper.php';
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-asset-loader.php';
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-dynamic-tags.php';

        // Load v2 includes (ZIP-based system with intelligent detection)
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-zip-analyzer.php';
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-component-detector.php';
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-plugin-recommender.php';
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-css-extractor.php';
        require_once L2WP_PLUGIN_DIR . 'includes/class-l2wp-elementor-builder.php';

        // Initialize classes
        $this->init_classes();
    }
    
    /**
     * Initialize plugin classes
     */
    private function init_classes() {
        // Initialize Export Engine (handles the form submission)
        new L2WP_Export_Engine();
        
        // Initialize other classes as needed
        // new L2WP_Asset_Loader();
        // new L2WP_Elementor_Mapper();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        add_option('lovable_to_wordpress_version', L2WP_VERSION);
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
            L2WP_PLUGIN_URL . 'assets/css/lovable.css',
            array(),
            L2WP_VERSION,
            'all'
        );

        // Enqueue JS (with defer)
        wp_enqueue_script(
            'lovable-to-wordpress-animations',
            L2WP_PLUGIN_URL . 'assets/js/lovable-animations.js',
            array(),
            L2WP_VERSION,
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
            L2WP_PLUGIN_URL . 'assets/css/lovable.css',
            array(),
            L2WP_VERSION
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
        include L2WP_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $api_bridge = new L2WP_API_Bridge();
        $api_bridge->register_routes();
    }
    
    /**
     * Register Elementor widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        // Register custom Elementor widgets if needed
        // Example: $widgets_manager->register(new L2WP_Custom_Widget());
    }
    
    /**
     * Register dynamic tags
     */
    public function register_dynamic_tags($dynamic_tags_manager) {
        $dynamic_tags = new L2WP_Dynamic_Tags();
        $dynamic_tags->register($dynamic_tags_manager);
    }
    
    /**
     * Handle import form submission
     *
     * @return void
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
        $plugin_choices = isset($_POST['plugin_choice']) ? array_map('sanitize_text_field', $_POST['plugin_choice']) : array();
        $install_plugins = isset($_POST['install_plugins']) && $_POST['install_plugins'] === '1';
        $import_assets = isset($_POST['import_assets']) && $_POST['import_assets'] === '1';
        $apply_css = isset($_POST['apply_css']) && $_POST['apply_css'] === '1';
        $remember_preferences = isset($_POST['remember_preferences']) && $_POST['remember_preferences'] === '1';

        // Save preferences if requested
        if ($remember_preferences && !empty($plugin_choices)) {
            $recommender = new L2WP_Plugin_Recommender();
            $recommender->save_preferences($plugin_choices);
        }

        // Import status tracking
        $import_status = array(
            'status' => 'processing',
            'created_pages' => 0,
            'installed_plugins' => array(),
            'errors' => array(),
        );

        /**
         * Fire action before import begins
         *
         * @since 1.0.0
         * @param array $analysis_result The ZIP analysis data
         * @param array $plugin_choices The selected plugin choices
         */
        do_action('l2wp_before_import_start', $analysis_result, $plugin_choices);

        // 1. Install required plugins if requested
        if ($install_plugins && !empty($plugin_choices)) {
            foreach ($plugin_choices as $functionality => $plugin_slug) {
                if ($plugin_slug !== 'skip') {
                    // Attempt to activate existing plugin or install it
                    if (!l2wp_is_plugin_active($plugin_slug)) {
                        $result = l2wp_install_plugin($plugin_slug);
                        if (!is_wp_error($result)) {
                            $import_status['installed_plugins'][] = $plugin_slug;
                        } else {
                            $import_status['errors'][] = sprintf(
                                __('Failed to install %s: %s', 'lovable-to-wordpress'),
                                $plugin_slug,
                                $result->get_error_message()
                            );
                        }
                    }
                }
            }
        }

        // 2. Create Elementor pages from detected pages
        if (!empty($analysis_result['pages'])) {
            $builder = new L2WP_Elementor_Builder();

            foreach ($analysis_result['pages'] as $page_data) {
                // Create page in WordPress
                $page_id = wp_insert_post(array(
                    'post_title' => sanitize_text_field($page_data['name']),
                    'post_type' => 'page',
                    'post_status' => 'draft',
                    'post_content' => '',
                ));

                if (!is_wp_error($page_id)) {
                    // Mark as Lovable imported page
                    update_post_meta($page_id, '_l2wp_lovable_import', true);
                    update_post_meta($page_id, '_l2wp_import_timestamp', current_time('mysql'));
                    update_post_meta($page_id, '_l2wp_plugin_selections', $plugin_choices);

                    $import_status['created_pages']++;

                    /**
                     * Fire action after each page is created
                     *
                     * @since 1.0.0
                     * @param int   $page_id The created page ID
                     * @param array $page_data The page data from analysis
                     */
                    do_action('l2wp_page_created_from_lovable', $page_id, $page_data);
                }
            }
        }

        // 3. Import assets if requested
        if ($import_assets && !empty($analysis_result['assets'])) {
            $asset_count = l2wp_import_assets($analysis_result['assets']);
            $import_status['imported_assets'] = $asset_count;
        }

        // 4. Apply CSS if requested
        if ($apply_css) {
            $css_extractor = new L2WP_CSS_Extractor();
            $css_data = $css_extractor->extract($analysis_result);

            if (!empty($css_data)) {
                // Store CSS for later use
                set_transient('l2wp_extracted_css_' . get_current_user_id(), $css_data, HOUR_IN_SECONDS);
                $import_status['css_extracted'] = true;
            }
        }

        // Clean up transients
        delete_transient('lovable_analysis_' . get_current_user_id());
        delete_transient('lovable_detections_' . get_current_user_id());

        $import_status['status'] = empty($import_status['errors']) ? 'success' : 'partial';

        /**
         * Fire action after import completes
         *
         * @since 1.0.0
         * @param array $import_status Status and results of import
         */
        do_action('l2wp_after_import_complete', $import_status);

        // Redirect with status
        $redirect_url = add_query_arg(
            array(
                'page' => 'lovable-to-wordpress',
                'import_status' => $import_status['status'],
                'created_pages' => $import_status['created_pages'],
                'installed_plugins' => count($import_status['installed_plugins']),
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
