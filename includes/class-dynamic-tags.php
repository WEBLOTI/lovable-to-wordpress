<?php
/**
 * Dynamic Tags Class
 * 
 * Registers custom dynamic tags for Elementor
 * Supports ACF, JetEngine, MetaBox, and custom post fields
 * 
 * @package Lovable_Exporter
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lovable_Dynamic_Tags {
    
    /**
     * Register dynamic tags
     * 
     * @param object $dynamic_tags_manager Elementor dynamic tags manager
     */
    public function register($dynamic_tags_manager) {
        // Check if Elementor is loaded
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Register tag group
        $dynamic_tags_manager->register_group('lovable', array(
            'title' => __('Lovable Fields', 'lovable-exporter'),
        ));
        
        // Register individual tags based on active plugins
        $this->register_post_tags($dynamic_tags_manager);
        $this->register_custom_field_tags($dynamic_tags_manager);
        $this->register_taxonomy_tags($dynamic_tags_manager);
    }
    
    /**
     * Register post field tags
     * 
     * @param object $dynamic_tags_manager Elementor dynamic tags manager
     */
    private function register_post_tags($dynamic_tags_manager) {
        // These would be custom tag classes
        // For now, we use Elementor's built-in post tags
    }
    
    /**
     * Register custom field tags
     * 
     * @param object $dynamic_tags_manager Elementor dynamic tags manager
     */
    private function register_custom_field_tags($dynamic_tags_manager) {
        $plugin = lovable_get_active_cpt_plugin();
        
        // Register based on active plugin
        if ($plugin === 'acf' && class_exists('ACF')) {
            // ACF tags are already registered by Elementor Pro or ACF
        } elseif ($plugin === 'jetengine') {
            // JetEngine has its own dynamic tags
        } elseif ($plugin === 'metabox') {
            // MetaBox integration
        }
    }
    
    /**
     * Register taxonomy tags
     * 
     * @param object $dynamic_tags_manager Elementor dynamic tags manager
     */
    private function register_taxonomy_tags($dynamic_tags_manager) {
        // Taxonomy tags are usually built into Elementor
    }
}

/**
 * Base Dynamic Tag Class for Lovable
 */
if (class_exists('Elementor\Core\DynamicTags\Tag')) {
    
    class Lovable_Dynamic_Tag_Base extends \Elementor\Core\DynamicTags\Tag {
        
        /**
         * Get tag name
         */
        public function get_name() {
            return 'lovable-field';
        }
        
        /**
         * Get tag title
         */
        public function get_title() {
            return __('Lovable Field', 'lovable-exporter');
        }
        
        /**
         * Get tag group
         */
        public function get_group() {
            return 'lovable';
        }
        
        /**
         * Get tag categories
         */
        public function get_categories() {
            return array(
                \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
                \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
                \Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY,
            );
        }
        
        /**
         * Register controls
         */
        protected function register_controls() {
            $this->add_control(
                'field_name',
                array(
                    'label' => __('Field Name', 'lovable-exporter'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                )
            );
        }
        
        /**
         * Render tag
         */
        public function render() {
            $field_name = $this->get_settings('field_name');
            
            if (empty($field_name)) {
                return;
            }
            
            $plugin = lovable_get_active_cpt_plugin();
            $post_id = get_the_ID();
            
            $value = '';
            
            switch ($plugin) {
                case 'acf':
                    if (function_exists('get_field')) {
                        $value = get_field($field_name, $post_id);
                    }
                    break;
                    
                case 'jetengine':
                    $value = get_post_meta($post_id, $field_name, true);
                    break;
                    
                case 'metabox':
                    if (function_exists('rwmb_meta')) {
                        $value = rwmb_meta($field_name, '', $post_id);
                    }
                    break;
                    
                default:
                    $value = get_post_meta($post_id, $field_name, true);
                    break;
            }
            
            echo wp_kses_post($value);
        }
    }
    
    /**
     * ACF Field Dynamic Tag
     */
    class Lovable_ACF_Field_Tag extends Lovable_Dynamic_Tag_Base {
        
        public function get_name() {
            return 'lovable-acf-field';
        }
        
        public function get_title() {
            return __('ACF Field', 'lovable-exporter');
        }
        
        protected function register_controls() {
            if (!function_exists('acf_get_field_groups')) {
                parent::register_controls();
                return;
            }
            
            $fields = array();
            $groups = acf_get_field_groups();
            
            foreach ($groups as $group) {
                $group_fields = acf_get_fields($group['key']);
                if ($group_fields) {
                    foreach ($group_fields as $field) {
                        $fields[$field['name']] = $field['label'];
                    }
                }
            }
            
            $this->add_control(
                'field_name',
                array(
                    'label' => __('Field', 'lovable-exporter'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => $fields,
                )
            );
        }
        
        public function render() {
            $field_name = $this->get_settings('field_name');
            
            if (empty($field_name) || !function_exists('get_field')) {
                return;
            }
            
            $value = get_field($field_name, get_the_ID());
            
            if (is_array($value)) {
                echo wp_kses_post(implode(', ', $value));
            } else {
                echo wp_kses_post($value);
            }
        }
    }
    
    /**
     * Taxonomy Terms Dynamic Tag
     */
    class Lovable_Taxonomy_Tag extends Lovable_Dynamic_Tag_Base {
        
        public function get_name() {
            return 'lovable-taxonomy';
        }
        
        public function get_title() {
            return __('Taxonomy Terms', 'lovable-exporter');
        }
        
        protected function register_controls() {
            $taxonomies = get_taxonomies(array('public' => true), 'objects');
            $taxonomy_options = array();
            
            foreach ($taxonomies as $taxonomy) {
                $taxonomy_options[$taxonomy->name] = $taxonomy->label;
            }
            
            $this->add_control(
                'taxonomy',
                array(
                    'label' => __('Taxonomy', 'lovable-exporter'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => $taxonomy_options,
                )
            );
            
            $this->add_control(
                'separator',
                array(
                    'label' => __('Separator', 'lovable-exporter'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => ', ',
                )
            );
        }
        
        public function render() {
            $taxonomy = $this->get_settings('taxonomy');
            $separator = $this->get_settings('separator');
            
            if (empty($taxonomy)) {
                return;
            }
            
            $terms = get_the_terms(get_the_ID(), $taxonomy);
            
            if ($terms && !is_wp_error($terms)) {
                $term_names = array_map(function($term) {
                    return $term->name;
                }, $terms);
                
                echo esc_html(implode($separator, $term_names));
            }
        }
    }
}
