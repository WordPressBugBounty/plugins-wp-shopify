<?php
/**
 * Core helpers, rendering and shortcodes.
 *
 * @package External_Store_for_Shopify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* -------------------------------------------------------------------------
 * Debug helpers (kept for backward compatibility, now locked down).
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'pree' ) ) {
	function pree( $data ) {
		echo '<pre>' . esc_html( print_r( $data, true ) ) . '</pre>'; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}
}

if ( ! function_exists( 'pre' ) ) {
	function pre( $data ) {
		if ( isset( $_GET['debug'] ) && defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			pree( $data );
		}
	}
}

/* -------------------------------------------------------------------------
 * Sanitizing and validation helpers.
 * ---------------------------------------------------------------------- */
if ( ! function_exists( 'sanitize_wpsy_data' ) ) {
	function sanitize_wpsy_data( $input ) {
		if ( is_array( $input ) ) {
			$new_input = array();
			foreach ( $input as $key => $val ) {
				$new_input[ sanitize_text_field( (string) $key ) ] = sanitize_wpsy_data( $val );
			}
			return $new_input;
		}

		$new_input = sanitize_text_field( (string) $input );

		if ( false !== strpos( $new_input, '@' ) && is_email( $new_input ) ) {
			$new_input = sanitize_email( $new_input );
		}
		if ( 0 === stripos( $new_input, 'http' ) && wp_http_validate_url( $new_input ) ) {
			$new_input = esc_url_raw( $new_input );
		}
		return $new_input;
	}
}

/**
 * Normalises a store domain. Returns an empty string when the value is not a valid host name.
 */
function wpsy_clean_domain( $domain ) {
	$domain = strtolower( trim( (string) $domain ) );
	$domain = preg_replace( '#^https?://#', '', $domain );
	$domain = preg_replace( '#[/?\#].*$#', '', $domain );
	$domain = rtrim( $domain, '.' );

	if ( ! preg_match( '/^(?=.{4,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+([a-z]{2,63}|xn--[a-z0-9-]{1,59})$/', $domain ) ) {
		return '';
	}
	return $domain;
}

/**
 * Accepts "123" or "gid://shopify/Product/123" and returns the numeric id (or an empty string).
 */
function wpsy_clean_id( $id ) {
	if ( is_object( $id ) || is_array( $id ) ) {
		return '';
	}
	if ( preg_match( '#^(?:gid://shopify/\w+/)?(\d{1,20})(?:\?.*)?$#', trim( (string) $id ), $m ) ) {
		return $m[1];
	}
	return '';
}

function wpsy_clean_limit( $limit, $default = 10 ) {
	if ( is_numeric( $limit ) && (int) $limit > 0 ) {
		return min( 250, (int) $limit );
	}
	return (int) $default;
}

function wpsy_truthy( $value ) {
	return in_array( strtolower( trim( (string) $value ) ), array( '1', 'yes', 'true', 'on', 'enable', 'show', 'display' ), true );
}

/**
 * Whitelist of sort keys accepted by the shortcodes.
 */
function wpsy_sort_key( $sort, $context = 'products' ) {
	$sort = sanitize_key( $sort );
	$map  = array(
		'products'   => array(
			'title'        => 'TITLE',
			'price'        => 'PRICE',
			'newest'       => 'CREATED_AT',
			'updated'      => 'UPDATED_AT',
			'best-selling' => 'BEST_SELLING',
			'best_selling' => 'BEST_SELLING',
			'vendor'       => 'VENDOR',
			'type'         => 'PRODUCT_TYPE',
		),
		'collection' => array(
			'title'        => 'TITLE',
			'price'        => 'PRICE',
			'newest'       => 'CREATED',
			'best-selling' => 'BEST_SELLING',
			'best_selling' => 'BEST_SELLING',
			'manual'       => 'MANUAL',
			'default'      => 'COLLECTION_DEFAULT',
		),
	);
	return isset( $map[ $context ][ $sort ] ) ? $map[ $context ][ $sort ] : '';
}

/* -------------------------------------------------------------------------
 * Settings helpers.
 * ---------------------------------------------------------------------- */
function wpsy_default_settings() {
	return array(
		'wpsy_api_key'            => '',
		'wpsy_password'           => '',
		'wpsy_storefront_shared'  => '',
		'wpsy_storefront_token'   => '',
		'wpsy_url'                => '',
		'wpsy_cache_minutes'      => 10,
		'wpsy_json_ld'            => 'yes',
		'wpsy_site_locale_prices' => 'no',
	);
}

