<?php
/**
 * Uninstall — Kiro Cursor
 *
 * Runs when the plugin is deleted via the WordPress Plugins admin screen.
 * Removes all wp_options rows registered by this plugin.
 *
 * WordPress loads this file directly (not via require_once), so we must
 * verify WP_UNINSTALL_PLUGIN is defined before doing anything.
 *
 * @package KiroCursor
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove all plugin options — complete trace cleanup on uninstall.
delete_option( 'kiro_cursor_color' );
delete_option( 'kiro_cursor_size' );
delete_option( 'kiro_cursor_disable_mobile' );
delete_option( 'kiro_cursor_style' );
delete_option( 'kiro_cursor_animation' );
