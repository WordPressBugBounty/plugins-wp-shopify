<?php
/**
 * Admin area: settings page, assets and tools.
 *
 * @package External_Store_for_Shopify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'admin_menu', 'wpsy_add_admin_menu' );
add_action( 'admin_init', 'wpsy_settings_init' );
add_action( 'admin_enqueue_scripts', 'wpsy_admin_enqueue' );
add_action( 'wp_ajax_wpsy_test_connection', 'wpsy_ajax_test_connection' );
add_action( 'wp_ajax_wpsy_clear_cache', 'wpsy_ajax_clear_cache' );
add_action( 'admin_post_wpsy_disconnect', 'wpsy_handle_disconnect' );

function wpsy_add_admin_menu() {
	global $wpsy_data;
	add_options_page( $wpsy_data['Name'], $wpsy_data['Name'], 'manage_options', 'wp_shopify', 'wp_shopify_settings' );
}

function wp_shopify_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-shopify' ), '', array( 'response' => 403 ) );
	}
	include WPSY_PLUGIN_DIR . '/inc/wps_settings.php';
}

function wpsy_settings_init() {
	register_setting(
		'wpsy_settings_page',
		'wpsy_db_data',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'wpsy_sanitize_settings',
			'default'           => array(),
		)
	);
}

/**
 * Sanitizes and validates the settings form.
 * Secret fields left blank keep their saved value (they are never printed back into the page).
 */
function wpsy_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$old   = wpsy_get_settings();
	$new   = wpsy_default_settings();

	$get = function ( $key ) use ( $input ) {
		return ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ) ? trim( sanitize_text_field( (string) $input[ $key ] ) ) : '';
	};

	$new['wpsy_api_key'] = $get( 'wpsy_api_key' );

	foreach ( array( 'wpsy_password', 'wpsy_storefront_shared', 'wpsy_storefront_token' ) as $secret ) {
		$value         = $get( $secret );
		$new[ $secret ] = ( '' === $value ) ? $old[ $secret ] : $value;
	}

	$raw_domain = $get( 'wpsy_url' );
	$domain     = wpsy_clean_domain( $raw_domain );
	if ( '' !== $raw_domain && '' === $domain ) {
		add_settings_error( 'wpsy_settings_page', 'wpsy_bad_domain', __( 'The store domain is not valid. Use something like your-store.myshopify.com.', 'wp-shopify' ), 'error' );
		$domain = wpsy_clean_domain( $old['wpsy_url'] );
	}
	$new['wpsy_url'] = $domain;

	$new['wpsy_cache_minutes']      = isset( $input['wpsy_cache_minutes'] ) ? max( 0, min( 1440, absint( $input['wpsy_cache_minutes'] ) ) ) : (int) $old['wpsy_cache_minutes'];
	$new['wpsy_json_ld']            = isset( $input['wpsy_json_ld'] ) ? 'yes' : 'no';
	$new['wpsy_site_locale_prices'] = isset( $input['wpsy_site_locale_prices'] ) ? 'yes' : 'no';

	wpsy_flush_cache();
	wpsy_clear_last_error();

	return $new;
}

function shoppd_settings_section_callback( $wpsy_db_data = array() ) {
	if ( ! wpsy_is_configured() ) {
		echo '<div class="alert alert-warning" role="alert">' . esc_html__( 'Please fill the required fields.', 'wp-shopify' ) . '</div>';
	}
}

function wpsy_admin_enqueue( $hook ) {
	if ( 'settings_page_wp_shopify' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'wp-shopify-styles-admin', WPSY_PLUGIN_URL . 'css/admin-styles.css', array(), WPSY_VERSION );
	wp_enqueue_style( 'fontawesome', WPSY_PLUGIN_URL . 'css/fontawesome.min.css', array(), WPSY_VERSION );
	wp_enqueue_style( 'bootstrap', WPSY_PLUGIN_URL . 'css/bootstrap.min.css', array(), WPSY_VERSION );

	wp_enqueue_script( 'fontawesome', WPSY_PLUGIN_URL . 'js/fontawesome.min.js', array( 'jquery' ), WPSY_VERSION, true );
	wp_enqueue_script( 'bootstrap', WPSY_PLUGIN_URL . 'js/bootstrap.min.js', array( 'jquery' ), WPSY_VERSION, true );
	wp_enqueue_script( 'wp-shopify-scripts', WPSY_PLUGIN_URL . 'js/scripts.js', array( 'jquery' ), WPSY_VERSION, true );

	wp_localize_script(
		'wp-shopify-scripts',
		'wpsy_object',
		array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wpsy-nonce' ),
			'i18n'  => array(
				'testing'  => __( 'Testing connection...', 'wp-shopify' ),
				'clearing' => __( 'Clearing cache...', 'wp-shopify' ),
				'failed'   => __( 'Request failed. Please try again.', 'wp-shopify' ),
				'copied'   => __( 'Copied!', 'wp-shopify' ),
				'confirm'  => __( 'This removes your saved Shopify credentials from this website. Continue?', 'wp-shopify' ),
			),
		)
	);
}

/* -------------------------------------------------------------------------
 * Tools tab actions.
 * ---------------------------------------------------------------------- */
function wpsy_ajax_test_connection() {
	check_ajax_referer( 'wpsy-nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'wp-shopify' ) ), 403 );
	}

	$data = wpsy_api_fetch( 'shop', array( 'nocache' => true ) );

	if ( is_object( $data ) && isset( $data->shop->name ) ) {
		/* translators: %s: shop name */
		wp_send_json_success( array( 'message' => sprintf( __( 'Connected to %s.', 'wp-shopify' ), sanitize_text_field( $data->shop->name ) ) ) );
	}

	$error = wpsy_get_last_error();
	wp_send_json_error( array( 'message' => $error ? $error : __( 'Could not connect to Shopify.', 'wp-shopify' ) ) );
}

function wpsy_ajax_clear_cache() {
	check_ajax_referer( 'wpsy-nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'wp-shopify' ) ), 403 );
	}

	wpsy_flush_cache();
	wp_send_json_success( array( 'message' => __( 'Cache cleared. Fresh data will be fetched from Shopify.', 'wp-shopify' ) ) );
}

function wpsy_handle_disconnect() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'wp-shopify' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'wpsy_disconnect' );

	delete_option( 'wpsy_db_data' );
	wpsy_flush_cache();
	wpsy_clear_last_error();

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'               => 'wp_shopify',
				'wpsy-disconnected' => 1,
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}

/* -------------------------------------------------------------------------
 * Plugins screen links.
 * ---------------------------------------------------------------------- */
function wp_shopify_admin_links( $links ) {
	global $wpsy_premium_copy, $wpsy_pro;

	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=wp_shopify' ) ) . '">' . esc_html__( 'Settings', 'wp-shopify' ) . '</a>';

	if ( $wpsy_pro ) {
		array_unshift( $links, $settings_link );
	} else {
		$premium_link = '<a href="' . esc_url( $wpsy_premium_copy ) . '" title="' . esc_attr__( 'Go Premium', 'wp-shopify' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Go Premium', 'wp-shopify' ) . '</a>';
		array_unshift( $links, $settings_link, $premium_link );
	}

	return $links;
}