function wpsy_get_settings() {
	$saved = get_option( 'wpsy_db_data' );
	$saved = is_array( $saved ) ? $saved : array();
	return wp_parse_args( $saved, wpsy_default_settings() );
}

function wpsy_get_domain() {
	$settings = wpsy_get_settings();
	return wpsy_clean_domain( $settings['wpsy_url'] );
}

function wpsy_is_configured() {
	$settings = wpsy_get_settings();
	return ( '' !== wpsy_clean_domain( $settings['wpsy_url'] ) && '' !== trim( (string) $settings['wpsy_storefront_token'] ) );
}

function wpsy_cache_minutes() {
	$settings = wpsy_get_settings();
	return max( 0, min( 1440, (int) $settings['wpsy_cache_minutes'] ) );
}

function wpsy_api_version() {
	return (string) apply_filters( 'wpsy_api_version', WPSY_API_VERSION );
}

function wpsy_is_pro() {
	return defined( 'WPSY_PRO' ) && WPSY_PRO;
}

function wpsy_get_last_error() {
	return (string) get_transient( 'wpsy_last_error' );
}

function wpsy_admin_only_notice( $message ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	return '<div class="wpsy-admin-notice" role="note">' . esc_html( $message ) . '</div>';
}

function wpsy_activate() {
	update_option( 'wpsy_version', WPSY_VERSION, false );
	if ( ! get_option( 'wpsy_cache_gen' ) ) {
		update_option( 'wpsy_cache_gen', 1, false );
	}
}

/**
 * Light upgrade routine: flush cached API responses when a new version is installed.
 */
function wpsy_maybe_upgrade() {
	if ( get_option( 'wpsy_version' ) !== WPSY_VERSION ) {
		update_option( 'wpsy_version', WPSY_VERSION, false );
		update_option( 'wpsy_cache_gen', (int) get_option( 'wpsy_cache_gen', 1 ) + 1, false );
	}
}
add_action( 'plugins_loaded', 'wpsy_maybe_upgrade' );

function wpsy_load_textdomain() {
	load_plugin_textdomain( 'wp-shopify', false, dirname( plugin_basename( WPSY_PLUGIN_FILE ) ) . '/languages' );
}
add_action( 'init', 'wpsy_load_textdomain' );

/* -------------------------------------------------------------------------
 * Front-end assets (loaded only where they are needed).
 * ---------------------------------------------------------------------- */
function wpsy_register_front_assets() {
	wp_register_style( 'wp-shopify-styles', WPSY_PLUGIN_URL . 'css/front-styles.css', array(), WPSY_VERSION );
	wp_register_script( 'wp-shopify-front', WPSY_PLUGIN_URL . 'js/front.js', array(), WPSY_VERSION, true );
	wp_localize_script( 'wp-shopify-front', 'wpsyFront', array( 'noResults' => __( 'No products found.', 'wp-shopify' ) ) );
}

function wpsy_enqueue_front_assets() {
	wpsy_register_front_assets();
	wp_enqueue_style( 'wp-shopify-styles' );
	wp_enqueue_script( 'wp-shopify-front' );
}

function wpsy_maybe_enqueue_front_assets() {
	wpsy_register_front_assets();

	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( ! $post ) {
		return;
	}
	foreach ( array( 'wp-shopify', 'wp-shopify-product', 'wp-shopify-continue-shopping', 'wp-shopify-related', 'wp-shopify-cart-button' ) as $shortcode ) {
		if ( has_shortcode( $post->post_content, $shortcode ) ) {
			wpsy_enqueue_front_assets();
			return;
		}
	}
}
add_action( 'wp_enqueue_scripts', 'wpsy_maybe_enqueue_front_assets' );

/* -------------------------------------------------------------------------
 * Price helpers.
 * ---------------------------------------------------------------------- */
function wpsy_price_locale() {
	$settings = wpsy_get_settings();
	$locale   = ( 'yes' === $settings['wpsy_site_locale_prices'] ) ? str_replace( '-', '_', get_locale() ) : 'en_US';
	return (string) apply_filters( 'wpsy_price_locale', $locale );
}

function wpsy_shop_product_display_price( $price, $currency = '' ) {
	$price    = (float) $price;
	$currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $currency ) );

	if ( '' !== $currency && class_exists( 'NumberFormatter' ) ) {
		try {
			$fmt = new NumberFormatter( wpsy_price_locale(), NumberFormatter::CURRENCY );
			$out = $fmt->formatCurrency( $price, $currency );
			if ( false !== $out ) {
				return $out;
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Fall through to the simple formatter below.
		}
	}

	$prefix = ( '' === $currency || 'USD' === $currency ) ? '$' : $currency . ' ';
	return $prefix . number_format( $price, 2 );
}

