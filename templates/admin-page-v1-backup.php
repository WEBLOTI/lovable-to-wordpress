<?php
/**
 * Admin Page Template
 * 
 * Main admin interface for Lovable Exporter
 * 
 * @package Lovable_Exporter
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap lovable-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Show success message if export was successful
    if (isset($_GET['exported'])) {
        $template_id = intval($_GET['exported']);
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                printf(
                    __('Design exported successfully! <a href="%s">Edit in Elementor</a>', 'lovable-exporter'),
                    admin_url('post.php?post=' . $template_id . '&action=elementor')
                );
                ?>
            </p>
        </div>
        <?php
    }
    ?>
    
    <div class="lovable-admin-container">
        
        <!-- Tabs -->
        <nav class="nav-tab-wrapper">
            <a href="#export" class="nav-tab nav-tab-active"><?php _e('Import Design', 'lovable-exporter'); ?></a>
            <a href="#mapper" class="nav-tab"><?php _e('Field Mapper', 'lovable-exporter'); ?></a>
            <a href="#templates" class="nav-tab"><?php _e('Templates', 'lovable-exporter'); ?></a>
            <a href="#settings" class="nav-tab"><?php _e('Settings', 'lovable-exporter'); ?></a>
            <a href="#help" class="nav-tab"><?php _e('Help', 'lovable-exporter'); ?></a>
        </nav>
        
        <!-- Export Tab -->
        <div id="export" class="lovable-tab-content active">
            <h2><?php _e('Import Lovable Design', 'lovable-exporter'); ?></h2>
            
            <div class="lovable-card">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('lovable_export_nonce'); ?>
                    <input type="hidden" name="action" value="lovable_export">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="design_data"><?php _e('Design JSON', 'lovable-exporter'); ?></label>
                            </th>
                            <td>
                                <textarea 
                                    name="design_data" 
                                    id="design_data" 
                                    rows="10" 
                                    class="large-text code"
                                    placeholder='{"title":"My Design","sections":[...]}'
                                ></textarea>
                                <p class="description">
                                    <?php _e('Paste your Lovable design JSON here. You can export this from your Lovable project.', 'lovable-exporter'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="debug_mode" value="1">
                                    <strong style="color: #d63638;">🔍 <?php _e('Debug Mode', 'lovable-exporter'); ?></strong> - 
                                    <?php _e('Show what PHP receives (for troubleshooting)', 'lovable-exporter'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Import to Elementor', 'lovable-exporter')); ?>
                </form>
            </div>
            
            <div class="lovable-card">
                <h3><?php _e('Quick Start Guide', 'lovable-exporter'); ?></h3>
                <ol>
                    <li><?php _e('Export your design from Lovable as JSON', 'lovable-exporter'); ?></li>
                    <li>
                        <?php _e('Validate your JSON using our tool:', 'lovable-exporter'); ?>
                        <a href="<?php echo plugins_url('test-json.php', LOVABLE_EXPORTER_PLUGIN_FILE); ?>" target="_blank" class="button button-secondary" style="margin-left: 10px;">
                            🔍 <?php _e('JSON Validator', 'lovable-exporter'); ?>
                        </a>
                    </li>
                    <li><?php _e('Paste the validated JSON in the field above', 'lovable-exporter'); ?></li>
                    <li><?php _e('Click "Import to Elementor"', 'lovable-exporter'); ?></li>
                    <li><?php _e('Edit the template in Elementor', 'lovable-exporter'); ?></li>
                </ol>
            </div>
        </div>
        
        <!-- Mapper Tab -->
        <div id="mapper" class="lovable-tab-content">
            <h2><?php _e('Field Mapper Configuration', 'lovable-exporter'); ?></h2>
            
            <div class="lovable-card">
                <h3><?php _e('Active Custom Fields Plugin', 'lovable-exporter'); ?></h3>
                <?php
                $active_plugin = lovable_get_active_cpt_plugin();
                $plugin_names = array(
                    'acf' => 'Advanced Custom Fields',
                    'jetengine' => 'JetEngine',
                    'metabox' => 'Meta Box',
                    'cptui' => 'Custom Post Type UI',
                );
                ?>
                <p>
                    <?php
                    if ($active_plugin) {
                        printf(
                            __('Detected: <strong>%s</strong>', 'lovable-exporter'),
                            $plugin_names[$active_plugin] ?? $active_plugin
                        );
                    } else {
                        _e('No custom fields plugin detected. Install ACF, JetEngine, or Meta Box for dynamic content.', 'lovable-exporter');
                    }
                    ?>
                </p>
            </div>
            
            <div class="lovable-card">
                <h3><?php _e('Placeholder Reference', 'lovable-exporter'); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Placeholder', 'lovable-exporter'); ?></th>
                            <th><?php _e('Description', 'lovable-exporter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>{{post.title}}</code></td>
                            <td><?php _e('Post title', 'lovable-exporter'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{{post.content}}</code></td>
                            <td><?php _e('Post content', 'lovable-exporter'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{{post.excerpt}}</code></td>
                            <td><?php _e('Post excerpt', 'lovable-exporter'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{{post.thumbnail}}</code></td>
                            <td><?php _e('Featured image URL', 'lovable-exporter'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{{acf.field_name}}</code></td>
                            <td><?php _e('ACF field value', 'lovable-exporter'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{{jet.field_name}}</code></td>
                            <td><?php _e('JetEngine field value', 'lovable-exporter'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{{mb.field_name}}</code></td>
                            <td><?php _e('Meta Box field value', 'lovable-exporter'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{{taxonomy.category}}</code></td>
                            <td><?php _e('Taxonomy terms (comma-separated)', 'lovable-exporter'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Templates Tab -->
        <div id="templates" class="lovable-tab-content">
            <h2><?php _e('Lovable Templates', 'lovable-exporter'); ?></h2>
            
            <?php
            $templates = get_posts(array(
                'post_type' => 'elementor_library',
                'meta_key' => '_lovable_source',
                'meta_value' => true,
                'posts_per_page' => -1,
            ));
            ?>
            
            <?php if (!empty($templates)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Template Name', 'lovable-exporter'); ?></th>
                            <th><?php _e('Type', 'lovable-exporter'); ?></th>
                            <th><?php _e('Date', 'lovable-exporter'); ?></th>
                            <th><?php _e('Actions', 'lovable-exporter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template) : ?>
                            <tr id="lovable-template-<?php echo $template->ID; ?>">
                                <td><?php echo esc_html($template->post_title); ?></td>
                                <td><?php echo esc_html(get_post_meta($template->ID, '_elementor_template_type', true)); ?></td>
                                <td><?php echo esc_html(get_the_date('', $template->ID)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $template->ID . '&action=elementor'); ?>" class="button">
                                        <?php _e('Edit with Elementor', 'lovable-exporter'); ?>
                                    </a>
                                    <button type="button" class="button lovable-remove-template" data-template-id="<?php echo $template->ID; ?>" style="margin-left: 5px;">
                                        🗑️ <?php _e('Remove from List', 'lovable-exporter'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No Lovable templates found. Export a design to get started.', 'lovable-exporter'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Settings Tab -->
        <div id="settings" class="lovable-tab-content">
            <h2><?php _e('Settings', 'lovable-exporter'); ?></h2>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('lovable_exporter_settings');
                $settings = get_option('lovable_exporter_settings', array());
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Animations', 'lovable-exporter'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lovable_exporter_settings[animation_enabled]" value="1" <?php checked($settings['animation_enabled'] ?? true, 1); ?>>
                                <?php _e('Enable Lovable animations on frontend', 'lovable-exporter'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Lazy Loading', 'lovable-exporter'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lovable_exporter_settings[lazyload_enabled]" value="1" <?php checked($settings['lazyload_enabled'] ?? true, 1); ?>>
                                <?php _e('Enable lazy loading for images', 'lovable-exporter'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Critical CSS', 'lovable-exporter'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lovable_exporter_settings[critical_css_enabled]" value="1" <?php checked($settings['critical_css_enabled'] ?? true, 1); ?>>
                                <?php _e('Inline critical CSS for better performance', 'lovable-exporter'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <!-- Help Tab -->
        <div id="help" class="lovable-tab-content">
            <h2><?php _e('Help & Documentation', 'lovable-exporter'); ?></h2>
            
            <div class="lovable-card">
                <h3><?php _e('REST API Endpoints', 'lovable-exporter'); ?></h3>
                <p><?php _e('Use these endpoints to integrate Lovable with WordPress:', 'lovable-exporter'); ?></p>
                <ul>
                    <li><code>GET /wp-json/lovable/v1/post-types</code> - Get all custom post types</li>
                    <li><code>GET /wp-json/lovable/v1/post-types/{post_type}/fields</code> - Get custom fields for a post type</li>
                    <li><code>GET /wp-json/lovable/v1/post-types/{post_type}/taxonomies</code> - Get taxonomies for a post type</li>
                    <li><code>GET /wp-json/lovable/v1/posts</code> - Get posts with custom fields</li>
                    <li><code>POST /wp-json/lovable/v1/export</code> - Export design to Elementor</li>
                </ul>
            </div>
            
            <div class="lovable-card">
                <h3><?php _e('Animation Attributes', 'lovable-exporter'); ?></h3>
                <p><?php _e('Add these attributes to any HTML element to trigger animations:', 'lovable-exporter'); ?></p>
                <ul>
                    <li><code>data-lovable-anim="fadeIn"</code> - Animation type</li>
                    <li><code>data-lovable-delay="300"</code> - Delay in milliseconds</li>
                    <li><code>data-lovable-duration="normal"</code> - Duration (fast, normal, slow)</li>
                    <li><code>data-lovable-once="true"</code> - Animate only once (default: true)</li>
                </ul>
            </div>
            
            <div class="lovable-card">
                <h3><?php _e('System Information', 'lovable-exporter'); ?></h3>
                <ul>
                    <li><?php printf(__('Plugin Version: %s', 'lovable-exporter'), LOVABLE_EXPORTER_VERSION); ?></li>
                    <li><?php printf(__('WordPress Version: %s', 'lovable-exporter'), get_bloginfo('version')); ?></li>
                    <li><?php printf(__('Elementor: %s', 'lovable-exporter'), did_action('elementor/loaded') ? __('Active', 'lovable-exporter') : __('Not Active', 'lovable-exporter')); ?></li>
                    <li><?php printf(__('PHP Version: %s', 'lovable-exporter'), PHP_VERSION); ?></li>
                </ul>
            </div>
        </div>
        
    </div>
</div>

<style>
.lovable-admin-wrap {
    margin: 20px 20px 0 0;
}

.lovable-admin-container {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
}

.lovable-tab-content {
    display: none;
    padding: 20px 0;
}

.lovable-tab-content.active {
    display: block;
}

.lovable-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.lovable-card h3 {
    margin-top: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.lovable-tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Remove template from Lovable list
    $('.lovable-remove-template').on('click', function() {
        var button = $(this);
        var templateId = button.data('template-id');
        var row = $('#lovable-template-' + templateId);
        
        if (!confirm('<?php _e('Remove this template from Lovable Templates list? The template will still exist in Elementor.', 'lovable-exporter'); ?>')) {
            return;
        }
        
        // Disable button
        button.prop('disabled', true).text('<?php _e('Removing...', 'lovable-exporter'); ?>');
        
        // AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lovable_remove_template',
                template_id: templateId,
                nonce: '<?php echo wp_create_nonce('lovable_remove_template'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Fade out and remove row
                    row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('#templates tbody tr').length === 0) {
                            $('#templates .wp-list-table').replaceWith(
                                '<p><?php _e('No Lovable templates found. Export a design to get started.', 'lovable-exporter'); ?></p>'
                            );
                        }
                    });
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertAfter('.lovable-admin-wrap h1')
                        .delay(3000)
                        .fadeOut();
                } else {
                    alert('<?php _e('Error:', 'lovable-exporter'); ?> ' + response.data.message);
                    button.prop('disabled', false).html('🗑️ <?php _e('Remove from List', 'lovable-exporter'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred. Please try again.', 'lovable-exporter'); ?>');
                button.prop('disabled', false).html('🗑️ <?php _e('Remove from List', 'lovable-exporter'); ?>');
            }
        });
    });
});
</script>
