<?php
/**
 * Uninstall handler for Advanced Menu Items Visibility Control
 *
 * This file runs when the plugin is deleted via the WordPress admin.
 * It cleans up all plugin data from the database.
 *
 * @package Advanced_Menu_Items_Visibility_Control
 */

// Exit if accessed directly or not during uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete all post meta created by this plugin
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} 
     WHERE meta_key IN (
         '_rcp_menu_item_login_status',
         '_rcp_menu_item_roles',
         '_rcp_menu_item_levels',
         '_rcp_menu_item_access_level'
     )"
);

// Delete transients
delete_transient('amiv_github_release');