/**
 * Formats the min (and max, when different) price of a product as plain text.
 */
function wpsy_product_price( $product_data ) {
	$ret = '';
	if ( ! is_object( $product_data ) || ! isset( $product_data->priceRange ) || ! is_object( $product_data->priceRange ) ) {
		return $ret;
	}

	$prices   = array();
	$currency = '';
	foreach ( $product_data->priceRange as $variant ) {
		if ( is_object( $variant ) && isset( $variant->amount ) ) {
			$prices[] = (float) $variant->amount;
			$currency = isset( $variant->currencyCode ) ? $variant->currencyCode : $currency;
		}
	}

	if ( ! empty( $prices ) ) {
		$min = min( $prices );
		$max = max( $prices );
		$ret = ( $min !== $max )
			? wpsy_shop_product_display_price( $min, $currency ) . ' - ' . wpsy_shop_product_display_price( $max, $currency )
			: wpsy_shop_product_display_price( $min, $currency );
	}
	return $ret;
}

function wpsy_is_on_sale( $product_data ) {
	if ( ! is_object( $product_data ) || ! isset( $product_data->compareAtPriceRange->maxVariantPrice->amount, $product_data->priceRange->minVariantPrice->amount ) ) {
		return false;
	}
	return (float) $product_data->compareAtPriceRange->maxVariantPrice->amount > (float) $product_data->priceRange->minVariantPrice->amount;
}

function wpsy_compare_price( $product_data ) {
	if ( ! wpsy_is_on_sale( $product_data ) ) {
		return '';
	}
	$amount   = $product_data->compareAtPriceRange->maxVariantPrice->amount;
	$currency = isset( $product_data->compareAtPriceRange->maxVariantPrice->currencyCode ) ? $product_data->compareAtPriceRange->maxVariantPrice->currencyCode : '';
	return wpsy_shop_product_display_price( $amount, $currency );
}

/**
 * Adds Shopify CDN size suffix (e.g. _300x) to an image url.
 */
function wpsy_thumb_url( $url, $size = 'default' ) {
	$url = (string) $url;
	if ( '' === $url || 'default' === $size || ! preg_match( '/^\d{2,4}$/', (string) $size ) ) {
		return $url;
	}
	return preg_replace( '/(\.[a-zA-Z0-9]+)(\?.*)?$/', '_' . (int) $size . 'x$1$2', $url );
}

/* -------------------------------------------------------------------------
 * Grid rendering (shared by the free shortcode and premium add-ons).
 * ---------------------------------------------------------------------- */
function wpsy_shop_filter_bar() {
	?>
<div class="wpsy-filter-bar-wrapper">
	<label class="screen-reader-text" for="wpsy-filter-bar"><?php esc_html_e( 'Search & Filter...', 'wp-shopify' ); ?></label>
	<input type="text" name="wpsy-filter-bar" id="wpsy-filter-bar" class="wpsy-filter-bar" placeholder="<?php esc_attr_e( 'Search & Filter...', 'wp-shopify' ); ?>" />
</div>
	<?php
}

