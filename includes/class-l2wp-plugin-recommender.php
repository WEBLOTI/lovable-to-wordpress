<?php
/**
 * Plugin Recommender Class
 * 
 * Recommends plugins based on detected functionalities
 * Handles plugin selection and installation
 * 
 * @package Lovable_Exporter
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_Plugin_Recommender {
    
    private $mappings;
    private $detections;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_mappings();
    }
    
    /**
     * Check if Elementor Pro is active
     * 
     * @return bool True if Elementor Pro is active
     */
    private function is_elementor_pro_active() {
        return defined('ELEMENTOR_PRO_VERSION') && is_plugin_active('elementor-pro/elementor-pro.php');
    }
    
    /**
     * Check if Elementor Free is active
     * 
     * @return bool True if Elementor Free is active (but not Pro)
     */
    private function is_elementor_free_active() {
        return defined('ELEMENTOR_VERSION') && !$this->is_elementor_pro_active();
    }
    
    /**
     * Load plugin mappings
     */
    private function load_mappings() {
        $mappings_file = L2WP_PLUGIN_DIR . 'plugin-mappings.json';
        
        if (!file_exists($mappings_file)) {
            $this->mappings = array();
            return;
        }
        
        $json = file_get_contents($mappings_file);
        $this->mappings = json_decode($json, true);
    }
    
    /**
     * Get all available solutions for a functionality
     * 
     * @param string $functionality Functionality key
     * @return array Solutions with installation status
     */
    public function get_solutions_for($functionality) {
        if (!isset($this->mappings['functionality_mappings'][$functionality])) {
            return array();
        }
        
        $mapping = $this->mappings['functionality_mappings'][$functionality];
        $solutions = $mapping['recommended_solutions'];
        
        // Filter solutions based on Elementor Pro capabilities
        $solutions = $this->filter_by_elementor_capabilities($functionality, $solutions);
        
        // Check which plugins are already installed
        foreach ($solutions as &$solution) {
            $solution['installed'] = $this->is_plugin_installed($solution['slug']);
            $solution['active'] = $this->is_plugin_active($solution['slug']);
        }
        
        // Intelligent sorting based on installation status, Elementor version, and solution quality
        $has_elementor_pro = $this->is_elementor_pro_active();
        
        usort($solutions, function($a, $b) use ($has_elementor_pro) {
            // HIGHEST PRIORITY: Installed and active plugins (user's preference)
            if ($a['active'] && !$b['active']) return -1;
            if (!$a['active'] && $b['active']) return 1;
            
            // SECOND PRIORITY: Installed but not active plugins
            if ($a['installed'] && !$b['installed']) return -1;
            if (!$a['installed'] && $b['installed']) return 1;
            
            // THIRD PRIORITY: If Elementor Pro is active, prioritize Elementor Pro native solutions
            if ($has_elementor_pro) {
                $a_is_pro = ($a['slug'] === 'elementor-pro');
                $b_is_pro = ($b['slug'] === 'elementor-pro');
                
                if ($a_is_pro && !$b_is_pro) return -1;
                if (!$a_is_pro && $b_is_pro) return 1;
            }
            
            // FINAL: Sort by compatibility (best solution wins)
            return $b['compatibility'] - $a['compatibility'];
        });
        
        return $solutions;
    }
    
    /**
     * Filter solutions based on Elementor Pro capabilities
     * 
     * @param string $functionality Functionality key
     * @param array $solutions Original solutions array
     * @return array Filtered solutions
     */
    private function filter_by_elementor_capabilities($functionality, $solutions) {
        $has_elementor_pro = $this->is_elementor_pro_active();
        
        // If Elementor Pro is active, filter out redundant plugins
        if ($has_elementor_pro) {
            // Elementor Pro capabilities that COMPLETELY replace other plugins
            $elementor_pro_replaces = array(
                'popup_modal' => true,      // Elementor Pro has Popup Builder (no need for other plugins)
            );
            
            // For animations, we want to keep WPCode + Elementor Pro options
            $elementor_pro_supplements = array(
                'animations' => array('insert-headers-and-footers', 'custom_animations'), // Keep WPCode and custom animations
            );
            
            // If this functionality is completely covered by Elementor Pro (no additional plugins needed)
            if (isset($elementor_pro_replaces[$functionality])) {
                // Keep only Elementor Pro native solution and filter out external plugins
                $filtered = array();
                
                foreach ($solutions as $solution) {
                    // Keep if it's an Elementor Pro solution OR a native/elementor solution
                    if ($solution['slug'] === 'elementor-pro' || 
                        $solution['slug'] === 'elementor' ||
                        ($solution['type'] === 'native' && strpos($solution['slug'], 'elementor') !== false)) {
                        $filtered[] = $solution;
                    }
                }
                
                // If we found Elementor Pro solutions, use those, otherwise show all (fallback)
                if (!empty($filtered)) {
                    return $filtered;
                }
            }
            
            // If this functionality is supplemented by Elementor Pro (keep specific additional plugins)
            if (isset($elementor_pro_supplements[$functionality])) {
                $allowed_slugs = $elementor_pro_supplements[$functionality];
                $filtered = array();
                
                foreach ($solutions as $solution) {
                    // Keep Elementor Pro/Elementor solutions OR whitelisted plugins
                    if ($solution['slug'] === 'elementor-pro' || 
                        $solution['slug'] === 'elementor' ||
                        in_array($solution['slug'], $allowed_slugs) ||
                        ($solution['type'] === 'native' && strpos($solution['slug'], 'elementor') !== false)) {
                        $filtered[] = $solution;
                    }
                }
                
                // If we found solutions, use those, otherwise show all (fallback)
                if (!empty($filtered)) {
                    return $filtered;
                }
            }
        }
        
        return $solutions;
    }
    
    /**
     * Check if plugin is installed
     * 
     * @param string $plugin_slug Plugin slug
     * @return bool Installed status
     */
    private function is_plugin_installed($plugin_slug) {
        // For native solutions, always return true
        if ($plugin_slug === 'elementor' || $plugin_slug === 'custom_html' || $plugin_slug === 'custom_animations') {
            return true;
        }
        
        // Special check for Elementor Pro
        if ($plugin_slug === 'elementor-pro') {
            return defined('ELEMENTOR_PRO_VERSION');
        }
        
        // Check in installed plugins
        $all_plugins = get_plugins();
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            if (strpos($plugin_path, $plugin_slug . '/') === 0 || 
                strpos($plugin_path, $plugin_slug . '.php') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if plugin is active
     * 
     * @param string $plugin_slug Plugin slug
     * @return bool Active status
     */
    private function is_plugin_active($plugin_slug) {
        // For native solutions, always return true
        if ($plugin_slug === 'elementor' || $plugin_slug === 'custom_html' || $plugin_slug === 'custom_animations') {
            return true;
        }
        
        // Special check for Elementor Pro
        if ($plugin_slug === 'elementor-pro') {
            return $this->is_elementor_pro_active();
        }
        
        $all_plugins = get_plugins();
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            if (strpos($plugin_path, $plugin_slug . '/') === 0 || 
                strpos($plugin_path, $plugin_slug . '.php') !== false) {
                return is_plugin_active($plugin_path);
            }
        }
        
        return false;
    }
    
    /**
     * Install plugin from WordPress.org
     * 
     * @param string $plugin_slug Plugin slug
     * @return bool|WP_Error Success or error
     */
    public function install_plugin($plugin_slug) {
        // Native solutions don't need installation
        if ($plugin_slug === 'elementor' || $plugin_slug === 'custom_html' || $plugin_slug === 'custom_animations') {
            return true;
        }
        
        // Check if already installed
        if ($this->is_plugin_installed($plugin_slug)) {
            return true;
        }
        
        // Include necessary files
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        
        // Get plugin info from WordPress.org
        $api = plugins_api('plugin_information', array(
            'slug' => $plugin_slug,
            'fields' => array('sections' => false)
        ));
        
        if (is_wp_error($api)) {
            return $api;
        }
        
        // Install plugin
        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        $result = $upgrader->install($api->download_link);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Activate plugin
     * 
     * @param string $plugin_slug Plugin slug
     * @return bool|WP_Error Success or error
     */
    public function activate_plugin($plugin_slug) {
        // Native solutions don't need activation
        if ($plugin_slug === 'elementor' || $plugin_slug === 'custom_html' || $plugin_slug === 'custom_animations') {
            return true;
        }
        
        // Find plugin file
        $all_plugins = get_plugins();
        $plugin_file = null;
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            if (strpos($plugin_path, $plugin_slug . '/') === 0 || 
                strpos($plugin_path, $plugin_slug . '.php') !== false) {
                $plugin_file = $plugin_path;
                break;
            }
        }
        
        if (!$plugin_file) {
            return new WP_Error('plugin_not_found', __('Plugin file not found', 'lovable-to-wordpress'));
        }
        
        // Activate plugin
        $result = activate_plugin($plugin_file);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Save user preferences for future imports
     * 
     * @param array $preferences User preferences
     */
    public function save_preferences($preferences) {
        update_option('lovable_plugin_preferences', $preferences);
    }
    
    /**
     * Get saved preferences
     * 
     * @return array Saved preferences
     */
    public function get_preferences() {
        return get_option('lovable_plugin_preferences', array());
    }
    
    /**
     * Get recommended solution for a functionality based on preferences
     * 
     * @param string $functionality Functionality key
     * @return array|null Recommended solution or null
     */
    public function get_preferred_solution($functionality) {
        $preferences = $this->get_preferences();
        
        // Check if user has a saved preference
        if (isset($preferences[$functionality])) {
            $preferred_slug = $preferences[$functionality];
            $solutions = $this->get_solutions_for($functionality);
            
            foreach ($solutions as $solution) {
                if ($solution['slug'] === $preferred_slug) {
                    return $solution;
                }
            }
        }
        
        // Return highest compatibility solution
        $solutions = $this->get_solutions_for($functionality);
        return !empty($solutions) ? $solutions[0] : null;
    }
    
    /**
     * Get conversion method for selected plugin
     * 
     * @param string $functionality Functionality key
     * @param string $plugin_slug Selected plugin slug
     * @return string Conversion method name
     */
    public function get_conversion_method($functionality, $plugin_slug) {
        $solutions = $this->get_solutions_for($functionality);
        
        foreach ($solutions as $solution) {
            if ($solution['slug'] === $plugin_slug) {
                return $solution['conversion_method'];
            }
        }
        
        return 'generic_conversion';
    }
    
    /**
     * Get plugin installation stats
     * 
     * @param array $detections Detected functionalities
     * @return array Installation statistics
     */
    public function get_installation_stats($detections) {
        $stats = array(
            'total_functionalities' => count($detections),
            'plugins_needed' => 0,
            'plugins_installed' => 0,
            'plugins_active' => 0,
            'native_solutions' => 0,
        );
        
        foreach ($detections as $key => $detection) {
            $preferred = $this->get_preferred_solution($key);
            
            if ($preferred) {
                if ($preferred['type'] === 'native') {
                    $stats['native_solutions']++;
                } else {
                    $stats['plugins_needed']++;
                    
                    if ($preferred['installed']) {
                        $stats['plugins_installed']++;
                    }
                    
                    if ($preferred['active']) {
                        $stats['plugins_active']++;
                    }
                }
            }
        }
        
        return $stats;
    }
}
