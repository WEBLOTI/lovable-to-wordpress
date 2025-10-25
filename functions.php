<?php
/**
 * Helper Functions for Lovable to WordPress
 *
 * This file contains utility functions for placeholder replacement,
 * dynamic content mapping, and asset optimization.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Replace placeholders in content with actual values
 * 
 * @param string $content Content with placeholders
 * @param int $post_id Post ID for context
 * @return string Processed content
 */
function l2wp_replace_placeholders($content, $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $post = get_post($post_id);
    if (!$post) {
        return $content;
    }
    
    // Post placeholders
    $placeholders = array(
        '{{post.title}}' => get_the_title($post_id),
        '{{post.content}}' => apply_filters('the_content', $post->post_content),
        '{{post.excerpt}}' => get_the_excerpt($post_id),
        '{{post.date}}' => get_the_date('', $post_id),
        '{{post.author}}' => get_the_author_meta('display_name', $post->post_author),
        '{{post.permalink}}' => get_permalink($post_id),
        '{{post.thumbnail}}' => get_the_post_thumbnail_url($post_id, 'full'),
    );
    
    // Replace post placeholders
    $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
    
    // ACF placeholders (if ACF is active)
    if (function_exists('get_field')) {
        $content = l2wp_replace_acf_placeholders($content, $post_id);
    }

    // JetEngine placeholders (if JetEngine is active)
    if (function_exists('jet_engine')) {
        $content = l2wp_replace_jetengine_placeholders($content, $post_id);
    }

    // MetaBox placeholders (if MetaBox is active)
    if (function_exists('rwmb_meta')) {
        $content = l2wp_replace_metabox_placeholders($content, $post_id);
    }

    // Taxonomy placeholders
    $content = l2wp_replace_taxonomy_placeholders($content, $post_id);
    
    return $content;
}

/**
 * Replace ACF placeholders
 * 
 * @param string $content Content with ACF placeholders
 * @param int $post_id Post ID
 * @return string Processed content
 */
function l2wp_replace_acf_placeholders($content, $post_id) {
    // Match {{acf.field_name}} pattern
    preg_match_all('/\{\{acf\.([a-zA-Z0-9_-]+)\}\}/', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field_name) {
            $field_value = get_field($field_name, $post_id);
            
            // Handle different field types
            if (is_array($field_value)) {
                $field_value = implode(', ', $field_value);
            } elseif (is_object($field_value)) {
                $field_value = json_encode($field_value);
            }
            
            $content = str_replace("{{acf.{$field_name}}}", $field_value, $content);
        }
    }
    
    return $content;
}

/**
 * Replace JetEngine placeholders
 * 
 * @param string $content Content with JetEngine placeholders
 * @param int $post_id Post ID
 * @return string Processed content
 */
function l2wp_replace_jetengine_placeholders($content, $post_id) {
    // Match {{jet.field_name}} pattern
    preg_match_all('/\{\{jet\.([a-zA-Z0-9_-]+)\}\}/', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field_name) {
            $field_value = get_post_meta($post_id, $field_name, true);
            $content = str_replace("{{jet.{$field_name}}}", $field_value, $content);
        }
    }
    
    return $content;
}

/**
 * Replace MetaBox placeholders
 * 
 * @param string $content Content with MetaBox placeholders
 * @param int $post_id Post ID
 * @return string Processed content
 */
function l2wp_replace_metabox_placeholders($content, $post_id) {
    // Match {{mb.field_name}} pattern
    preg_match_all('/\{\{mb\.([a-zA-Z0-9_-]+)\}\}/', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field_name) {
            $field_value = rwmb_meta($field_name, '', $post_id);
            
            if (is_array($field_value)) {
                $field_value = implode(', ', $field_value);
            }
            
            $content = str_replace("{{mb.{$field_name}}}", $field_value, $content);
        }
    }
    
    return $content;
}

/**
 * Replace taxonomy placeholders
 * 
 * @param string $content Content with taxonomy placeholders
 * @param int $post_id Post ID
 * @return string Processed content
 */