function wpsy_product_page_base( $slug ) {
	$slug = trim( (string) $slug, '/' );
	if ( is_numeric( $slug ) ) {
		$slug = (string) get_post_field( 'post_name', (int) $slug );
	}
	$parts = array_filter( array_map( 'sanitize_title', explode( '/', $slug ) ) );
	$slug  = $parts ? implode( '/', $parts ) : 'product';
	$page  = get_page_by_path( $slug );

	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/**
 * Renders a list of product nodes as the product grid.
 *
 * @param array $nodes Product objects returned by the Storefront API.
 * @param array $opts  Display options.
 * @return string
 */
function wpsy_render_grid( $nodes, $opts = array() ) {
	$opts = wp_parse_args(
		$opts,
		array(
			'url-type'     => 'default',
			'product-page' => 'product',
			'thumb-size'   => 'default',
			'columns'      => 0,
			'show-price'   => 'yes',
			'layout'       => 'grid',
			'limit'        => '',
			'searchfilter' => false,
		)
	);

	$nodes = is_array( $nodes ) ? $nodes : array();
	if ( is_numeric( $opts['limit'] ) && (int) $opts['limit'] >= 0 ) {
		$nodes = array_slice( $nodes, 0, (int) $opts['limit'] );
	}

	$domain     = wpsy_get_domain();
	$page_base  = wpsy_product_page_base( $opts['product-page'] );
	$show_price = wpsy_truthy( $opts['show-price'] );
	$columns    = max( 0, min( 8, (int) $opts['columns'] ) );

	$classes   = array( 'wpsy-shop', 'wpsy-layout-' . sanitize_html_class( $opts['layout'] ) );
	$classes   = (array) apply_filters( 'wpsy_shop_wrapper_class', $classes, $opts );
	$classes   = array_map( 'sanitize_html_class', $classes );
	$style_var = $columns ? ' style="--wpsy-cols:' . (int) $columns . '"' : '';

	ob_start();

	echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

	if ( $opts['searchfilter'] ) {
		wpsy_shop_filter_bar();
	}

	if ( ! empty( $nodes ) ) {
		echo '<ul class="wp_shopify"' . $style_var . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from an integer.

		foreach ( $nodes as $product_data ) {
			if ( ! is_object( $product_data ) ) {
				continue;
			}

			$title        = isset( $product_data->title ) ? (string) $product_data->title : '';
			$handle       = isset( $product_data->handle ) ? (string) $product_data->handle : '';
			$product_type = isset( $product_data->productType ) ? (string) $product_data->productType : '';
			$pid          = isset( $product_data->id ) ? wpsy_clean_id( $product_data->id ) : '';
			$new_tab      = false;

			if ( 'shopify' === $opts['url-type'] ) {
				$url     = ! empty( $product_data->onlineStoreUrl ) ? (string) $product_data->onlineStoreUrl : ( ( $domain && $handle ) ? 'https://' . $domain . '/products/' . rawurlencode( $handle ) : '' );
				$new_tab = true;
			} else {
				$url = add_query_arg( 'id', $pid, $page_base );
			}

			$attr = ' href="' . esc_url( $url ) . '"';
			if ( '' !== $product_type ) {
				$attr .= ' title="' . esc_attr( ucwords( $product_type ) ) . '"';
			}
			if ( $new_tab ) {
				$attr .= ' target="_blank" rel="nofollow noopener noreferrer" aria-label="' . esc_attr( $title . ' ' . __( '(Opens in a new window)', 'wp-shopify' ) ) . '"';
			}

			$image     = '';
			$image_alt = $title;
			if ( isset( $product_data->featuredImage->url ) && '' !== $product_data->featuredImage->url ) {
				$image = wpsy_thumb_url( $product_data->featuredImage->url, $opts['thumb-size'] );
				if ( ! empty( $product_data->featuredImage->altText ) ) {
					$image_alt = (string) $product_data->featuredImage->altText;
				}
			}

			$item_classes = (array) apply_filters( 'wpsy_shop_item_class', array( 'wpsy-item' ), $product_data, $opts );
			$item_classes = array_map( 'sanitize_html_class', $item_classes );

			echo '<li class="' . esc_attr( implode( ' ', $item_classes ) ) . '">';

			echo '<span class="wpsy-badges">';
			do_action( 'wpsy_shop_item_badges', $product_data, $opts );
			echo '</span>';

			echo '<a class="wpsy-thumb"' . $attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			if ( '' !== $image ) {
				echo '<img alt="' . esc_attr( $image_alt ) . '" src="' . esc_url( $image ) . '" loading="lazy" decoding="async" />';
			}
			echo '</a>';

			echo '<a class="ptitle"' . $attr . '>' . esc_html( $title ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.

			if ( $show_price ) {
				$compare = wpsy_compare_price( $product_data );
				echo '<strong class="wpsy-price">';
				if ( '' !== $compare ) {
					echo '<del class="wpsy-compare-price">' . esc_html( $compare ) . '</del> ';
				}
				echo esc_html( wpsy_product_price( $product_data ) );
				echo '</strong>';
			}

			do_action( 'wpsy_shop_item_after_price', $product_data, $opts );

			if ( ! empty( $product_data->description ) ) {
				echo '<div class="wpsy-search-text" hidden>' . esc_html( wp_strip_all_tags( (string) $product_data->description ) ) . '</div>';
			}

			echo '</li>';
		}

		echo '</ul>';
	}

	echo '</div>';

	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * [wp-shopify] shortcode.
 * ---------------------------------------------------------------------- */
function wpsy_shop_display_shortcode( $atts = array(), $content = null, $tag = '' ) {
	$atts = shortcode_atts(
		array(
			'type'         => 'products',
			'id'           => '',
			'limit'        => '',
			'url-type'     => 'default',
			'product-page' => '',
			'searchfilter' => 'no',
			'thumb-size'   => 'default',
			'columns'      => '',
			'sort'         => '',
			'reverse'      => 'no',
			'show-price'   => 'yes',
			'layout'       => 'grid',
		),
		array_change_key_case( (array) $atts, CASE_LOWER ),
		'wp-shopify'
	);

	wpsy_enqueue_front_assets();

	if ( ! wpsy_is_configured() ) {
		return wpsy_admin_only_notice( __( 'External Store for Shopify is not configured yet. Please add your store domain and Storefront access token in Settings.', 'wp-shopify' ) );
	}

	$type         = ( 'collection' === $atts['type'] ) ? 'collection' : 'products';
	$url_type     = ( 'shopify' === $atts['url-type'] ) ? 'shopify' : 'default';
	$limit        = $atts['limit'];
	$id           = wpsy_clean_id( $atts['id'] );
	$product_page = trim( sanitize_text_field( $atts['product-page'] ) );
	$product_page = ( '' !== $product_page ) ? $product_page : 'product';
	$thumb_size   = preg_match( '/^\d{2,4}$/', (string) $atts['thumb-size'] ) ? $atts['thumb-size'] : 'default';
	$layout       = in_array( $atts['layout'], array( 'grid', 'list', 'cards' ), true ) ? $atts['layout'] : 'grid';

	$store_data = array();
	$header     = '';

	if ( 'collection' === $type ) {
		if ( '' === $id ) {
			return wpsy_admin_only_notice( __( 'A valid collection id is required, for example [wp-shopify type="collection" id="141421936749"].', 'wp-shopify' ) );
		}

		$data       = wpsy_graphql_central(
			array(
				'query'   => 'collection',
				'id'      => $id,
				'limit'   => $limit,
				'sort'    => $atts['sort'],
				'reverse' => $atts['reverse'],
			),
			true
		);
		$collection = ( is_object( $data ) && isset( $data->collection ) && is_object( $data->collection ) ) ? $data->collection : null;

		if ( $collection ) {
			$header = wpsy_collection_header_html( $collection );
			if ( isset( $collection->products->edges ) && is_array( $collection->products->edges ) ) {
				$store_data = $collection->products->edges;
			}
		}
	} else {
		$data = wpsy_graphql_central(
			array(
				'query'   => 'products',
				'limit'   => $limit,
				'sort'    => $atts['sort'],
				'reverse' => $atts['reverse'],
			),
			true
		);
		if ( is_object( $data ) && isset( $data->products->edges ) && is_array( $data->products->edges ) ) {
			$store_data = $data->products->edges;
		}
	}

	$nodes = array();
	foreach ( $store_data as $edge ) {
		if ( is_object( $edge ) && isset( $edge->node ) ) {
			$nodes[] = $edge->node;
		}
	}

	if ( empty( $nodes ) && '' === $header ) {
		$error = wpsy_get_last_error();
		return wpsy_admin_only_notice( $error ? $error : __( 'No products were returned by your Shopify store.', 'wp-shopify' ) );
	}

	$html = $header . wpsy_render_grid(
		$nodes,
		array(
			'url-type'     => $url_type,
			'product-page' => $product_page,
			'thumb-size'   => $thumb_size,
			'columns'      => $atts['columns'],
			'show-price'   => $atts['show-price'],
			'layout'       => $layout,
			'limit'        => $limit,
			'searchfilter' => wpsy_truthy( $atts['searchfilter'] ),
		)
	);

	return $html;
}

function wpsy_collection_header_html( $collection ) {
	$title = isset( $collection->title ) ? (string) $collection->title : '';
	$cid   = isset( $collection->id ) ? (string) $collection->id : '';

	ob_start();
	?>
<div class="wpsy-collection" id="<?php echo esc_attr( sanitize_html_class( basename( $cid ) ) ); ?>">
	<h2><?php echo esc_html( $title ); ?></h2>
	<?php if ( ! empty( $collection->image->url ) ) : ?>
	<div class="store-collection-description" style="margin:20px 0 0 0; padding:0">
		<img class="store-collection-image" alt="<?php echo esc_attr( $title ); ?>" src="<?php echo esc_url( $collection->image->url ); ?>" style="height:200px; width:auto; float:left; margin:10px 20px 0 0;" loading="lazy" />
		<p class="store-collection-text"><?php echo isset( $collection->descriptionHtml ) ? wp_kses_post( $collection->descriptionHtml ) : ''; ?></p>
	</div>
	<?php endif; ?>
</div>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * [wp-shopify-product] shortcode.
 * ---------------------------------------------------------------------- */
function wpsy_shop_product_display_shortcode( $atts = array(), $content = null, $tag = '' ) {
	if ( is_admin() ) {
		return '';
	}

	global $wpsy_pro, $wpsy_allowed_templates;

	$atts = array_change_key_case( (array) $atts, CASE_LOWER );

	wpsy_enqueue_front_assets();

	if ( ! wpsy_is_configured() ) {
		return wpsy_admin_only_notice( __( 'External Store for Shopify is not configured yet. Please add your store domain and Storefront access token in Settings.', 'wp-shopify' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public, read only product id.
	$raw_id = isset( $atts['id'] ) ? $atts['id'] : ( isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '' );
	$id     = wpsy_clean_id( $raw_id );

	if ( '' === $id ) {
		return '';
	}

	$continue_shopping_page_id = isset( $atts['continue-shopping-page'] ) ? sanitize_text_field( $atts['continue-shopping-page'] ) : '';

	// Template names are checked against a whitelist and the file system (no path traversal possible).
	$template = isset( $atts['template'] ) ? sanitize_file_name( $atts['template'] ) : 'default';
	if ( ! preg_match( '/^[a-z0-9_-]+$/i', $template ) ) {
		$template = 'default';
	}

	$button_type = ( isset( $atts['button_type'] ) && 'js' === $atts['button_type'] ) ? 'js' : 'default';
	$shop_link   = isset( $atts['shop_link'] ) ? esc_url_raw( $atts['shop_link'] ) : home_url( 'shop' );
	$description = isset( $atts['description'] ) ? $atts['description'] : 'true';

	$wpsy_db_data             = wpsy_get_settings();
	$wpsy_db_data['wpsy_url'] = wpsy_get_domain();

	$store_data = wpsy_graphql_central(
		array(
			'query' => 'product',
			'id'    => $id,
		),
		true
	);

	$o = '';

	if ( ! is_object( $store_data ) || ! isset( $store_data->product ) || ! is_object( $store_data->product ) ) {
		return wpsy_admin_only_notice( wpsy_get_last_error() ? wpsy_get_last_error() : __( 'The requested product could not be loaded.', 'wp-shopify' ) );
	}

	$product = $store_data->product;
	$images  = isset( $product->images ) ? $product->images : null;

	$data                = array();
	$data['name']        = sanitize_text_field( isset( $product->title ) ? $product->title : '' );
	$data['price']       = wpsy_product_price( $product );
	$data['special']     = wpsy_compare_price( $product );
	$data['description'] = '';
	$data['variations']  = array();

	if ( wpsy_truthy( $description ) && isset( $product->descriptionHtml ) ) {
		$data['description'] = wp_kses_post( $product->descriptionHtml );
	}

	$image_url = '';
	if ( isset( $product->featuredImage->url ) ) {
		$image_url = esc_url( $product->featuredImage->url );
	}
	$image_alt = ( isset( $product->featuredImage->altText ) && '' !== $product->featuredImage->altText ) ? $product->featuredImage->altText : $data['name'];

	$data['image'] = '<img data-src="' . $image_url . '" src="' . $image_url . '" alt="' . esc_attr( $image_alt ) . '" loading="lazy" />';

	$handle = isset( $product->handle ) ? sanitize_title( $product->handle ) : '';

	$link         = apply_filters( 'wp-shopify-buy-button-link', 'https://' . $wpsy_db_data['wpsy_url'] . '/products/' . $handle, $store_data, $id, $continue_shopping_page_id );
	$data['link'] = esc_url_raw( $link );

	// JSON-LD structured data (once per page).
	if ( 'yes' === $wpsy_db_data['wpsy_json_ld'] ) {
		wpsy_output_product_schema( $product, $image_url );
	}

	$allowed_templates = (array) apply_filters( 'wpsy_allowed_templates', (array) $wpsy_allowed_templates );

	if ( $wpsy_pro && 'default' !== $template && in_array( $template, $allowed_templates, true ) ) {

		if ( ! empty( $product->variants->edges ) ) {
			foreach ( $product->variants->edges as $variant ) {
				$node                                     = $variant->node;
				$variant_id                               = wpsy_clean_id( $node->id );
				$data['variations'][ $variant_id ] = $node;
			}
		}

		$templates_dir = realpath( WPSY_PLUGIN_DIR . '/pro/templates' );
		$template_file = realpath( WPSY_PLUGIN_DIR . '/pro/templates/' . $template . '.php' );

		$template_css_file = realpath( WPSY_PLUGIN_DIR . '/pro/templates/css/' . $template . '.css' );
		$template_css_file = $template_css_file ? $template_css_file : '';

		$template_js_file = realpath( WPSY_PLUGIN_DIR . '/pro/templates/js/' . $template . '.js' );
		$template_js_file = $template_js_file ? $template_js_file : '';

		if ( $templates_dir && $template_file && 0 === strpos( $template_file, $templates_dir . DIRECTORY_SEPARATOR ) ) {

			if ( $template_css_file ) {
				wp_enqueue_style( 'wpsy-template-' . $template, WPSY_PLUGIN_URL . 'pro/templates/css/' . $template . '.css', array(), WPSY_VERSION );
			}
			if ( $template_js_file ) {
				wp_enqueue_script( 'wpsy-template-' . $template, WPSY_PLUGIN_URL . 'pro/templates/js/' . $template . '.js', array( 'jquery' ), WPSY_VERSION, true );
			}

			ob_start();
			include $template_file;
			$o = ob_get_clean();
		}
	} else {

		$o .= '<div class="wp_shopify_product">';

		$o .= '<div class="prod-left"><a href="' . esc_url( $data['link'] ) . '">' . $data['image'] . '</a>';

		if ( ! empty( $images->edges ) ) {
			$gallery = '';
			foreach ( $images->edges as $edge ) {
				if ( isset( $edge->node->url ) && $edge->node->url !== $product->featuredImage->url ) {
					$gallery .= '<li><a href="' . esc_url( $edge->node->url ) . '"><img src="' . esc_url( $edge->node->url ) . '" alt="' . esc_attr( $data['name'] ) . '" loading="lazy" /></a></li>';
				}
			}
			if ( '' !== $gallery ) {
				$o .= '<div class="prod-gallery"><ul>' . $gallery . '</ul></div>';
			}
		}

		$o .= '</div>';

		$o .= '<div class="prod-upper">';
		$o .= '<div class="prod-right"><a target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $data['name'] . ' ' . __( '(Opens in a new window)', 'wp-shopify' ) ) . '" href="' . esc_url( $data['link'] ) . '"><h3>' . esc_html( $data['name'] ) . '</h3></a><br />';

		$o .= '<div class="prod-price">';
		if ( '' !== $data['special'] ) {
			$o .= '<del class="wpsy-compare-price">' . esc_html( $data['special'] ) . '</del> ';
		}
		$o .= esc_html( $data['price'] ) . '</div>';

		if ( isset( $product->availableForSale ) && false === $product->availableForSale ) {
			$o .= '<div class="wpsy-soldout-label">' . esc_html__( 'Sold out', 'wp-shopify' ) . '</div>';
		}

		if ( function_exists( 'wpsy_add_to_cart_js' ) && 'js' === $button_type ) {
			$o .= wpsy_add_to_cart_js( $id, $wpsy_db_data );
		} else {
			$o .= '<div class="prod-buy"><a href="' . esc_url( $data['link'] ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $data['name'] . ' ' . __( '(Opens in a new window)', 'wp-shopify' ) ) . '"><img src="' . esc_url( WPSY_PLUGIN_URL . 'images/btn.png' ) . '" alt="' . esc_attr__( 'Buy now', 'wp-shopify' ) . '" /></a></div>';
		}

		$o .= '</div>'; // prod-right.
		$o .= '</div>'; // prod-upper.
		$o .= '<div class="prod-clear"></div>';
		$o .= '<div class="prod-desc">' . $data['description'] . '</div>';

		if ( null !== $content && '' !== trim( $content ) ) {
			$o .= do_shortcode( wpautop( wp_kses_post( $content ) ) );
		}

		ob_start();
		do_action( 'wp-shopify-product-after-description' );
		$o .= ob_get_clean();

		$o .= '</div>';
	}

	return $o;
}

/**
 * Prints Product structured data once per request.
 */
function wpsy_output_product_schema( $product, $image_url = '' ) {
	static $printed = false;
	if ( $printed || ! is_object( $product ) ) {
		return;
	}
	$printed = true;

	$permalink = get_permalink();
	$offer     = array(
		'@type'        => 'Offer',
		'url'          => esc_url_raw( $permalink ? add_query_arg( 'id', wpsy_clean_id( $product->id ), $permalink ) : home_url( '/' ) ),
		'availability' => ( isset( $product->availableForSale ) && false === $product->availableForSale ) ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
	);
	if ( isset( $product->priceRange->minVariantPrice->amount ) ) {
		$offer['price']         = (string) $product->priceRange->minVariantPrice->amount;
		$offer['priceCurrency'] = isset( $product->priceRange->minVariantPrice->currencyCode ) ? (string) $product->priceRange->minVariantPrice->currencyCode : '';
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'name'        => isset( $product->title ) ? wp_strip_all_tags( $product->title ) : '',
		'description' => isset( $product->description ) ? wp_strip_all_tags( $product->description ) : '',
		'offers'      => $offer,
	);
	if ( '' !== $image_url ) {
		$schema['image'] = $image_url;
	}
	if ( ! empty( $product->vendor ) ) {
		$schema['brand'] = array(
			'@type' => 'Brand',
			'name'  => wp_strip_all_tags( $product->vendor ),
		);
	}

	$schema = apply_filters( 'wpsy_product_schema', $schema, $product );
	if ( empty( $schema ) ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with hex tag flags.
}

/* -------------------------------------------------------------------------
 * Sold out badge (free). Premium adds the sale badge on the same hook.
 * ---------------------------------------------------------------------- */
function wpsy_soldout_badge( $product_data ) {
	if ( isset( $product_data->availableForSale ) && false === $product_data->availableForSale ) {
		echo '<span class="wpsy-badge wpsy-badge-soldout">' . esc_html__( 'Sold out', 'wp-shopify' ) . '</span>';
	}
}
add_action( 'wpsy_shop_item_badges', 'wpsy_soldout_badge', 20 );

/* -------------------------------------------------------------------------
 * Misc helpers kept for backward compatibility.
 * ---------------------------------------------------------------------- */
function wpsy_shop_product_display_get_error( $msg ) {
	return '<div class="shop_product_display-box">' . wp_kses_post( $msg ) . '</div>';
}

/* -------------------------------------------------------------------------
 * [wp-shopify-continue-shopping] shortcode.
 * ---------------------------------------------------------------------- */
function wp_shopify_continue_shopping_callback( $atts = array() ) {
	$atts   = array_change_key_case( (array) $atts, CASE_LOWER );
	$domain = wpsy_get_domain();

	if ( '' === $domain ) {
		return '';
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read only request values.
	$raw_id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : ( isset( $atts['id'] ) ? $atts['id'] : '' );
	$id     = wpsy_clean_id( $raw_id );

	if ( isset( $_GET['product'] ) ) {
		$product = sanitize_title_with_dashes( wp_unslash( $_GET['product'] ) );
	} else {
		$product = isset( $atts['product'] ) ? sanitize_title_with_dashes( $atts['product'] ) : '';
	}
	// phpcs:enable

	if ( '' !== $id ) {
		$store_data = wpsy_graphql_central(
			array(
				'query' => 'product',
				'id'    => $id,
			),
			true
		);
		if ( is_object( $store_data ) && isset( $store_data->product->handle ) ) {
			$product = sanitize_title( $store_data->product->handle );
		}
	}

	if ( '' === $product ) {
		return '';
	}

	wpsy_enqueue_front_assets();

	$product_url = esc_url( 'https://' . $domain . '/products/' . $product );

	ob_start();
	?>
<div class="wp-shopify-continue-shopping-wrapper">
	<iframe src="<?php echo $product_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>" title="<?php esc_attr_e( 'Continue shopping', 'wp-shopify' ); ?>" class="wp-shopify-continue-shopping-iframe" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</div>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * Shortcode registration.
 * ---------------------------------------------------------------------- */
function wpsy_pro_only_stub( $atts = array(), $content = null, $tag = '' ) {
	/* translators: %s: shortcode tag */
	return wpsy_admin_only_notice( sprintf( __( '[%s] is a Premium feature of External Store for Shopify.', 'wp-shopify' ), $tag ) );
}

function wpsy_shop_product_display_shortcodes_init() {
	add_shortcode( 'wp-shopify', 'wpsy_shop_display_shortcode' );
	add_shortcode( 'wp-shopify-product', 'wpsy_shop_product_display_shortcode' );
	add_shortcode( 'wp-shopify-continue-shopping', 'wp_shopify_continue_shopping_callback' );

	// Premium only shortcodes. When the /pro folder exists it replaces these (priority 20).
	add_shortcode( 'wp-shopify-related', 'wpsy_pro_only_stub' );
	add_shortcode( 'wp-shopify-cart-button', 'wpsy_pro_only_stub' );
}
add_action( 'init', 'wpsy_shop_product_display_shortcodes_init' );
