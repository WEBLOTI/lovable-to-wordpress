<?php
/**
 * Elementor Mapper Class
 * 
 * Detects active plugins (JetEngine, ACF, MetaBox, etc.)
 * Replaces placeholders with widgets, shortcodes, or Dynamic Tags
 * Maintains structure and classes for animations
 * 
 * @package Lovable_Exporter
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_Elementor_Mapper {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add hooks for placeholder replacement
        add_filter('elementor/frontend/the_content', array($this, 'replace_placeholders_in_content'), 10, 1);
        add_filter('elementor/widget/render_content', array($this, 'replace_placeholders_in_widget'), 10, 2);
    }
    
    /**
     * Replace placeholders in content
     * 
     * @param string $content Content to process
     * @return string Processed content
     */
    public function replace_placeholders_in_content($content) {
        global $post;
        
        if (!$post) {
            return $content;
        }
        
        return lovable_replace_placeholders($content, $post->ID);
    }
    
    /**
     * Replace placeholders in widget content
     * 
     * @param string $content Widget content
     * @param object $widget Widget instance
     * @return string Processed content
     */
    public function replace_placeholders_in_widget($content, $widget) {
        global $post;
        
        if (!$post) {
            return $content;
        }
        
        return lovable_replace_placeholders($content, $post->ID);
    }
    
    /**
     * Get dynamic tag for field
     * 
     * @param string $field_name Field name
     * @param string $plugin Plugin name (acf, jetengine, metabox)
     * @return string Dynamic tag shortcode
     */
    public function get_dynamic_tag($field_name, $plugin = null) {
        if (!$plugin) {
            $plugin = lovable_get_active_cpt_plugin();
        }
        
        switch ($plugin) {
            case 'acf':
                return $this->get_acf_dynamic_tag($field_name);
                
            case 'jetengine':
                return $this->get_jetengine_dynamic_tag($field_name);
                
            case 'metabox':
                return $this->get_metabox_dynamic_tag($field_name);
                
            default:
                return get_post_meta(get_the_ID(), $field_name, true);
        }
    }
    
    /**
     * Get ACF dynamic tag
     * 
     * @param string $field_name ACF field name
     * @return string Dynamic tag
     */
    private function get_acf_dynamic_tag($field_name) {
        if (function_exists('get_field')) {
            return get_field($field_name);
        }
        return '';
    }
    
    /**
     * Get JetEngine dynamic tag
     * 
     * @param string $field_name JetEngine field name
     * @return string Dynamic tag
     */
    private function get_jetengine_dynamic_tag($field_name) {
        if (function_exists('jet_engine')) {
            return get_post_meta(get_the_ID(), $field_name, true);
        }
        return '';
    }
    
    /**
     * Get MetaBox dynamic tag
     * 
     * @param string $field_name MetaBox field name
     * @return string Dynamic tag
     */
    private function get_metabox_dynamic_tag($field_name) {
        if (function_exists('rwmb_meta')) {
            return rwmb_meta($field_name);
        }
        return '';
    }
    
    /**
     * Convert placeholder to Elementor Dynamic Tag
     * 
     * @param string $placeholder Placeholder string (e.g., {{acf.price}})
     * @return string Elementor Dynamic Tag format
     */
    public function convert_to_elementor_dynamic_tag($placeholder) {
        // Parse placeholder
        preg_match('/\{\{([a-z]+)\.([a-zA-Z0-9_-]+)\}\}/', $placeholder, $matches);
        
        if (empty($matches)) {
            return $placeholder;
        }
        
        $source = $matches[1]; // acf, jet, mb, post, taxonomy
        $field = $matches[2];
        
        // Convert to Elementor dynamic tag format
        switch ($source) {
            case 'acf':
                return '[elementor-tag id="acf" name="acf-field" settings=\'{"key":"' . $field . '"}\']';
                
            case 'jet':
                return '[elementor-tag id="jet" name="jet-field" settings=\'{"field":"' . $field . '"}\']';
                
            case 'mb':
                return '[elementor-tag id="metabox" name="metabox-field" settings=\'{"key":"' . $field . '"}\']';
                
            case 'post':
                return '[elementor-tag id="post" name="post-' . $field . '"]';
                
            case 'taxonomy':
                return '[elementor-tag id="taxonomy" name="taxonomy" settings=\'{"taxonomy":"' . $field . '"}\']';
                
            default:
                return $placeholder;
        }
    }
    
    /**
     * Map Lovable placeholder to Elementor widget
     * 
     * @param string $placeholder Placeholder string
     * @param string $post_type Post type context
     * @return array Widget configuration
     */
    public function map_placeholder_to_widget($placeholder, $post_type = 'post') {
        preg_match('/\{\{([a-z]+)\.([a-zA-Z0-9_-]+)\}\}/', $placeholder, $matches);
        
        if (empty($matches)) {
            return null;
        }
        
        $source = $matches[1];
        $field = $matches[2];
        
        $widget_config = array(
            'widgetType' => 'text-editor',
            'settings' => array(),
        );
        
        // Determine widget type based on field
        $field_type = $this->get_field_type($field, $source, $post_type);
        
        switch ($field_type) {
            case 'image':
                $widget_config['widgetType'] = 'image';
                $widget_config['settings']['dynamic'] = array(
                    'image' => $this->convert_to_elementor_dynamic_tag($placeholder),
                );
                break;
                
            case 'wysiwyg':
            case 'textarea':
                $widget_config['widgetType'] = 'text-editor';
                $widget_config['settings']['dynamic'] = array(
                    'editor' => $this->convert_to_elementor_dynamic_tag($placeholder),
                );
                break;
                
            case 'url':
            case 'link':
                $widget_config['widgetType'] = 'button';
                $widget_config['settings']['dynamic'] = array(
                    'link' => $this->convert_to_elementor_dynamic_tag($placeholder),
                );
                break;
                
            default:
                $widget_config['widgetType'] = 'text-editor';
                $widget_config['settings']['editor'] = $this->convert_to_elementor_dynamic_tag($placeholder);
                break;
        }
        
        return $widget_config;
    }
    
    /**
     * Get field type from custom fields plugin
     * 
     * @param string $field_name Field name
     * @param string $source Field source (acf, jet, mb)
     * @param string $post_type Post type
     * @return string Field type
     */
    private function get_field_type($field_name, $source, $post_type) {
        switch ($source) {
            case 'acf':
                if (function_exists('acf_get_field')) {
                    $field = acf_get_field($field_name);
                    return $field['type'] ?? 'text';
                }
                break;
                
            case 'jet':
                // JetEngine field type detection
                if (class_exists('Jet_Engine')) {
                    // Get field from post type meta boxes
                    return 'text'; // Simplified for now
                }
                break;
                
            case 'mb':
                if (function_exists('rwmb_get_field_settings')) {
                    $field = rwmb_get_field_settings($field_name);
                    return $field['type'] ?? 'text';
                }
                break;
        }
        
        return 'text';
    }
    
    /**
     * Register custom Elementor widgets for Lovable
     */
    public function register_custom_widgets() {
        // This can be extended to add custom Elementor widgets
        // For now, we use standard widgets with dynamic tags
    }
    
    /**
     * Add Elementor controls for animation settings
     * 
     * @param object $element Elementor element
     */
    public function add_animation_controls($element) {
        $element->start_controls_section(
            'lovable_animation_section',
            array(
                'label' => __('Lovable Animations', 'lovable-to-wordpress'),
                'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
            )
        );
        
        $element->add_control(
            'lovable_animation_type',
            array(
                'label' => __('Animation Type', 'lovable-to-wordpress'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    '' => __('None', 'lovable-to-wordpress'),
                    'fadeIn' => __('Fade In', 'lovable-to-wordpress'),
                    'fadeInUp' => __('Fade In Up', 'lovable-to-wordpress'),
                    'fadeInDown' => __('Fade In Down', 'lovable-to-wordpress'),
                    'fadeInLeft' => __('Fade In Left', 'lovable-to-wordpress'),
                    'fadeInRight' => __('Fade In Right', 'lovable-to-wordpress'),
                    'scaleUp' => __('Scale Up', 'lovable-to-wordpress'),
                    'scaleDown' => __('Scale Down', 'lovable-to-wordpress'),
                    'slideInUp' => __('Slide In Up', 'lovable-to-wordpress'),
                    'slideInDown' => __('Slide In Down', 'lovable-to-wordpress'),
                    'slideInLeft' => __('Slide In Left', 'lovable-to-wordpress'),
                    'slideInRight' => __('Slide In Right', 'lovable-to-wordpress'),
                    'rotateIn' => __('Rotate In', 'lovable-to-wordpress'),
                    'bounceIn' => __('Bounce In', 'lovable-to-wordpress'),
                    'flipInX' => __('Flip In X', 'lovable-to-wordpress'),
                    'flipInY' => __('Flip In Y', 'lovable-to-wordpress'),
                    'zoomIn' => __('Zoom In', 'lovable-to-wordpress'),
                    'blurIn' => __('Blur In', 'lovable-to-wordpress'),
                ),
                'default' => '',
            )
        );
        
        $element->add_control(
            'lovable_animation_delay',
            array(
                'label' => __('Animation Delay (ms)', 'lovable-to-wordpress'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'max' => 3000,
                'step' => 100,
                'condition' => array(
                    'lovable_animation_type!' => '',
                ),
            )
        );
        
        $element->add_control(
            'lovable_animation_duration',
            array(
                'label' => __('Animation Duration', 'lovable-to-wordpress'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'fast' => __('Fast', 'lovable-to-wordpress'),
                    'normal' => __('Normal', 'lovable-to-wordpress'),
                    'slow' => __('Slow', 'lovable-to-wordpress'),
                ),
                'default' => 'normal',
                'condition' => array(
                    'lovable_animation_type!' => '',
                ),
            )
        );
        
        $element->end_controls_section();
    }
    
    /**
     * Render animation attributes for element
     * 
     * @param object $element Elementor element
     */
    public function render_animation_attributes($element) {
        $settings = $element->get_settings();
        
        if (empty($settings['lovable_animation_type'])) {
            return;
        }
        
        $element->add_render_attribute('_wrapper', 'data-lovable-anim', $settings['lovable_animation_type']);
        
        if (!empty($settings['lovable_animation_delay'])) {
            $element->add_render_attribute('_wrapper', 'data-lovable-delay', $settings['lovable_animation_delay']);
        }
        
        if (!empty($settings['lovable_animation_duration'])) {
            $element->add_render_attribute('_wrapper', 'data-lovable-duration', $settings['lovable_animation_duration']);
        }
    }
}