function l2wp_replace_taxonomy_placeholders($content, $post_id) {
    // Match {{taxonomy.taxonomy_name}} pattern
    preg_match_all('/\{\{taxonomy\.([a-zA-Z0-9_-]+)\}\}/', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $taxonomy) {
            $terms = get_the_terms($post_id, $taxonomy);
            
            if ($terms && !is_wp_error($terms)) {
                $term_names = array_map(function($term) {
                    return $term->name;
                }, $terms);
                
                $content = str_replace("{{taxonomy.{$taxonomy}}}", implode(', ', $term_names), $content);
            }
        }
    }
    
    return $content;
}

/**
 * Get active custom fields plugin
 * 
 * @return string|false Plugin name or false
 */
function l2wp_get_active_cpt_plugin() {
    if (function_exists('get_field')) {
        return 'acf';
    } elseif (function_exists('jet_engine')) {
        return 'jetengine';
    } elseif (function_exists('rwmb_meta')) {
        return 'metabox';
    } elseif (function_exists('cptui_init')) {
        return 'cptui';
    }
    
    return false;
}

/**
 * Get all custom post types
 * 
 * @return array Custom post types
 */
function l2wp_get_custom_post_types() {
    $args = array(
        'public' => true,
        '_builtin' => false,
    );
    
    return get_post_types($args, 'objects');
}

/**
 * Get all custom fields for a post type
 * 
 * @param string $post_type Post type name
 * @return array Custom fields
 */
function l2wp_get_custom_fields($post_type) {
    $fields = array();
    $plugin = l2wp_get_active_cpt_plugin();
    
    switch ($plugin) {
        case 'acf':
            if (function_exists('acf_get_field_groups')) {
                $groups = acf_get_field_groups(array('post_type' => $post_type));
                foreach ($groups as $group) {
                    $group_fields = acf_get_fields($group['key']);
                    if ($group_fields) {
                        $fields = array_merge($fields, $group_fields);
                    }
                }
            }
            break;
            
        case 'jetengine':
            // JetEngine meta fields
            if (class_exists('Jet_Engine_Meta_Boxes')) {
                $meta_boxes = \Jet_Engine\Modules\Custom_Content_Types\Module::instance()->manager->get_item_for_edit($post_type);
                if ($meta_boxes) {
                    $fields = $meta_boxes['meta_fields'];
                }
            }
            break;
            
        case 'metabox':
            // MetaBox fields
            if (function_exists('rwmb_get_registry')) {
                $registry = rwmb_get_registry('meta_box');
                $meta_boxes = $registry->get_by(array('object_type' => $post_type));
                foreach ($meta_boxes as $meta_box) {
                    if (isset($meta_box->meta_box['fields'])) {
                        $fields = array_merge($fields, $meta_box->meta_box['fields']);
                    }
                }
            }
            break;
    }
    
    return $fields;
}

/**
 * Generate Elementor-compatible JSON from Lovable design
 * 
 * @param array $lovable_design Lovable design data
 * @return array Elementor JSON structure
 */
function l2wp_generate_elementor_json($lovable_design) {
    $elementor_data = array(
        'version' => '0.4',
        'title' => $lovable_design['title'] ?? 'Lovable Design',
        'type' => 'page',
        'content' => array(),
    );
    
    // Process each section from Lovable
    if (!empty($lovable_design['sections'])) {
        foreach ($lovable_design['sections'] as $section) {
            $elementor_data['content'][] = l2wp_convert_section_to_elementor($section);
        }
    }
    
    return $elementor_data;
}

/**
 * Convert Lovable section to Elementor format
 * 
 * @param array $section Lovable section data
 * @return array Elementor section structure
 */
function l2wp_convert_section_to_elementor($section) {
    return array(
        'id' => uniqid(),
        'elType' => 'section',
        'settings' => array(
            'layout' => $section['layout'] ?? 'boxed',
            '_lovable_animation' => $section['animation'] ?? '',
            'css_classes' => 'lovable-section ' . ($section['classes'] ?? ''),
        ),
        'elements' => l2wp_convert_columns_to_elementor($section['columns'] ?? array()),
    );
}

