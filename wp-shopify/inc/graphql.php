<?php
/**
 * Shopify Storefront API layer: query builders, request handling and caching.
 *
 * @package External_Store_for_Shopify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'wp_ajax_wpsy_graphql_central', 'wpsy_graphql_central' );

function wpsy_set_last_error( $message ) {
	set_transient( 'wpsy_last_error', sanitize_text_field( $message ), HOUR_IN_SECONDS );
}

function wpsy_clear_last_error() {
	delete_transient( 'wpsy_last_error' );
}

/**
 * Invalidates every cached API response.
 */
function wpsy_flush_cache() {
	update_option( 'wpsy_cache_gen', (int) get_option( 'wpsy_cache_gen', 1 ) + 1, false );
}

/**
 * Common product card fields used by listings.
 */
function wpsy_graphql_card_fields() {
	return '
		id
		title
		handle
		productType
		description
		onlineStoreUrl
		availableForSale
		variants(first: 2) { edges { node { id title availableForSale } } }
		priceRange {
			maxVariantPrice { amount currencyCode }
			minVariantPrice { amount currencyCode }
		}
		compareAtPriceRange {
			maxVariantPrice { amount currencyCode }
			minVariantPrice { amount currencyCode }
		}
		featuredImage { url altText }
	';
}

/**
 * Builds a GraphQL query. Every dynamic value is validated (integers, whitelisted enums or numeric ids),
 * so user supplied strings can never reach the query.
 *
 * @param string $name Query name: shop, products, product, collection, related.
 * @param array  $args Query arguments.
 * @return string Empty string for unknown queries.
 */
function wpsy_build_query( $name, $args = array() ) {
	$args  = is_array( $args ) ? $args : array();
	$id    = isset( $args['id'] ) ? wpsy_clean_id( $args['id'] ) : '';
	$limit = wpsy_clean_limit( isset( $args['limit'] ) ? $args['limit'] : '', 10 );

	switch ( $name ) {
		case 'shop':
			return '{ shop { name description } }';

		case 'products':
			$arguments = 'first: ' . $limit;
			$sort      = wpsy_sort_key( isset( $args['sort'] ) ? $args['sort'] : '', 'products' );
			if ( '' !== $sort ) {
				$arguments .= ', sortKey: ' . $sort . ', reverse: ' . ( wpsy_truthy( isset( $args['reverse'] ) ? $args['reverse'] : '' ) ? 'true' : 'false' );
			}
			return '{ products(' . $arguments . ') { edges { node { ' . wpsy_graphql_card_fields() . ' } } } }';

		case 'product':
			if ( '' === $id ) {
				return '';
			}
			return '{
				product(id: "gid://shopify/Product/' . $id . '") {
					id
					handle
					title
					vendor
					description
					descriptionHtml
					availableForSale
					featuredImage { url altText }
					priceRange {
						maxVariantPrice { amount currencyCode }
						minVariantPrice { amount currencyCode }
					}
					compareAtPriceRange {
						maxVariantPrice { amount currencyCode }
						minVariantPrice { amount currencyCode }
					}
					images(first: 10) { edges { node { url altText } } }
					variants(first: 15) {
						edges {
							node {
								id
								sku
								title
								availableForSale
								price { amount currencyCode }
								compareAtPrice { amount currencyCode }
								image { id url }
							}
						}
					}
					options { id name values }
				}
			}';

		case 'collection':
			if ( '' === $id ) {
				return '';
			}
			$arguments = 'first: ' . $limit;
			$sort      = wpsy_sort_key( isset( $args['sort'] ) ? $args['sort'] : '', 'collection' );
			if ( '' !== $sort ) {
				$arguments .= ', sortKey: ' . $sort . ', reverse: ' . ( wpsy_truthy( isset( $args['reverse'] ) ? $args['reverse'] : '' ) ? 'true' : 'false' );
			}
			return '{
				collection(id: "gid://shopify/Collection/' . $id . '") {
					id
					title
					handle
					description
					descriptionHtml
					image { url }
					products(' . $arguments . ') { edges { node { ' . wpsy_graphql_card_fields() . ' } } }
				}
			}';

		case 'related':
			if ( '' === $id ) {
				return '';
			}
			return '{ productRecommendations(productId: "gid://shopify/Product/' . $id . '") { ' . wpsy_graphql_card_fields() . ' } }';
	}

	return '';
}

