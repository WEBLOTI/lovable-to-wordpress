<?php
/**
 * Lovable to WordPress Uninstall
 *
 * This file handles the cleanup when the plugin is uninstalled.
 * Removes all options, transients, and data created by the plugin.
 *
 * @package Lovable_To_WordPress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up all plugin data on uninstall
 *
 * This function removes:
 * - Plugin options and settings
 * - Transients and cached data
 * - Post meta related to Lovable exports
 * - Custom post types created by the plugin
 * - Temporary files
 */
function l2wp_uninstall() {
    global $wpdb;

    /**
     * Filter to control whether data should be deleted on uninstall
     *
     * By default, plugin data is deleted when uninstalled.
     * Set to false to keep data even after uninstall.
     *
     * @since 1.0.0
     * @param bool $delete_data Whether to delete plugin data. Default true.
     */
    $delete_data = apply_filters('l2wp_uninstall_delete_data', true);

    if (!$delete_data) {
        return;
    }

    /**
     * Action hook before uninstall cleanup begins
     *
     * @since 1.0.0
     */
    do_action('l2wp_before_uninstall');

    // 1. Delete plugin options
    delete_option('lovable_to_wordpress_version');
    delete_option('lovable_to_wordpress_settings');
    delete_option('l2wp_detection_cache');
    delete_option('l2wp_plugin_recommendations');

    // 2. Delete transients
    delete_transient('l2wp_componentdetection_cache');
    delete_transient('l2wp_plugin_mapping_cache');

    // 3. Delete post meta for lovable exports
    $lovable_posts = $wpdb->get_results(
        "SELECT post_id FROM {$wpdb->postmeta}
        WHERE meta_key = '_l2wp_lovable_export'
        OR meta_key = '_l2wp_export_source'
        OR meta_key = '_l2wp_component_map'"
    );

    if (!empty($lovable_posts)) {
        foreach ($lovable_posts as $post) {
            delete_post_meta($post->post_id, '_l2wp_lovable_export');
            delete_post_meta($post->post_id, '_l2wp_export_source');
            delete_post_meta($post->post_id, '_l2wp_component_map');
            delete_post_meta($post->post_id, '_l2wp_import_timestamp');
            delete_post_meta($post->post_id, '_l2wp_plugin_recommendations');
        }
    }

    // 4. Delete user meta for plugin settings
    $users = $wpdb->get_results(
        "SELECT user_id FROM {$wpdb->usermeta}
        WHERE meta_key LIKE '%l2wp_%'"
    );

    if (!empty($users)) {
        foreach ($users as $user) {
            delete_user_meta($user->user_id, 'l2wp_last_export');
            delete_user_meta($user->user_id, 'l2wp_preferences');
        }
    }

    // 5. Clean up temporary files
    l2wp_cleanup_temp_files();

    /**
     * Action hook after uninstall cleanup is complete
     *
     * @since 1.0.0
     */
    do_action('l2wp_after_uninstall');
}

/**
 * Clean up temporary files created by the plugin
 *
 * Removes any temporary files stored in wp-content/uploads/l2wp-temp/
 *
 * @since 1.0.0
 * @return void
 */
function l2wp_cleanup_temp_files() {
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/l2wp-temp';

    if (is_dir($temp_dir)) {
        // Recursively delete directory contents
        l2wp_delete_directory($temp_dir);
    }
}

/**
 * Recursively delete a directory and all its contents
 *
 * @since 1.0.0
 * @param string $dir Directory path to delete.
 * @return bool True if successful, false otherwise.
 */
function l2wp_delete_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = scandir($dir);

    if (!is_array($files)) {
        return false;
    }

    foreach ($files as $file) {
        if ('.' !== $file && '..' !== $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                l2wp_delete_directory($path);
            } else {
                @unlink($path);
            }
        }
    }

    return @rmdir($dir);
}

// Run uninstall cleanup
l2wp_uninstall();
