<?php
/**
 * Asset Loader Class
 * 
 * Handles CSS and JS enqueuing with optimization
 * Implements defer, critical CSS, and lazy loading
 * 
 * @package Lovable_Exporter
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class L2WP_Asset_Loader {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 5);
        add_action('wp_head', array($this, 'add_critical_css'), 1);
        add_filter('script_loader_tag', array($this, 'optimize_scripts'), 10, 3);
        add_filter('style_loader_tag', array($this, 'optimize_styles'), 10, 4);
    }
    
    /**
     * Enqueue all assets
     */
    public function enqueue_assets() {
        $settings = get_option('lovable_exporter_settings', array());
        
        // Enqueue main stylesheet
        wp_enqueue_style(
            'lovable-styles',
            L2WP_PLUGIN_URL . 'assets/css/lovable.css',
            array(),
            L2WP_VERSION,
            'all'
        );
        
        // Enqueue animations script
        if ($settings['animation_enabled'] ?? true) {
            wp_enqueue_script(
                'lovable-animations',
                L2WP_PLUGIN_URL . 'assets/js/lovable-animations.js',
                array(),
                L2WP_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('lovable-animations', 'lovableSettings', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lovable_nonce'),
                'animationEnabled' => $settings['animation_enabled'] ?? true,
                'lazyloadEnabled' => $settings['lazyload_enabled'] ?? true,
            ));
        }
    }
    
    /**
     * Add critical CSS inline
     */
    public function add_critical_css() {
        $settings = get_option('lovable_exporter_settings', array());
        
        if (!($settings['critical_css_enabled'] ?? true)) {
            return;
        }
        
        // Critical CSS for above-the-fold content
        ?>
        <style id="lovable-critical-css">
            .lovable-section,.lovable-column,.lovable-widget{box-sizing:border-box}
            .lovable-section{position:relative;width:100%}
            .lovable-column{position:relative;min-height:1px}
            .lovable-widget{position:relative}
            [data-lovable-anim]:not(.lovable-animated){opacity:0}
            .lovable-animated{opacity:1}
            .lovable-image{max-width:100%;height:auto;display:block}
        </style>
        <?php
    }
    
    /**
     * Optimize script tags
     * 
     * @param string $tag Script tag
     * @param string $handle Script handle
     * @param string $src Script source
     * @return string Optimized tag
     */
    public function optimize_scripts($tag, $handle, $src) {
        // Add defer to Lovable scripts
        if (strpos($handle, 'lovable-') === 0) {
            // Don't defer if already has async or defer
            if (strpos($tag, 'async') === false && strpos($tag, 'defer') === false) {
                $tag = str_replace(' src', ' defer src', $tag);
            }
        }
        
        return $tag;
    }
    
    /**
     * Optimize style tags
     * 
     * @param string $html Style tag HTML
     * @param string $handle Style handle
     * @param string $href Stylesheet URL
     * @param string $media Media attribute
     * @return string Optimized tag
     */
    public function optimize_styles($html, $handle, $href, $media) {
        // Preload Lovable styles for better performance
        if (strpos($handle, 'lovable-') === 0) {
            $preload = sprintf(
                '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">',
                esc_url($href)
            );
            $html = $preload . $html;
        }
        
        return $html;
    }
    
    /**
     * Preload important assets
     */
    public function preload_assets() {
        ?>
        <link rel="preload" href="<?php echo L2WP_PLUGIN_URL; ?>assets/css/lovable.css" as="style">
        <link rel="preload" href="<?php echo L2WP_PLUGIN_URL; ?>assets/js/lovable-animations.js" as="script">
        <?php
    }
    
    /**
     * Get inline critical CSS
     * 
     * @return string Critical CSS content
     */
    private function get_critical_css() {
        $critical_css_file = L2WP_PLUGIN_DIR . 'assets/css/critical.css';
        
        if (file_exists($critical_css_file)) {
            return file_get_contents($critical_css_file);
        }
        
        // Default critical CSS
        return '.lovable-section,.lovable-column,.lovable-widget{box-sizing:border-box}.lovable-section{position:relative;width:100%}[data-lovable-anim]:not(.lovable-animated){opacity:0}.lovable-animated{opacity:1}';
    }
    
    /**
     * Load assets conditionally based on page
     */
    public function conditional_load() {
        // Only load on pages/posts with Lovable content
        if (!$this->has_lovable_content()) {
            return;
        }
        
        $this->enqueue_assets();
    }
    
    /**
     * Check if current page has Lovable content
     * 
     * @return bool
     */
    private function has_lovable_content() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if post was created with Lovable
        $is_lovable = get_post_meta($post->ID, '_lovable_source', true);
        
        if ($is_lovable) {
            return true;
        }
        
        // Check if content has Lovable classes
        if (strpos($post->post_content, 'lovable-') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Defer non-critical CSS
     * 
     * @param string $url CSS file URL
     */
    public function defer_css($url) {
        ?>
        <link rel="preload" href="<?php echo esc_url($url); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="<?php echo esc_url($url); ?>"></noscript>
        <?php
    }
    
    /**
     * Add resource hints for performance
     */
    public function add_resource_hints() {
        ?>
        <link rel="dns-prefetch" href="//fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
        <?php
    }
}
