<?php
/**
 * Export Engine Class
 * 
 * Takes Lovable designs and packages them for Elementor
 * Maintains containers, widgets, animations, and effects
 * 
 * @package Lovable_Exporter
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_Export_Engine {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add hooks for export functionality
        add_action('admin_post_lovable_export', array($this, 'handle_export'));
        
        // Add AJAX handler for removing template from Lovable list
        add_action('wp_ajax_lovable_remove_template', array($this, 'ajax_remove_template'));
    }
    
    /**
     * AJAX handler to remove template from Lovable list
     */
    public function ajax_remove_template() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lovable_remove_template')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'lovable-to-wordpress')
            ));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized access', 'lovable-to-wordpress')
            ));
        }
        
        // Get template ID
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        
        if (!$template_id) {
            wp_send_json_error(array(
                'message' => __('Invalid template ID', 'lovable-to-wordpress')
            ));
        }
        
        // Verify it's an Elementor template
        $post = get_post($template_id);
        if (!$post || $post->post_type !== 'elementor_library') {
            wp_send_json_error(array(
                'message' => __('Invalid template', 'lovable-to-wordpress')
            ));
        }
        
        // Remove Lovable metadata (but keep the template in Elementor)
        delete_post_meta($template_id, '_lovable_source');
        delete_post_meta($template_id, '_lovable_version');
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Template "%s" removed from Lovable Templates list. It still exists in Elementor.', 'lovable-to-wordpress'),
                $post->post_title
            )
        ));
    }
    
    /**
     * Handle export request
     */
    public function handle_export() {
        check_admin_referer('lovable_export_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'lovable-to-wordpress'));
        }
        
        $design_json = $_POST['design_data'] ?? '';
        
        if (empty($design_json)) {
            wp_die(__('No design data provided', 'lovable-to-wordpress'));
        }
        
        // Debug mode - show what PHP is receiving
        if (isset($_POST['debug_mode']) && $_POST['debug_mode'] === '1') {
            echo '<h2>Debug Information</h2>';
            echo '<h3>Raw JSON Length:</h3><p>' . strlen($design_json) . ' characters</p>';
            echo '<h3>First 500 characters:</h3><pre>' . htmlspecialchars(substr($design_json, 0, 500)) . '</pre>';
            echo '<h3>Last 500 characters:</h3><pre>' . htmlspecialchars(substr($design_json, -500)) . '</pre>';
            echo '<h3>JSON Error Test:</h3>';
            json_decode($design_json);
            echo '<p>JSON Error Code: ' . json_last_error() . '</p>';
            echo '<p>JSON Error Message: ' . json_last_error_msg() . '</p>';
            echo '<h3>Encoding:</h3><p>' . mb_detect_encoding($design_json, 'UTF-8, ISO-8859-1', true) . '</p>';
            echo '<hr><a href="' . admin_url('admin.php?page=lovable-to-wordpress') . '">Back to Plugin</a>';
            exit;
        }
        
        $result = $this->export_design($design_json);
        
        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }
        
        wp_redirect(admin_url('admin.php?page=lovable-to-wordpress&exported=' . $result));
        exit;
    }
    
    /**
     * Export Lovable design to Elementor
     * 
     * @param string $design_json JSON string of Lovable design
     * @return int|WP_Error Template ID or error
     */
    public function export_design($design_json) {
        // Trim whitespace
        $design_json = trim($design_json);
        
        // Remove WordPress slashes (magic quotes)
        $design_json = stripslashes($design_json);
        
        // Try to decode JSON
        $design_data = json_decode($design_json, true);
        
        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_msg = $this->get_json_error_message(json_last_error());
            return new WP_Error('invalid_json', sprintf(
                __('Invalid JSON: %s', 'lovable-to-wordpress'),
                $error_msg
            ));
        }
        
        if (!$design_data || !is_array($design_data)) {
            return new WP_Error('invalid_json', __('Invalid design data structure', 'lovable-to-wordpress'));
        }
        
        // Get title from proyecto or fallback
        $title = 'Lovable Design';
        if (isset($design_data['proyecto']['nombre'])) {
            $title = $design_data['proyecto']['nombre'];
        } elseif (isset($design_data['title'])) {
            $title = $design_data['title'];
        }
        
        // Convert to Elementor format
        $elementor_data = $this->convert_to_elementor($design_data);
        
        // Create Elementor template
        $template_id = $this->create_elementor_template($elementor_data, $title);
        
        return $template_id;
    }
    
    /**
     * Get human-readable JSON error message
     * 
     * @param int $error_code JSON error code
     * @return string Error message
     */
    private function get_json_error_message($error_code) {
        switch ($error_code) {
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Invalid or malformed JSON';
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters';
            default:
                return 'Unknown JSON error';
        }
    }
    
    /**
     * Convert Lovable design to Elementor format
     * 
     * @param array $design_data Lovable design data
     * @return array Elementor-compatible data
     */
    public function convert_to_elementor($design_data) {
        // Check if it's the real Lovable project format
        if (isset($design_data['proyecto'])) {
            return $this->convert_lovable_project($design_data['proyecto']);
        }
        
        // Fallback to simple format
        $elementor_structure = array(
            'version' => '0.4',
            'title' => $design_data['title'] ?? 'Lovable Design',
            'type' => $design_data['type'] ?? 'page',
            'content' => array(),
        );
        
        // Process sections
        if (!empty($design_data['sections'])) {
            foreach ($design_data['sections'] as $section) {
                $elementor_structure['content'][] = $this->convert_section($section);
            }
        }
        
        return $elementor_structure;
    }
    
    /**
     * Convert Lovable project format to Elementor
     * 
     * @param array $proyecto Lovable project data
     * @return array Elementor structure
     */
    private function convert_lovable_project($proyecto) {
        $project_name = $proyecto['nombre'] ?? 'Lovable Project';
        
        $elementor_structure = array(
            'version' => '0.4',
            'title' => $project_name,
            'type' => 'page',
            'content' => array(),
        );
        
        // Process pages from estructura_paginas
        if (!empty($proyecto['estructura_paginas']['paginas'])) {
            foreach ($proyecto['estructura_paginas']['paginas'] as $page) {
                // Convert each page section to Elementor sections
                if (!empty($page['secciones'])) {
                    foreach ($page['secciones'] as $seccion) {
                        $elementor_structure['content'][] = $this->convert_lovable_seccion($seccion);
                    }
                }
            }
        }
        
        return $elementor_structure;
    }
    
    /**
     * Convert Lovable seccion to Elementor section
     * 
     * @param array $seccion Lovable section data
     * @return array Elementor section
     */
    private function convert_lovable_seccion($seccion) {
        $section_id = uniqid();
        $section_name = $seccion['nombre'] ?? 'Section';
        
        $elementor_section = array(
            'id' => $section_id,
            'elType' => 'section',
            'settings' => array(
                'layout' => 'boxed',
                'css_classes' => 'lovable-section',
            ),
            'elements' => array(),
        );
        
        // Create a single column for the content
        $column = array(
            'id' => uniqid(),
            'elType' => 'column',
            'settings' => array(
                '_column_size' => 100,
                'css_classes' => 'lovable-column',
            ),
            'elements' => array(),
        );
        
        // Add heading for section name
        $column['elements'][] = array(
            'id' => uniqid(),
            'elType' => 'widget',
            'widgetType' => 'heading',
            'settings' => array(
                'title' => $section_name,
                'header_size' => 'h2',
                '_css_classes' => 'lovable-widget',
                '_lovable_animation' => 'fadeInUp',
            ),
        );
        
        // Add content text if available
        if (!empty($seccion['contenido'])) {
            $column['elements'][] = array(
                'id' => uniqid(),
                'elType' => 'widget',
                'widgetType' => 'text-editor',
                'settings' => array(
                    'editor' => $seccion['contenido'],
                    '_css_classes' => 'lovable-widget',
                ),
            );
        }
        
        // Process elementos (buttons, cards, etc.)
        if (!empty($seccion['elementos'])) {
            foreach ($seccion['elementos'] as $elemento) {
                $widget = $this->convert_lovable_elemento($elemento);
                if ($widget) {
                    $column['elements'][] = $widget;
                }
            }
        }
        
        // Process cards if available
        if (!empty($seccion['cards'])) {
            foreach ($seccion['cards'] as $card) {
                $column['elements'][] = $this->convert_lovable_card($card);
            }
        }
        
        // Process buttons if available
        if (!empty($seccion['botones'])) {
            foreach ($seccion['botones'] as $btn_text) {
                $column['elements'][] = array(
                    'id' => uniqid(),
                    'elType' => 'widget',
                    'widgetType' => 'button',
                    'settings' => array(
                        'text' => $btn_text,
                        'link' => array('url' => '#'),
                        '_css_classes' => 'lovable-widget',
                        '_lovable_animation' => 'scaleUp',
                    ),
                );
            }
        }
        
        $elementor_section['elements'][] = $column;
        
        return $elementor_section;
    }
    
    /**
     * Convert Lovable elemento to Elementor widget
     * 
     * @param mixed $elemento Element data
     * @return array|null Elementor widget or null
     */
    private function convert_lovable_elemento($elemento) {
        if (is_string($elemento)) {
            // Simple text element
            return array(
                'id' => uniqid(),
                'elType' => 'widget',
                'widgetType' => 'text-editor',
                'settings' => array(
                    'editor' => $elemento,
                    '_css_classes' => 'lovable-widget',
                ),
            );
        }
        
        return null;
    }
    
    /**
     * Convert Lovable card to Elementor widgets
     * 
     * @param array $card Card data
     * @return array Elementor widget
     */
    private function convert_lovable_card($card) {
        $title = $card['titulo'] ?? '';
        $description = $card['descripcion'] ?? '';
        $icon = $card['icono'] ?? '';
        
        // Return an icon-box widget if we have all parts
        return array(
            'id' => uniqid(),
            'elType' => 'widget',
            'widgetType' => 'icon-box',
            'settings' => array(
                'title_text' => $title,
                'description_text' => $description,
                'icon' => array('value' => 'fas fa-' . strtolower($icon)),
                '_css_classes' => 'lovable-widget lovable-card',
                '_lovable_animation' => 'fadeInUp',
            ),
        );
    }
    
    /**
     * Convert section from Lovable to Elementor
     * 
     * @param array $section Lovable section data
     * @return array Elementor section
     */
    private function convert_section($section) {
        $elementor_section = array(
            'id' => $this->generate_id(),
            'elType' => 'section',
            'settings' => array(
                'layout' => $section['layout'] ?? 'boxed',
                'content_width' => $section['content_width'] ?? 'boxed',
                'gap' => $section['gap'] ?? 'default',
                'height' => $section['height'] ?? 'default',
            ),
            'elements' => array(),
        );
        
        // Add custom classes
        if (!empty($section['classes'])) {
            $elementor_section['settings']['css_classes'] = 'lovable-section ' . $section['classes'];
        } else {
            $elementor_section['settings']['css_classes'] = 'lovable-section';
        }
        
        // Add animation attributes
        if (!empty($section['animation'])) {
            $elementor_section['settings']['_lovable_animation'] = $section['animation'];
            $elementor_section['settings']['css_classes'] .= ' ' . $this->get_animation_attributes($section['animation']);
        }
        
        // Add background settings
        if (!empty($section['background'])) {
            $elementor_section['settings'] = array_merge(
                $elementor_section['settings'],
                $this->convert_background($section['background'])
            );
        }
        
        // Process columns
        if (!empty($section['columns'])) {
            foreach ($section['columns'] as $column) {
                $elementor_section['elements'][] = $this->convert_column($column);
            }
        }
        
        return $elementor_section;
    }
    
    /**
     * Convert column from Lovable to Elementor
     * 
     * @param array $column Lovable column data
     * @return array Elementor column
     */
    private function convert_column($column) {
        $elementor_column = array(
            'id' => $this->generate_id(),
            'elType' => 'column',
            'settings' => array(
                '_column_size' => $column['width'] ?? 100,
                '_inline_size' => $column['width'] ?? null,
            ),
            'elements' => array(),
        );
        
        // Add custom classes
        if (!empty($column['classes'])) {
            $elementor_column['settings']['css_classes'] = 'lovable-column ' . $column['classes'];
        } else {
            $elementor_column['settings']['css_classes'] = 'lovable-column';
        }
        
        // Add animation
        if (!empty($column['animation'])) {
            $elementor_column['settings']['_lovable_animation'] = $column['animation'];
            $elementor_column['settings']['css_classes'] .= ' ' . $this->get_animation_attributes($column['animation']);
        }
        
        // Process widgets
        if (!empty($column['widgets'])) {
            foreach ($column['widgets'] as $widget) {
                $elementor_column['elements'][] = $this->convert_widget($widget);
            }
        }
        
        return $elementor_column;
    }
    
    /**
     * Convert widget from Lovable to Elementor
     * 
     * @param array $widget Lovable widget data
     * @return array Elementor widget
     */
    private function convert_widget($widget) {
        $widget_type = $this->map_widget_type($widget['type']);
        
        $elementor_widget = array(
            'id' => $this->generate_id(),
            'elType' => 'widget',
            'widgetType' => $widget_type,
            'settings' => array(),
        );
        
        // Add custom classes
        $classes = 'lovable-widget';
        if (!empty($widget['classes'])) {
            $classes .= ' ' . $widget['classes'];
        }
        
        // Add animation
        if (!empty($widget['animation'])) {
            $classes .= ' ' . $this->get_animation_attributes($widget['animation']);
            $elementor_widget['settings']['_lovable_animation'] = $widget['animation'];
        }
        
        $elementor_widget['settings']['_css_classes'] = $classes;
        
        // Convert widget-specific settings
        $elementor_widget['settings'] = array_merge(
            $elementor_widget['settings'],
            $this->convert_widget_settings($widget)
        );
        
        return $elementor_widget;
    }
    
    /**
     * Map Lovable widget type to Elementor widget type
     * 
     * @param string $lovable_type Lovable widget type
     * @return string Elementor widget type
     */
    private function map_widget_type($lovable_type) {
        $mapping = array(
            'heading' => 'heading',
            'text' => 'text-editor',
            'paragraph' => 'text-editor',
            'image' => 'image',
            'button' => 'button',
            'divider' => 'divider',
            'spacer' => 'spacer',
            'icon' => 'icon',
            'video' => 'video',
            'html' => 'html',
            'shortcode' => 'shortcode',
            'icon-box' => 'icon-box',
            'image-box' => 'image-box',
            'star-rating' => 'star-rating',
            'testimonial' => 'testimonial',
            'counter' => 'counter',
            'progress' => 'progress',
            'accordion' => 'accordion',
            'tabs' => 'tabs',
            'toggle' => 'toggle',
        );
        
        return $mapping[$lovable_type] ?? 'text-editor';
    }
    
    /**
     * Convert widget settings from Lovable to Elementor
     * 
     * @param array $widget Lovable widget data
     * @return array Elementor widget settings
     */
    private function convert_widget_settings($widget) {
        $settings = array();
        $type = $widget['type'];
        
        switch ($type) {
            case 'heading':
                $settings['title'] = $widget['content'] ?? '';
                $settings['header_size'] = $widget['tag'] ?? 'h2';
                $settings['align'] = $widget['align'] ?? 'left';
                break;
                
            case 'text':
            case 'paragraph':
                $settings['editor'] = $widget['content'] ?? '';
                break;
                
            case 'image':
                if (!empty($widget['src'])) {
                    $settings['image'] = array('url' => $widget['src']);
                }
                $settings['image_size'] = $widget['size'] ?? 'full';
                $settings['align'] = $widget['align'] ?? 'center';
                $settings['caption'] = $widget['caption'] ?? '';
                break;
                
            case 'button':
                $settings['text'] = $widget['text'] ?? 'Click Here';
                $settings['link'] = array('url' => $widget['url'] ?? '#');
                $settings['size'] = $widget['size'] ?? 'md';
                $settings['align'] = $widget['align'] ?? 'left';
                break;
                
            case 'icon':
                $settings['icon'] = array('value' => $widget['icon'] ?? 'fas fa-star');
                $settings['view'] = $widget['view'] ?? 'default';
                break;
                
            case 'video':
                $settings['youtube_url'] = $widget['url'] ?? '';
                $settings['video_type'] = $widget['video_type'] ?? 'youtube';
                break;
        }
        
        // Add placeholder replacement if content contains placeholders
        if (isset($settings['editor'])) {
            $settings['editor'] = $this->process_placeholders($settings['editor']);
        }
        if (isset($settings['title'])) {
            $settings['title'] = $this->process_placeholders($settings['title']);
        }
        
        return $settings;
    }
    
    /**
     * Process placeholders in content
     * 
     * @param string $content Content with placeholders
     * @return string Processed content
     */
    private function process_placeholders($content) {
        // For now, keep placeholders as-is
        // They will be replaced on render
        return $content;
    }
    
    /**
     * Convert background settings
     * 
     * @param array $background Lovable background data
     * @return array Elementor background settings
     */
    private function convert_background($background) {
        $settings = array();
        
        if (!empty($background['type'])) {
            $settings['background_background'] = $background['type'];
            
            switch ($background['type']) {
                case 'color':
                    $settings['background_color'] = $background['color'] ?? '#ffffff';
                    break;
                    
                case 'gradient':
                    $settings['background_color'] = $background['color'] ?? '#ffffff';
                    $settings['background_color_b'] = $background['color_b'] ?? '#000000';
                    $settings['background_gradient_angle'] = $background['angle'] ?? 180;
                    break;
                    
                case 'image':
                    if (!empty($background['image'])) {
                        $settings['background_image'] = array('url' => $background['image']);
                    }
                    $settings['background_position'] = $background['position'] ?? 'center center';
                    $settings['background_size'] = $background['size'] ?? 'cover';
                    break;
            }
        }
        
        return $settings;
    }
    
    /**
     * Get animation attributes for HTML
     * 
     * @param mixed $animation Animation data
     * @return string HTML attributes string
     */
    private function get_animation_attributes($animation) {
        if (is_string($animation)) {
            return 'data-lovable-anim="' . esc_attr($animation) . '"';
        }
        
        if (is_array($animation)) {
            $attrs = array();
            $attrs[] = 'data-lovable-anim="' . esc_attr($animation['type'] ?? 'fadeIn') . '"';
            
            if (!empty($animation['delay'])) {
                $attrs[] = 'data-lovable-delay="' . esc_attr($animation['delay']) . '"';
            }
            
            if (!empty($animation['duration'])) {
                $attrs[] = 'data-lovable-duration="' . esc_attr($animation['duration']) . '"';
            }
            
            return implode(' ', $attrs);
        }
        
        return '';
    }
    
    /**
     * Create Elementor template
     * 
     * @param array $elementor_data Elementor structure
     * @param string $title Template title
     * @return int|WP_Error Template ID or error
     */
    private function create_elementor_template($elementor_data, $title = 'Lovable Design') {
        // Create template post
        $template_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($template_id)) {
            return $template_id;
        }
        
        // Save Elementor data
        update_post_meta($template_id, '_elementor_data', wp_json_encode($elementor_data['content']));
        update_post_meta($template_id, '_elementor_template_type', $elementor_data['type']);
        update_post_meta($template_id, '_elementor_edit_mode', 'builder');
        update_post_meta($template_id, '_lovable_source', true);
        update_post_meta($template_id, '_lovable_version', L2WP_VERSION);
        
        return $template_id;
    }
    
    /**
     * Generate unique ID
     * 
     * @return string Unique ID
     */
    private function generate_id() {
        return uniqid();
    }
    
    /**
     * Export as JSON file
     * 
     * @param array $elementor_data Elementor data
     * @param string $filename Output filename
     * @return bool Success status
     */
    public function export_as_json($elementor_data, $filename = 'lovable-export.json') {
        $json = json_encode($elementor_data, JSON_PRETTY_PRINT);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        
        echo $json;
        exit;
    }
}