/**
 * Sends a query to the Storefront API (with transient caching).
 *
 * @return object|string Decoded "data" object, or an empty string on failure.
 */
function wpsy_api_fetch( $name, $args = array() ) {
	$settings = wpsy_get_settings();
	$domain   = wpsy_clean_domain( $settings['wpsy_url'] );
	$token    = trim( (string) $settings['wpsy_storefront_token'] );

	if ( '' === $domain || '' === $token ) {
		wpsy_set_last_error( __( 'The store domain or the Storefront access token is missing.', 'wp-shopify' ) );
		return '';
	}

	$query = wpsy_build_query( $name, $args );
	if ( '' === $query ) {
		wpsy_set_last_error( __( 'Invalid request: a valid numeric id is required.', 'wp-shopify' ) );
		return '';
	}

	$query     = trim( preg_replace( '/\s+/', ' ', $query ) );
	$ttl       = wpsy_cache_minutes() * MINUTE_IN_SECONDS;
	$use_cache = ( $ttl > 0 && 'shop' !== $name && empty( $args['nocache'] ) );
	$cache_key = 'wpsy_' . md5( $domain . '|' . wpsy_api_version() . '|' . (int) get_option( 'wpsy_cache_gen', 1 ) . '|' . $query );

	if ( $use_cache ) {
		$cached = get_transient( $cache_key );
		if ( is_string( $cached ) && '' !== $cached ) {
			$decoded = json_decode( $cached );
			if ( is_object( $decoded ) ) {
				return $decoded;
			}
		}
	}

	$response = wp_remote_post(
		'https://' . $domain . '/api/' . rawurlencode( wpsy_api_version() ) . '/graphql.json',
		array(
			'timeout'     => 15,
			'redirection' => 3,
			'headers'     => array(
				'Content-Type'                      => 'application/graphql',
				'Accept'                            => 'application/json',
				'X-Shopify-Storefront-Access-Token' => $token,
			),
			'body'        => $query,
		)
	);

	if ( is_wp_error( $response ) ) {
		wpsy_set_last_error( $response->get_error_message() );
		return '';
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ) );

	if ( 401 === $code || 403 === $code ) {
		wpsy_set_last_error( __( 'Shopify rejected the Storefront access token (HTTP 401/403). Please check your credentials and access scopes.', 'wp-shopify' ) );
		return '';
	}
	if ( 200 !== $code || ! is_object( $body ) ) {
		/* translators: %d: HTTP status code */
		wpsy_set_last_error( sprintf( __( 'Unexpected response from Shopify (HTTP %d).', 'wp-shopify' ), $code ) );
		return '';
	}

	$has_data = ( isset( $body->data ) && is_object( $body->data ) );

	if ( isset( $body->errors ) && ! $has_data ) {
		$message = __( 'Shopify returned an error.', 'wp-shopify' );
		if ( is_array( $body->errors ) && isset( $body->errors[0]->message ) ) {
			$message = (string) $body->errors[0]->message;
		} elseif ( is_string( $body->errors ) ) {
			$message = $body->errors;
		}
		wpsy_set_last_error( $message );
		return '';
	}

	if ( ! $has_data ) {
		wpsy_set_last_error( __( 'Shopify returned an empty response.', 'wp-shopify' ) );
		return '';
	}

	wpsy_clear_last_error();

	if ( $use_cache ) {
		set_transient( $cache_key, wp_json_encode( $body->data ), $ttl );
	}

	return $body->data;
}

/**
 * Central entry point (kept for backward compatibility).
 *
 * - Internal calls: wpsy_graphql_central( array( 'query' => 'products' ), true ) returns the data object.
 * - AJAX calls (administrators only, nonce protected) return JSON.
 */
if ( ! function_exists( 'wpsy_graphql_central' ) ) {
	function wpsy_graphql_central( $posted = array(), $inner = false ) {

		if ( ! $inner ) {
			check_ajax_referer( 'wpsy-nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'wp-shopify' ) ), 403 );
			}
			$posted = sanitize_wpsy_data( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		}

		$posted = is_array( $posted ) ? $posted : array();
		$name   = isset( $posted['query'] ) ? sanitize_key( $posted['query'] ) : '';
		$data   = wpsy_api_fetch( $name, $posted );

		if ( $inner ) {
			return $data;
		}

		if ( ! is_object( $data ) ) {
			wp_send_json_error( array( 'message' => wpsy_get_last_error() ) );
		}
		wp_send_json_success( $data );
	}
}