/**
 * Convert Lovable columns to Elementor format
 * 
 * @param array $columns Lovable columns data
 * @return array Elementor columns structure
 */
function l2wp_convert_columns_to_elementor($columns) {
    $elementor_columns = array();
    
    foreach ($columns as $column) {
        $elementor_columns[] = array(
            'id' => uniqid(),
            'elType' => 'column',
            'settings' => array(
                '_column_size' => $column['width'] ?? 100,
                'css_classes' => 'lovable-column ' . ($column['classes'] ?? ''),
            ),
            'elements' => l2wp_convert_widgets_to_elementor($column['widgets'] ?? array()),
        );
    }
    
    return $elementor_columns;
}

/**
 * Convert Lovable widgets to Elementor format
 * 
 * @param array $widgets Lovable widgets data
 * @return array Elementor widgets structure
 */
function l2wp_convert_widgets_to_elementor($widgets) {
    $elementor_widgets = array();
    
    foreach ($widgets as $widget) {
        $widget_type = l2wp_map_widget_type($widget['type']);
        
        $elementor_widgets[] = array(
            'id' => uniqid(),
            'elType' => 'widget',
            'widgetType' => $widget_type,
            'settings' => l2wp_map_widget_settings($widget),
        );
    }
    
    return $elementor_widgets;
}

/**
 * Map Lovable widget type to Elementor widget type
 * 
 * @param string $lovable_type Lovable widget type
 * @return string Elementor widget type
 */
function l2wp_map_widget_type($lovable_type) {
    $mapping = array(
        'heading' => 'heading',
        'text' => 'text-editor',
        'image' => 'image',
        'button' => 'button',
        'divider' => 'divider',
        'spacer' => 'spacer',
        'icon' => 'icon',
        'video' => 'video',
    );
    
    return $mapping[$lovable_type] ?? 'text-editor';
}

/**
 * Map Lovable widget settings to Elementor settings
 * 
 * @param array $widget Lovable widget data
 * @return array Elementor settings
 */
function l2wp_map_widget_settings($widget) {
    $settings = array(
        'css_classes' => 'lovable-widget ' . ($widget['classes'] ?? ''),
        '_lovable_animation' => $widget['animation'] ?? '',
    );
    
    // Add widget-specific settings
    switch ($widget['type']) {
        case 'heading':
            $settings['title'] = $widget['content'] ?? '';
            $settings['header_size'] = $widget['tag'] ?? 'h2';
            break;
            
        case 'text':
            $settings['editor'] = $widget['content'] ?? '';
            break;
            
        case 'image':
            $settings['image'] = array('url' => $widget['src'] ?? '');
            $settings['image_size'] = 'full';
            break;
            
        case 'button':
            $settings['text'] = $widget['text'] ?? '';
            $settings['link'] = array('url' => $widget['url'] ?? '#');
            break;
    }
    
    return $settings;
}

/**
 * Sanitize animation name
 * 
 * @param string $animation Animation name
 * @return string Sanitized animation name
 */
function l2wp_sanitize_animation($animation) {
    return sanitize_html_class($animation);
}

/**
 * Check if Elementor is active
 * 
 * @return bool
 */
function l2wp_is_elementor_active() {
    return did_action('elementor/loaded');
}

/**
 * Import Elementor template from JSON
 * 
 * @param string $json_file Path to JSON file
 * @param string $title Template title
 * @return int|WP_Error Template ID or error
 */
function l2wp_import_elementor_template($json_file, $title = 'Lovable Template') {
    if (!l2wp_is_elementor_active()) {
        return new WP_Error('elementor_not_active', __('Elementor is not active', 'lovable-to-wordpress'));
    }
    
    $json_content = file_get_contents($json_file);
    if (!$json_content) {
        return new WP_Error('file_not_found', __('Template file not found', 'lovable-to-wordpress'));
    }
    
    $template_data = json_decode($json_content, true);
    if (!$template_data) {
        return new WP_Error('invalid_json', __('Invalid JSON format', 'lovable-to-wordpress'));
    }
    
    // Create template post
    $template_id = wp_insert_post(array(
        'post_title' => $title,
        'post_type' => 'elementor_library',
        'post_status' => 'publish',
    ));
    
    if (is_wp_error($template_id)) {
        return $template_id;
    }
    
    // Save template data
    update_post_meta($template_id, '_elementor_data', wp_json_encode($template_data['content']));
    update_post_meta($template_id, '_elementor_template_type', $template_data['type'] ?? 'page');
    update_post_meta($template_id, '_elementor_edit_mode', 'builder');
    
    return $template_id;
}

