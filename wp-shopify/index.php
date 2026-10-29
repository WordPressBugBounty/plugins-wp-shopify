<?php
/**
 * Plugin Name: External Store for Shopify
 * Plugin URI:  https://profiles.wordpress.org/fahadmahmood/#content-plugins
 * Description: Display Shopify products on your WordPress website with shortcodes. Secure, cached and fast.
 * Version:     1.7.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:      Fahad Mahmood
 * Author URI:  https://profiles.wordpress.org/fahadmahmood/#content-about
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-shopify
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'WPSY_VERSION' ) ) {
	define( 'WPSY_VERSION', '1.7.0' );
}
if ( ! defined( 'WPSY_PLUGIN_FILE' ) ) {
	define( 'WPSY_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WPSY_PLUGIN_DIR' ) ) {
	define( 'WPSY_PLUGIN_DIR', dirname( __FILE__ ) );
}
if ( ! defined( 'WPSY_PLUGIN_URL' ) ) {
	define( 'WPSY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WPSY_API_VERSION' ) ) {
	// Shopify Storefront API version. Can be changed through the "wpsy_api_version" filter.
	define( 'WPSY_API_VERSION', '2026-01' );
}

global $wpsy_data, $wpsy_pro, $wpsy_allowed_templates, $wpsy_premium_copy;

$wpsy_allowed_templates = array( 'bello-sole' );
$wpsy_premium_copy      = 'https://shop.androidbubbles.com/product/wp-shopify';
$wpsy_data              = array(
	'Name'    => 'External Store for Shopify',
	'Version' => WPSY_VERSION,
);

// Core (free) functionality. Always loaded.
require_once WPSY_PLUGIN_DIR . '/inc/functions.php';
require_once WPSY_PLUGIN_DIR . '/inc/graphql.php';

if ( is_admin() ) {
	require_once WPSY_PLUGIN_DIR . '/inc/admin.php';
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wp_shopify_admin_links' );
}

/*
 * Premium add-on: plug and play.
 * If the /pro directory exists its features are switched on, otherwise the free version works on its own.
 */
$wpsy_pro_file = WPSY_PLUGIN_DIR . '/pro/wpsy-pro.php';
$wpsy_pro      = file_exists( $wpsy_pro_file );

if ( $wpsy_pro ) {
	if ( ! defined( 'WPSY_PRO' ) ) {
		define( 'WPSY_PRO', true );
	}
	include_once $wpsy_pro_file;
}

register_activation_hook( __FILE__, 'wpsy_activate' );
