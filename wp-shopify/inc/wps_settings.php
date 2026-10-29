<?php
/**
 * Settings page template.
 *
 * @package External_Store_for_Shopify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $wpsy_data, $wpsy_pro, $wpsy_premium_copy;

$wpsy_settings   = wpsy_get_settings();
$wpsy_site_slug  = strtolower( preg_replace( '/[^a-zA-Z0-9]/', '', get_bloginfo( 'name' ) ) );
$wpsy_site_slug  = ( '' !== $wpsy_site_slug ) ? $wpsy_site_slug : 'mysite';
$wpsy_domain     = wpsy_get_domain();
$wpsy_configured = wpsy_is_configured();

$wpsy_shop = '';
if ( $wpsy_configured ) {
	$wpsy_shop_data = wpsy_graphql_central( array( 'query' => 'shop' ), true );
	if ( is_object( $wpsy_shop_data ) && isset( $wpsy_shop_data->shop->name ) ) {
		$wpsy_shop = $wpsy_shop_data->shop;
	}
}

$wpsy_has_secret = function ( $key ) use ( $wpsy_settings ) {
	return '' !== trim( (string) $wpsy_settings[ $key ] );
};
$wpsy_secret_placeholder = __( 'Saved. Leave blank to keep the current value.', 'wp-shopify' );

$wpsy_premium_features = array(
	__( 'JS cart drawer with variant and quantity support, line quantity controls and a live cart counter', 'wp-shopify' ),
	__( 'Quick "Add to cart" buttons inside the product grid (single variant products)', 'wp-shopify' ),
	__( 'Sale badges and the list / cards layouts for the product grid', 'wp-shopify' ),
	__( '[wp-shopify-related] shortcode: related product recommendations', 'wp-shopify' ),
	__( '[wp-shopify-cart-button] shortcode: place the cart button anywhere', 'wp-shopify' ),
	__( 'Premium product templates (Bello Sole)', 'wp-shopify' ),
);
?>

<div class="wrap wpsy_settings_div">

<h2>
	<?php echo esc_html( $wpsy_data['Name'] ); ?>
	(<?php echo esc_html( $wpsy_data['Version'] ); ?>)<?php echo $wpsy_pro ? ' Pro' : ''; ?> - <?php esc_html_e( 'Settings', 'wp-shopify' ); ?>
	<?php if ( $wpsy_shop ) : ?>
		<span title="<?php echo esc_attr( isset( $wpsy_shop->description ) ? $wpsy_shop->description : '' ); ?>"><i class="fas fa-crown"></i> <?php echo esc_html( $wpsy_shop->name ); ?></span>
	<?php endif; ?>
</h2>

<?php
settings_errors();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
if ( isset( $_GET['wpsy-disconnected'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Your Shopify credentials have been removed.', 'wp-shopify' ) . '</p></div>';
}
?>

<h2 class="nav-tab-wrapper">
	<a href="#config-settings" class="nav-tab nav-tab-active" data-tab="config-settings"><?php esc_html_e( 'Configuration', 'wp-shopify' ); ?> <i class="fas fa-tools"></i></a>
	<a href="#shortcodes" class="nav-tab" data-tab="shortcodes"><?php esc_html_e( 'Shortcodes', 'wp-shopify' ); ?> <i class="fas fa-code"></i></a>
	<a href="#tools" class="nav-tab" data-tab="tools"><?php esc_html_e( 'Tools', 'wp-shopify' ); ?> <i class="fas fa-wrench"></i></a>
	<a href="#help" class="nav-tab" style="float:right" data-tab="help"><?php esc_html_e( 'Help', 'wp-shopify' ); ?> <i class="fas fa-question-circle"></i></a>
</h2>

<!-- Configuration -->
<div class="nav-tab-content container-fluid tab-config-settings" data-content="config-settings" style="padding-top:20px;">

	<form action="options.php" method="post" class="ignore">
		<?php
		settings_fields( 'wpsy_settings_page' );
		shoppd_settings_section_callback();
		?>
		<table class="form-table" role="presentation"><tbody>

			<tr class="wpsy_fields"><th scope="row" class="connection-bar">
				<i class="fas fa-sync-alt"></i><?php esc_html_e( 'Connect and Sync', 'wp-shopify' ); ?>
				<?php if ( ! $wpsy_shop ) : ?>
					<span class="connection-status-red"><i class="fas fa-circle"></i><?php esc_html_e( 'Disconnected', 'wp-shopify' ); ?></span>
				<?php else : ?>
					<span class="connection-status-green"><i class="fas fa-circle"></i><?php esc_html_e( 'Connected', 'wp-shopify' ); ?></span>
				<?php endif; ?>
			</th></tr>

			<tr class="wpsy_fields"><th scope="row" style="padding-bottom:0;"><hr /></th></tr>

			<tr class="wpsy_fields"><th scope="row" style="font-weight:normal;">
				<?php esc_html_e( 'Enter your Shopify private app API keys below.', 'wp-shopify' ); ?>
				<a style="color:#4D83A8;" href="#help" class="wpsy-need-help"><?php esc_html_e( 'Need help finding these?', 'wp-shopify' ); ?></a>
			</th></tr>

			<tr class="wpsy_fields"><th scope="row"><label for="wpsy_api_key"><?php esc_html_e( 'API Key', 'wp-shopify' ); ?></label> <i style="color:#06C;" class="fas fa-key"></i></th></tr>
			<tr class="wpsy_fields"><th>
				<input type="text" id="wpsy_api_key" name="wpsy_db_data[wpsy_api_key]" value="<?php echo esc_attr( $wpsy_settings['wpsy_api_key'] ); ?>" size="80" class="wpsy_fields" autocomplete="off" />
			</th></tr>

			<tr class="wpsy_fields"><th scope="row"><label for="wpsy_password"><?php esc_html_e( 'API Password', 'wp-shopify' ); ?></label> <i style="color:#F90;" class="fas fa-fingerprint"></i></th></tr>
			<tr class="wpsy_fields"><th>
				<input type="password" id="wpsy_password" name="wpsy_db_data[wpsy_password]" value="" placeholder="<?php echo $wpsy_has_secret( 'wpsy_password' ) ? esc_attr( $wpsy_secret_placeholder ) : ''; ?>" size="80" class="wpsy_fields" autocomplete="new-password" />
			</th></tr>

			<tr class="wpsy_fields"><th scope="row"><label for="wpsy_storefront_shared"><?php esc_html_e( 'Shared Secret', 'wp-shopify' ); ?></label> <i style="color:#8A8B8C;" class="fas fa-share-alt"></i></th></tr>
			<tr class="wpsy_fields"><th>
				<input type="password" id="wpsy_storefront_shared" name="wpsy_db_data[wpsy_storefront_shared]" value="" placeholder="<?php echo $wpsy_has_secret( 'wpsy_storefront_shared' ) ? esc_attr( $wpsy_secret_placeholder ) : ''; ?>" size="80" class="wpsy_fields" autocomplete="new-password" />
			</th></tr>

			<tr class="wpsy_fields"><th scope="row"><label for="wpsy_storefront_token"><?php esc_html_e( 'Storefront Access Token', 'wp-shopify' ); ?></label> <i style="color:#333;" class="fas fa-user-lock"></i></th></tr>
			<tr class="wpsy_fields"><th>
				<input type="password" id="wpsy_storefront_token" name="wpsy_db_data[wpsy_storefront_token]" value="" placeholder="<?php echo $wpsy_has_secret( 'wpsy_storefront_token' ) ? esc_attr( $wpsy_secret_placeholder ) : ''; ?>" size="80" class="wpsy_fields" autocomplete="new-password" />
			</th></tr>

			<tr class="wpsy_fields"><th scope="row"><label for="wpsy_url"><?php esc_html_e( 'Domain', 'wp-shopify' ); ?></label> <i style="color:#96BF48;" class="fab fa-shopify"></i></th></tr>
			<tr class="wpsy_fields"><th>
				<input type="text" id="wpsy_url" name="wpsy_db_data[wpsy_url]" value="<?php echo esc_attr( $wpsy_domain ); ?>" size="80" class="wpsy_fields" placeholder="<?php esc_attr_e( 'your-store.myshopify.com', 'wp-shopify' ); ?>" autocomplete="off" />
			</th></tr>

			<tr class="wpsy_fields"><th scope="row" style="padding-top:10px;"><hr /></th></tr>

			<tr class="wpsy_fields"><th scope="row"><label for="wpsy_cache_minutes"><?php esc_html_e( 'Cache (minutes)', 'wp-shopify' ); ?></label> <i style="color:#369;" class="fas fa-bolt"></i></th></tr>
			<tr class="wpsy_fields"><th>
				<input type="number" min="0" max="1440" id="wpsy_cache_minutes" name="wpsy_db_data[wpsy_cache_minutes]" value="<?php echo esc_attr( (int) $wpsy_settings['wpsy_cache_minutes'] ); ?>" class="wpsy_fields small-text" />
				<p class="description"><?php esc_html_e( 'Product data from Shopify is cached to make your pages faster. Use 0 to disable caching.', 'wp-shopify' ); ?></p>
			</th></tr>

			<tr class="wpsy_fields"><th scope="row" style="font-weight:normal;">
				<label>
					<input type="checkbox" name="wpsy_db_data[wpsy_json_ld]" value="yes" <?php checked( 'yes', $wpsy_settings['wpsy_json_ld'] ); ?> />
					<?php esc_html_e( 'Add Product structured data (JSON-LD) on single product pages', 'wp-shopify' ); ?>
				</label>
			</th></tr>

			<tr class="wpsy_fields"><th scope="row" style="font-weight:normal;">
				<label>
					<input type="checkbox" name="wpsy_db_data[wpsy_site_locale_prices]" value="yes" <?php checked( 'yes', $wpsy_settings['wpsy_site_locale_prices'] ); ?> />
					<?php esc_html_e( 'Format prices using the website language instead of English (US)', 'wp-shopify' ); ?>
				</label>
			</th></tr>

			<tr class="wpsy_fields"><th scope="row">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Connect your Shopify store', 'wp-shopify' ); ?>" />
			</th></tr>
		</tbody></table>
	</form>

	<?php if ( $wpsy_domain ) : ?>
	<ul class="wpsy-useful-links">
		<li><a href="<?php echo esc_url( 'https://' . $wpsy_domain ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Go to Store (Opens in a new window)', 'wp-shopify' ); ?>"><i style="color:#96BF48;" class="fab fa-shopify"></i> <?php esc_html_e( 'Go to Store', 'wp-shopify' ); ?></a></li>
		<li><a href="https://accounts.shopify.com/store-login" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Store Login (Opens in a new window)', 'wp-shopify' ); ?>"><i style="color:#F93;" class="fas fa-user-lock"></i> <?php esc_html_e( 'Store Login', 'wp-shopify' ); ?></a></li>
		<li><a href="<?php echo esc_url( 'https://' . $wpsy_domain . '/admin/apps/development' ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Application / API Credentials (Opens in a new window)', 'wp-shopify' ); ?>"><i style="color:#369;" class="fas fa-rocket"></i> <?php esc_html_e( 'Application / API Credentials', 'wp-shopify' ); ?></a></li>
		<li><a href="<?php echo esc_url( 'https://' . $wpsy_domain . '/admin/collections' ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Create Collections (Opens in a new window)', 'wp-shopify' ); ?>"><i style="color:#C3C;" class="fas fa-boxes"></i> <?php esc_html_e( 'Create Collections', 'wp-shopify' ); ?></a></li>
	</ul>
	<?php endif; ?>

</div>

<!-- Shortcodes -->
<div class="nav-tab-content container-fluid hides tab-shortcodes" data-content="shortcodes">
	<div class="row mt-3">

		<ol class="wp-shopify-shortcodes">
			<li>[wp-shopify product-page="product"]</li>
			<li>[wp-shopify-product]
				<br /><br />
				<small><strong><?php esc_html_e( 'OR', 'wp-shopify' ); ?></strong> <br />[wp-shopify-product id="<?php esc_html_e( 'some product ID', 'wp-shopify' ); ?>" button_type="default"]</small>
				<br /><br />
				<small><strong><?php esc_html_e( 'OR', 'wp-shopify' ); ?></strong> <br />[wp-shopify-product id="<?php esc_html_e( 'some product ID', 'wp-shopify' ); ?>" <i class="green">button_type="js"</i> <i class="red">template="bello-sole"</i> continue-shopping-page="<?php esc_html_e( 'some product ID', 'wp-shopify' ); ?>"] <b class="red">(<?php esc_html_e( 'Premium Version', 'wp-shopify' ); ?>)</b><br />
					<br />
					<ol>
						<li>add_action('wp-shopify-product-after-description', 'callback_func'); //FOR EXTRA HTML BASED CONTENT</li>
						<li>add_filter('wp-shopify-buy-button-link', '<?php echo esc_html( $wpsy_site_slug ); ?>_shopify_buy_btn_callback', 10, 4);</li>
					</ol>
				</small>
				<br /><br />
			</li>
			<li>[wp-shopify type="collection" id="141421936749" limit="4" searchfilter="yes" url-type="default|shopify" thumb-size="default|300" product-page="product"]</li>
			<li>[wp-shopify type="products" limit="100" product-page="product"]</li>
			<li><?php esc_html_e( 'New in 1.7.0:', 'wp-shopify' ); ?> [wp-shopify type="products" columns="4" sort="title|price|newest|best-selling" reverse="yes" show-price="yes|no"]</li>
			<li>[wp-shopify-continue-shopping]</li>
			<li>[wp-shopify-related id="<?php esc_html_e( 'some product ID', 'wp-shopify' ); ?>" limit="4"] <b class="red">(<?php esc_html_e( 'Premium Version', 'wp-shopify' ); ?>)</b></li>
			<li>[wp-shopify-cart-button] <b class="red">(<?php esc_html_e( 'Premium Version', 'wp-shopify' ); ?>)</b></li>
		</ol>

		<div class="wpsy-generator">
			<h3><i class="fas fa-magic"></i> <?php esc_html_e( 'Shortcode generator', 'wp-shopify' ); ?></h3>
			<p>
				<label for="wpsy-gen-type"><?php esc_html_e( 'Show', 'wp-shopify' ); ?></label>
				<select id="wpsy-gen-type">
					<option value="products"><?php esc_html_e( 'All products', 'wp-shopify' ); ?></option>
					<option value="collection"><?php esc_html_e( 'A collection', 'wp-shopify' ); ?></option>
				</select>
			</p>
			<p>
				<label for="wpsy-gen-id"><?php esc_html_e( 'Collection ID', 'wp-shopify' ); ?></label>
				<input type="text" id="wpsy-gen-id" class="regular-text" inputmode="numeric" />
			</p>
			<p>
				<label for="wpsy-gen-limit"><?php esc_html_e( 'Limit', 'wp-shopify' ); ?></label>
				<input type="number" id="wpsy-gen-limit" class="small-text" min="1" max="250" value="12" />
				<label for="wpsy-gen-columns"><?php esc_html_e( 'Columns', 'wp-shopify' ); ?></label>
				<input type="number" id="wpsy-gen-columns" class="small-text" min="1" max="8" value="4" />
			</p>
			<p>
				<label for="wpsy-gen-sort"><?php esc_html_e( 'Sort by', 'wp-shopify' ); ?></label>
				<select id="wpsy-gen-sort">
					<option value=""><?php esc_html_e( 'Default', 'wp-shopify' ); ?></option>
					<option value="title"><?php esc_html_e( 'Title', 'wp-shopify' ); ?></option>
					<option value="price"><?php esc_html_e( 'Price', 'wp-shopify' ); ?></option>
					<option value="newest"><?php esc_html_e( 'Newest', 'wp-shopify' ); ?></option>
					<option value="best-selling"><?php esc_html_e( 'Best selling', 'wp-shopify' ); ?></option>
				</select>
				<label><input type="checkbox" id="wpsy-gen-reverse" /> <?php esc_html_e( 'Reverse order', 'wp-shopify' ); ?></label>
			</p>
			<p>
				<label for="wpsy-gen-urltype"><?php esc_html_e( 'Product links', 'wp-shopify' ); ?></label>
				<select id="wpsy-gen-urltype">
					<option value="default"><?php esc_html_e( 'Open on my website', 'wp-shopify' ); ?></option>
					<option value="shopify"><?php esc_html_e( 'Open on Shopify', 'wp-shopify' ); ?></option>
				</select>
			</p>
			<p>
				<label><input type="checkbox" id="wpsy-gen-search" /> <?php esc_html_e( 'Search filter', 'wp-shopify' ); ?></label>
				<label><input type="checkbox" id="wpsy-gen-price" checked="checked" /> <?php esc_html_e( 'Show prices', 'wp-shopify' ); ?></label>
			</p>
			<p>
				<input type="text" id="wpsy-gen-output" class="large-text code" readonly="readonly" value="" aria-label="<?php esc_attr_e( 'Generated shortcode', 'wp-shopify' ); ?>" />
			</p>
			<p>
				<button type="button" class="button button-primary" id="wpsy-gen-copy"><?php esc_html_e( 'Copy shortcode', 'wp-shopify' ); ?></button>
			</p>
		</div>

	</div>
</div>

<!-- Tools -->
<div class="nav-tab-content container-fluid hides tab-tools" data-content="tools">
	<div class="row mt-3 wpsy-tools">

		<h3><?php esc_html_e( 'Connection', 'wp-shopify' ); ?></h3>
		<p>
			<button type="button" class="button" id="wpsy-test-connection"><?php esc_html_e( 'Test connection', 'wp-shopify' ); ?></button>
			<button type="button" class="button" id="wpsy-clear-cache"><?php esc_html_e( 'Clear cache', 'wp-shopify' ); ?></button>
			<span id="wpsy-tool-result" role="status" aria-live="polite"></span>
		</p>

		<table class="widefat striped wpsy-status-table" role="presentation"><tbody>
			<tr><td><?php esc_html_e( 'Plugin version', 'wp-shopify' ); ?></td><td><?php echo esc_html( WPSY_VERSION ); ?><?php echo $wpsy_pro ? ' (Pro)' : ''; ?></td></tr>
			<tr><td><?php esc_html_e( 'Storefront API version', 'wp-shopify' ); ?></td><td><?php echo esc_html( wpsy_api_version() ); ?></td></tr>
			<tr><td><?php esc_html_e( 'Cache', 'wp-shopify' ); ?></td><td><?php echo esc_html( wpsy_cache_minutes() ? wpsy_cache_minutes() . ' ' . __( 'minutes', 'wp-shopify' ) : __( 'Disabled', 'wp-shopify' ) ); ?></td></tr>
			<tr><td><?php esc_html_e( 'PHP version', 'wp-shopify' ); ?></td><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
			<tr><td><?php esc_html_e( 'Last error', 'wp-shopify' ); ?></td><td><?php echo esc_html( wpsy_get_last_error() ? wpsy_get_last_error() : __( 'None', 'wp-shopify' ) ); ?></td></tr>
		</tbody></table>

		<h3 style="margin-top:30px;"><?php esc_html_e( 'Premium features', 'wp-shopify' ); ?> <?php echo $wpsy_pro ? '<span class="wpsy-pro-active">' . esc_html__( 'Active', 'wp-shopify' ) . '</span>' : ''; ?></h3>
		<ul class="wpsy-feature-list">
			<?php foreach ( $wpsy_premium_features as $wpsy_feature ) : ?>
				<li><i class="fas fa-<?php echo $wpsy_pro ? 'check-circle' : 'crown'; ?>"></i> <?php echo esc_html( $wpsy_feature ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php if ( ! $wpsy_pro ) : ?>
			<p><a class="button button-primary" href="<?php echo esc_url( $wpsy_premium_copy ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Go Premium', 'wp-shopify' ); ?></a></p>
		<?php endif; ?>

		<h3 style="margin-top:30px;"><?php esc_html_e( 'Disconnect', 'wp-shopify' ); ?></h3>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="ignore wpsy-disconnect-form" style="width:auto;float:none;">
			<input type="hidden" name="action" value="wpsy_disconnect" />
			<?php wp_nonce_field( 'wpsy_disconnect' ); ?>
			<p class="description"><?php esc_html_e( 'Remove the saved Shopify credentials from this website.', 'wp-shopify' ); ?></p>
			<button type="submit" class="button button-link-delete" id="wpsy-disconnect"><?php esc_html_e( 'Remove saved credentials', 'wp-shopify' ); ?></button>
		</form>

	</div>
</div>

<!-- Help -->
<div class="nav-tab-content container-fluid hides tab-help" data-content="help">
	<div class="row mt-3">
		<ul class="position-relative">
			<li><a class="btn btn-sm btn-info" href="https://wordpress.org/support/plugin/wp-shopify/" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Open a Ticket on Support Forums (Opens in a new window)', 'wp-shopify' ); ?>"><?php esc_html_e( 'Open a Ticket on Support Forums', 'wp-shopify' ); ?> &nbsp;<i class="fas fa-tag"></i></a></li>
			<li><a class="btn btn-sm btn-warning" href="http://demo.androidbubble.com/contact/" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Contact Developer (Opens in a new window)', 'wp-shopify' ); ?>"><?php esc_html_e( 'Contact Developer', 'wp-shopify' ); ?> &nbsp;<i class="fas fa-headset"></i></a></li>
			<li><a class="btn btn-sm btn-secondary" href="<?php echo esc_url( $wpsy_premium_copy . '/?help' ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Need Urgent Help? (Opens in a new window)', 'wp-shopify' ); ?>"><?php esc_html_e( 'Need Urgent Help?', 'wp-shopify' ); ?> &nbsp;<i class="fas fa-phone"></i></a></li>
			<li><a class="btn btn-sm btn-secondary" style="background-color:red;" href="https://androidbubbles.wordpress.com/2024/04/03/wp-shopify-storefront-api-credentials/" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Video Tutorial (Opens in a new window)', 'wp-shopify' ); ?>"><?php esc_html_e( 'Video Tutorial', 'wp-shopify' ); ?> &nbsp;<i class="fab fa-youtube"></i></a></li>
			<li><iframe width="560" height="315" src="https://www.youtube.com/embed/grnbmlLhkJE" title="<?php esc_attr_e( 'Video Tutorial', 'wp-shopify' ); ?>" loading="lazy" frameborder="0" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></li>
		</ul>
	</div>
</div>

</div>