/**
 * Check if a plugin is active
 *
 * @since 1.0.0
 * @param string $plugin_slug Plugin slug (folder/file.php)
 * @return bool True if plugin is active, false otherwise
 */
function l2wp_is_plugin_active($plugin_slug) {
    $plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';

    if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        return is_plugin_active($plugin_file);
    }

    return false;
}

/**
 * Install and activate a plugin
 *
 * @since 1.0.0
 * @param string $plugin_slug Plugin slug to install
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function l2wp_install_plugin($plugin_slug) {
    // Check if plugin is already installed and active
    if (l2wp_is_plugin_active($plugin_slug)) {
        return true;
    }

    // Check if plugin exists but is inactive
    $plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
    if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        $activate = activate_plugin($plugin_file);
        if (is_wp_error($activate)) {
            return $activate;
        }
        return true;
    }

    // Plugin needs to be downloaded from WordPress.org
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';

    // Get plugin info from WordPress.org
    $api = plugins_api('plugin_information', array(
        'slug' => $plugin_slug,
        'fields' => array('sections' => false)
    ));

    if (is_wp_error($api)) {
        return new WP_Error(
            'plugin_not_found',
            sprintf(__('Plugin %s not found in WordPress.org repository', 'lovable-to-wordpress'), $plugin_slug)
        );
    }

    // Install plugin
    $skin = new WP_Ajax_Upgrader_Skin();
    $upgrader = new Plugin_Upgrader($skin);
    $result = $upgrader->install($api->download_link);

    if (is_wp_error($result)) {
        return $result;
    }

    if ($result === false) {
        return new WP_Error(
            'plugin_install_failed',
            sprintf(__('Failed to install plugin %s', 'lovable-to-wordpress'), $plugin_slug)
        );
    }

    // Activate the newly installed plugin
    $activate = activate_plugin($plugin_file);
    if (is_wp_error($activate)) {
        return $activate;
    }

    return true;
}

/**
 * Import assets from Lovable project
 *
 * @since 1.0.0
 * @param array $assets List of asset files to import
 * @return int Number of assets imported
 */
function l2wp_import_assets($assets) {
    $imported = 0;

    if (empty($assets) || !is_array($assets)) {
        return $imported;
    }

    // Require media functions
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    foreach ($assets as $asset) {
        $asset_file = sanitize_file_name($asset['path']);
        $asset_url = esc_url_raw($asset['url']);

        // Skip if invalid
        if (empty($asset_file) || empty($asset_url)) {
            continue;
        }

        // Try to download and attach to media library
        // This is a simplified version - full implementation would handle various formats
        if (l2wp_import_asset_file($asset_file, $asset_url)) {
            $imported++;
        }
    }

    return $imported;
}

/**
 * Import a single asset file
 *
 * @since 1.0.0
 * @param string $filename The filename
 * @param string $url The asset URL
 * @return int|bool Attachment ID on success, false on failure
 */
function l2wp_import_asset_file($filename, $url) {
    // Download file to temp location
    $temp_file = download_url($url);
    
    if (is_wp_error($temp_file)) {
        return false;
    }

    // Prepare file data
    $file_array = array(
        'name' => $filename,
        'tmp_name' => $temp_file
    );

    // Import to media library
    $attachment_id = media_handle_sideload($file_array, 0);

    // Clean up temp file
    if (file_exists($temp_file)) {
        @unlink($temp_file);
    }

    if (is_wp_error($attachment_id)) {
        return false;
    }

    // Mark as imported from Lovable
    update_post_meta($attachment_id, '_l2wp_imported_asset', true);
    update_post_meta($attachment_id, '_l2wp_import_timestamp', current_time('mysql'));

    return $attachment_id;
}
