<?php
/**
 * Elementor Builder Class
 * 
 * Converts Lovable React components to Elementor templates
 * Creates Flexbox/Grid containers and widgets
 * 
 * @package Lovable_Exporter
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_Elementor_Builder {
    
    private $analysis_result;
    private $css_data;
    private $selected_plugins;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor
    }
    
    /**
     * Build Elementor templates from Lovable project
     * 
     * @param array $analysis_result ZIP analysis result
     * @param array $css_data Extracted CSS data
     * @param array $selected_plugins User's plugin selections
     * @return array|WP_Error Built templates or error
     */
    public function build($analysis_result, $css_data, $selected_plugins = array()) {
        $this->analysis_result = $analysis_result;
        $this->css_data = $css_data;
        $this->selected_plugins = $selected_plugins;
        
        $templates = array();
        
        // Process each page
        $pages = $analysis_result['pages'] ?? array();
        
        foreach ($pages as $page) {
            $template = $this->convert_page_to_template($page);
            
            if (!is_wp_error($template)) {
                $templates[] = $template;
            }
        }
        
        return $templates;
    }
    
    /**
     * Convert a page to Elementor template
     * 
     * @param array $page Page data from analyzer
     * @return array|WP_Error Template data or error
     */
    private function convert_page_to_template($page) {
        $page_name = $page['name'];
        $content = $page['content'];
        
        // Parse React component to extract sections
        $sections = $this->parse_react_component($content);
        
        // Convert sections to Elementor format
        $elementor_data = array();
        
        foreach ($sections as $section) {
            $elementor_section = $this->create_elementor_section($section);
            if ($elementor_section) {
                $elementor_data[] = $elementor_section;
            }
        }
        
        // Create WordPress page
        $page_id = $this->create_wp_page($page_name, $elementor_data);
        
        if (is_wp_error($page_id)) {
            return $page_id;
        }
        
        return array(
            'page_id' => $page_id,
            'page_name' => $page_name,
            'sections_count' => count($elementor_data),
        );
    }
    
    /**
     * Parse React component to extract sections
     * 
     * @param string $content React component content
     * @return array Sections found
     */
    private function parse_react_component($content) {
        $sections = array();
        
        // Find <section> tags
        preg_match_all('/<section[^>]*className=["\']([^"\']*)["\'][^>]*>(.*?)<\/section>/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $className = $match[1];
            $innerHTML = $match[2];
            
            $sections[] = array(
                'type' => 'section',
                'classes' => $className,
                'content' => $innerHTML,
            );
        }
        
        // If no sections found, treat whole content as one section
        if (empty($sections)) {
            $sections[] = array(
                'type' => 'section',
                'classes' => 'lovable-section',
                'content' => $content,
            );
        }
        
        return $sections;
    }
    
    /**
     * Create Elementor section from parsed data
     * 
     * @param array $section Section data
     * @return array Elementor section structure
     */
    private function create_elementor_section($section) {
        $section_id = uniqid();
        
        $elementor_section = array(
            'id' => $section_id,
            'elType' => 'section',
            'settings' => array(
                'layout' => 'boxed',
                'css_classes' => $section['classes'] . ' lovable-section',
            ),
            'elements' => array(),
        );
        
        // Create column
        $column = $this->create_elementor_column($section['content']);
        $elementor_section['elements'][] = $column;
        
        return $elementor_section;
    }
    
    /**
     * Create Elementor column
     * 
     * @param string $content Column content
     * @return array Elementor column structure
     */
    private function create_elementor_column($content) {
        $column_id = uniqid();
        
        $column = array(
            'id' => $column_id,
            'elType' => 'column',
            'settings' => array(
                '_column_size' => 100,
                'css_classes' => 'lovable-column',
            ),
            'elements' => array(),
        );
        
        // Parse widgets from content
        $widgets = $this->parse_widgets($content);
        
        foreach ($widgets as $widget) {
            $column['elements'][] = $widget;
        }
        
        return $column;
    }
    
    /**
     * Parse widgets from content
     * 
     * @param string $content Content to parse
     * @return array Widgets found
     */
    private function parse_widgets($content) {
        $widgets = array();
        
        // Parse headings
        preg_match_all('/<h([1-6])[^>]*className=["\']([^"\']*)["\'][^>]*>(.*?)<\/h\1>/s', $content, $headings, PREG_SET_ORDER);
        
        foreach ($headings as $heading) {
            $widgets[] = $this->create_heading_widget($heading[1], $heading[3], $heading[2]);
        }
        
        // Parse paragraphs
        preg_match_all('/<p[^>]*className=["\']([^"\']*)["\'][^>]*>(.*?)<\/p>/s', $content, $paragraphs, PREG_SET_ORDER);
        
        foreach ($paragraphs as $para) {
            $widgets[] = $this->create_text_widget($para[2], $para[1]);
        }
        
        // Parse buttons
        preg_match_all('/<Button[^>]*>(.*?)<\/Button>/s', $content, $buttons, PREG_SET_ORDER);
        
        foreach ($buttons as $button) {
            $widgets[] = $this->create_button_widget($button[1]);
        }
        
        // If no widgets found, create a generic HTML widget with the content
        if (empty($widgets)) {
            $widgets[] = $this->create_html_widget($content);
        }
        
        return $widgets;
    }
    
    /**
     * Create heading widget
     * 
     * @param string $level Heading level (1-6)
     * @param string $text Heading text
     * @param string $classes CSS classes
     * @return array Elementor heading widget
     */
    private function create_heading_widget($level, $text, $classes = '') {
        return array(
            'id' => uniqid(),
            'elType' => 'widget',
            'widgetType' => 'heading',
            'settings' => array(
                'title' => strip_tags($text),
                'header_size' => 'h' . $level,
                '_css_classes' => 'lovable-widget ' . $classes,
            ),
        );
    }
    
    /**
     * Create text widget
     * 
     * @param string $text Text content
     * @param string $classes CSS classes
     * @return array Elementor text widget
     */
    private function create_text_widget($text, $classes = '') {
        return array(
            'id' => uniqid(),
            'elType' => 'widget',
            'widgetType' => 'text-editor',
            'settings' => array(
                'editor' => wp_kses_post($text),
                '_css_classes' => 'lovable-widget ' . $classes,
            ),
        );
    }
    
    /**
     * Create button widget
     * 
     * @param string $text Button text
     * @return array Elementor button widget
     */
    private function create_button_widget($text) {
        return array(
            'id' => uniqid(),
            'elType' => 'widget',
            'widgetType' => 'button',
            'settings' => array(
                'text' => strip_tags($text),
                'link' => array('url' => '#'),
                '_css_classes' => 'lovable-widget lovable-button',
            ),
        );
    }
    
    /**
     * Create HTML widget (fallback)
     * 
     * @param string $html HTML content
     * @return array Elementor HTML widget
     */
    private function create_html_widget($html) {
        // Clean up React syntax
        $html = $this->clean_react_syntax($html);
        
        return array(
            'id' => uniqid(),
            'elType' => 'widget',
            'widgetType' => 'html',
            'settings' => array(
                'html' => $html,
                '_css_classes' => 'lovable-widget lovable-html',
            ),
        );
    }
    
    /**
     * Clean React syntax from HTML
     * 
     * @param string $html HTML with React syntax
     * @return string Cleaned HTML
     */
    private function clean_react_syntax($html) {
        // Remove JSX curly braces (basic cleanup)
        $html = preg_replace('/\{[^}]+\}/', '', $html);
        
        // Convert className to class
        $html = str_replace('className=', 'class=', $html);
        
        // Remove self-closing tags syntax
        $html = str_replace('/>', '>', $html);
        
        return $html;
    }
    
    /**
     * Create WordPress page with Elementor data
     * 
     * @param string $page_name Page name
     * @param array $elementor_data Elementor content
     * @return int|WP_Error Page ID or error
     */
    private function create_wp_page($page_name, $elementor_data) {
        // Create page
        $page_id = wp_insert_post(array(
            'post_title' => $page_name,
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '', // Elementor manages content
        ));
        
        if (is_wp_error($page_id)) {
            return $page_id;
        }
        
        // Save Elementor data
        update_post_meta($page_id, '_elementor_data', wp_json_encode($elementor_data));
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        update_post_meta($page_id, '_lovable_source', true);
        update_post_meta($page_id, '_lovable_version', L2WP_VERSION);
        
        // Apply custom CSS
        if (!empty($this->css_data['custom_css'])) {
            update_post_meta($page_id, '_elementor_page_settings', array(
                'custom_css' => $this->css_data['custom_css'],
            ));
        }
        
        return $page_id;
    }
    
    /**
     * Import assets (images, fonts)
     * 
     * @return array Imported assets mapping
     */
    public function import_assets() {
        $assets = $this->analysis_result['assets'] ?? array();
        $imported = array(
            'images' => array(),
            'fonts' => array(),
        );
        
        // Import images
        foreach ($assets['images'] ?? array() as $image) {
            $imported_id = $this->import_image($image);
            
            if (!is_wp_error($imported_id)) {
                $imported['images'][$image['name']] = $imported_id;
            }
        }
        
        return $imported;
    }
    
    /**
     * Import single image to WordPress media library
     * 
     * @param array $image Image data
     * @return int|WP_Error Attachment ID or error
     */
    private function import_image($image) {
        $file_path = $image['path'];
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'Image file not found');
        }
        
        // Upload to WordPress
        $upload = wp_upload_bits(
            $image['name'],
            null,
            file_get_contents($file_path)
        );
        
        if ($upload['error']) {
            return new WP_Error('upload_error', $upload['error']);
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => wp_check_filetype($image['name'])['type'],
            'post_title' => sanitize_file_name($image['name']),
            'post_content' => '',
            'post_status' => 'inherit',
        );
        
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        // Generate metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
}
