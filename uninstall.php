<?php

/**
 * Fired when the plugin is uninstalled.
 *
 *
 * @link       http://www.example.com
 * @since      0.0.1
 *
 * @package    Plugin_Name
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
global $wpdb;
$table_name = $wpdb->prefix . 'wrong_url';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");