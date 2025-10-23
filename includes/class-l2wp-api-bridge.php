<?php
/**
 * API Bridge Class
 * 
 * Connects Lovable with WordPress via REST API
 * Extracts CPTs, taxonomies, and custom fields
 * 
 * @package Lovable_Exporter
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_API_Bridge {
    
    /**
     * REST API namespace
     */
    const NAMESPACE = 'lovable/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor can be used for initialization if needed
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get all custom post types
        register_rest_route(self::NAMESPACE, '/post-types', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_post_types'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Get custom fields for a post type
        register_rest_route(self::NAMESPACE, '/post-types/(?P<post_type>[a-zA-Z0-9_-]+)/fields', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_custom_fields'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Get taxonomies for a post type
        register_rest_route(self::NAMESPACE, '/post-types/(?P<post_type>[a-zA-Z0-9_-]+)/taxonomies', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_taxonomies'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Get posts with custom fields
        register_rest_route(self::NAMESPACE, '/posts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_posts_with_fields'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Export design to Elementor
        register_rest_route(self::NAMESPACE, '/export', array(
            'methods' => 'POST',
            'callback' => array($this, 'export_design'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Get mapper configuration
        register_rest_route(self::NAMESPACE, '/mapper', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_mapper_config'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Update mapper configuration
        register_rest_route(self::NAMESPACE, '/mapper', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_mapper_config'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }
    
    /**
     * Check permission for API access
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get all custom post types
     */
    public function get_post_types($request) {
        $post_types = lovable_get_custom_post_types();
        
        $result = array();
        foreach ($post_types as $key => $post_type) {
            $result[] = array(
                'slug' => $key,
                'name' => $post_type->label,
                'singular_name' => $post_type->labels->singular_name,
                'supports' => get_all_post_type_supports($key),
                'has_archive' => $post_type->has_archive,
                'hierarchical' => $post_type->hierarchical,
            );
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get custom fields for a post type
     */
    public function get_custom_fields($request) {
        $post_type = $request->get_param('post_type');
        
        if (!post_type_exists($post_type)) {
            return new WP_Error('invalid_post_type', __('Invalid post type', 'lovable-to-wordpress'), array('status' => 404));
        }
        
        $fields = lovable_get_custom_fields($post_type);
        $plugin = lovable_get_active_cpt_plugin();
        
        $formatted_fields = array();
        
        foreach ($fields as $field) {
            $field_data = array(
                'name' => $field['name'] ?? $field['id'] ?? '',
                'label' => $field['label'] ?? $field['title'] ?? '',
                'type' => $field['type'] ?? 'text',
                'plugin' => $plugin,
            );
            
            // Add placeholder format
            switch ($plugin) {
                case 'acf':
                    $field_data['placeholder'] = '{{acf.' . $field_data['name'] . '}}';
                    break;
                case 'jetengine':
                    $field_data['placeholder'] = '{{jet.' . $field_data['name'] . '}}';
                    break;
                case 'metabox':
                    $field_data['placeholder'] = '{{mb.' . $field_data['name'] . '}}';
                    break;
                default:
                    $field_data['placeholder'] = '{{meta.' . $field_data['name'] . '}}';
            }
            
            $formatted_fields[] = $field_data;
        }
        
        return rest_ensure_response($formatted_fields);
    }
    
    /**
     * Get taxonomies for a post type
     */
    public function get_taxonomies($request) {
        $post_type = $request->get_param('post_type');
        
        if (!post_type_exists($post_type)) {
            return new WP_Error('invalid_post_type', __('Invalid post type', 'lovable-to-wordpress'), array('status' => 404));
        }
        
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        
        $result = array();
        foreach ($taxonomies as $key => $taxonomy) {
            $result[] = array(
                'slug' => $key,
                'name' => $taxonomy->label,
                'singular_name' => $taxonomy->labels->singular_name,
                'hierarchical' => $taxonomy->hierarchical,
                'placeholder' => '{{taxonomy.' . $key . '}}',
            );
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get posts with custom fields
     */
    public function get_posts_with_fields($request) {
        $post_type = $request->get_param('post_type') ?: 'post';
        $per_page = $request->get_param('per_page') ?: 10;
        $page = $request->get_param('page') ?: 1;
        
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
        );
        
        $query = new WP_Query($args);
        $posts = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $post_data = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'content' => get_the_content(),
                    'date' => get_the_date(),
                    'author' => get_the_author(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($post_id, 'full'),
                );
                
                // Add custom fields
                $fields = lovable_get_custom_fields($post_type);
                $plugin = lovable_get_active_cpt_plugin();
                
                $custom_fields = array();
                foreach ($fields as $field) {
                    $field_name = $field['name'] ?? $field['id'] ?? '';
                    
                    switch ($plugin) {
                        case 'acf':
                            $custom_fields[$field_name] = get_field($field_name, $post_id);
                            break;
                        case 'jetengine':
                        case 'metabox':
                            $custom_fields[$field_name] = get_post_meta($post_id, $field_name, true);
                            break;
                    }
                }
                
                $post_data['custom_fields'] = $custom_fields;
                
                // Add taxonomies
                $taxonomies = get_object_taxonomies($post_type);
                $taxonomy_data = array();
                
                foreach ($taxonomies as $taxonomy) {
                    $terms = get_the_terms($post_id, $taxonomy);
                    if ($terms && !is_wp_error($terms)) {
                        $taxonomy_data[$taxonomy] = array_map(function($term) {
                            return array(
                                'id' => $term->term_id,
                                'name' => $term->name,
                                'slug' => $term->slug,
                            );
                        }, $terms);
                    }
                }
                
                $post_data['taxonomies'] = $taxonomy_data;
                
                $posts[] = $post_data;
            }
            wp_reset_postdata();
        }
        
        $response = array(
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Export design to Elementor
     */
    public function export_design($request) {
        $design_data = $request->get_param('design');
        $template_name = $request->get_param('name') ?: 'Lovable Design';
        $template_type = $request->get_param('type') ?: 'page';
        
        if (!$design_data) {
            return new WP_Error('no_design_data', __('No design data provided', 'lovable-to-wordpress'), array('status' => 400));
        }
        
        // Convert Lovable design to Elementor format
        $elementor_data = lovable_generate_elementor_json(json_decode($design_data, true));
        
        // Create template
        $template_id = wp_insert_post(array(
            'post_title' => $template_name,
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($template_id)) {
            return $template_id;
        }
        
        // Save template data
        update_post_meta($template_id, '_elementor_data', wp_json_encode($elementor_data['content']));
        update_post_meta($template_id, '_elementor_template_type', $template_type);
        update_post_meta($template_id, '_elementor_edit_mode', 'builder');
        update_post_meta($template_id, '_lovable_source', true);
        
        return rest_ensure_response(array(
            'id' => $template_id,
            'name' => $template_name,
            'type' => $template_type,
            'edit_url' => admin_url('post.php?post=' . $template_id . '&action=elementor'),
        ));
    }
    
    /**
     * Get mapper configuration
     */
    public function get_mapper_config($request) {
        $mapper_file = L2WP_PLUGIN_DIR . 'mapper.json';
        
        if (!file_exists($mapper_file)) {
            // Return default mapper config
            return rest_ensure_response(array(
                'version' => '1.0',
                'mappings' => array(),
            ));
        }
        
        $mapper_content = file_get_contents($mapper_file);
        $mapper_data = json_decode($mapper_content, true);
        
        return rest_ensure_response($mapper_data);
    }
    
    /**
     * Update mapper configuration
     */
    public function update_mapper_config($request) {
        $mapper_data = $request->get_param('mapper');
        
        if (!$mapper_data) {
            return new WP_Error('no_mapper_data', __('No mapper data provided', 'lovable-to-wordpress'), array('status' => 400));
        }
        
        $mapper_file = L2WP_PLUGIN_DIR . 'mapper.json';
        
        // Validate JSON
        $json_data = json_encode($mapper_data, JSON_PRETTY_PRINT);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON data', 'lovable-to-wordpress'), array('status' => 400));
        }
        
        // Save mapper file
        $result = file_put_contents($mapper_file, $json_data);
        
        if ($result === false) {
            return new WP_Error('save_failed', __('Failed to save mapper configuration', 'lovable-to-wordpress'), array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Mapper configuration updated successfully', 'lovable-to-wordpress'),
        ));
    }
}
