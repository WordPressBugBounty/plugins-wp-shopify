<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	global $wpsy_data, $wpsy_pro, $wpsy_premium_copy;
	$wpsy_db_data = get_option('wpsy_db_data');
	$wpsy_db_data = (is_array($wpsy_db_data)?$wpsy_db_data:array());
	$wpsy_db_data = array_map('esc_attr', $wpsy_db_data);
	$site_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', get_bloginfo('name')));

	
	$store_data = wpsy_graphql_central(array('query'=>'shop'), true);

	
	$shop_name = (!empty($store_data)?'<span title="'.$store_data->shop->description.'"><i class="fas fa-crown"></i> '.$store_data->shop->name.'</span>':'');
	
?>

<div class="wrap wpsy_settings_div">

        

<h2><?php echo $wpsy_data['Name']; ?> <?php echo '('.$wpsy_data['Version'].($wpsy_pro?') Pro':')'); ?> - <?php _e('Settings','wp-shopify'); ?> <?php echo $shop_name; ?></h2> 


    <h2 class="nav-tab-wrapper">

        <a class="nav-tab nav-tab-active" data-tab="config-settings"><?php _e("Configuration",'wp-shopify'); ?> <i class="fas fa-tools"></i></a>
        
        <a class="nav-tab" data-tab="shortcodes"><?php _e("Shortcodes",'wp-shopify'); ?> <i class="fas fa-code"></i></a>

        <a class="nav-tab" style="float:right" data-tab="help"><?php _e("Help",'wp-shopify'); ?> <i class="fas fa-question-circle"></i></a>

    </h2>
    
    <div class="nav-tab-content container-fluid tab-config-settings" data-content="config-settings" style="padding-top:20px;">
            
    <form action="options.php" method="post" class="ignore">



    <?php
			settings_fields('wpsy_settings_page');
			//do_settings_sections('wpsy_settings_page');
			shoppd_settings_section_callback($wpsy_db_data);
?>
<table class="form-table" role="presentation"><tbody>

	<tr class="wpsy_fields"><th scope="row" class="connection-bar">
    <i class="fas fa-sync-alt"></i><?php _e("Connect and Sync",'wp-shopify'); ?> <?php if(!$shop_name): ?><span class="connection-status-red"><i class="fas fa-circle"></i><?php _e("Disconnected",'wp-shopify'); ?></span><?php endif; ?><?php if($shop_name): ?><span class="connection-status-green"><i class="fas fa-circle"></i><?php _e("Connected",'wp-shopify'); ?></span><?php endif; ?>
    </th></tr>
    

	<tr class="wpsy_fields"><th scope="row" style="padding-bottom:0;">
    <hr />
    </th></tr>
    
	<tr class="wpsy_fields"><th scope="row" style="font-weight:normal;">
	<?php _e("Enter your Shopify private app API keys below.",'wp-shopify'); ?> <a style="color:#4D83A8;" href="" class="wpsy-need-help"><?php _e("Nee help finding these?",'wp-shopify'); ?></a>
    </th></tr>
	
    <tr class="wpsy_fields">
    <th scope="row"><?php _e("API Key",'wp-shopify'); ?> <i style="color:#06C;" class="fas fa-key"></i></th>
    </tr><tr class="wpsy_fields">
    <th>		<input type="text" name="wpsy_db_data[wpsy_api_key]" value="<?php echo (isset($wpsy_db_data['wpsy_api_key'])?$wpsy_db_data['wpsy_api_key']:''); ?>" size="80" class="wpsy_fields"> 
    </th>
    </tr>
    
    
    <tr class="wpsy_fields"><th scope="row"><?php _e("API Password",'wp-shopify'); ?> <i style="color:#F90;" class="fas fa-fingerprint"></i></th>
    </tr><tr class="wpsy_fields">
    <th>		<input type="password" name="wpsy_db_data[wpsy_password]" value="<?php echo (isset($wpsy_db_data['wpsy_password'])?$wpsy_db_data['wpsy_password']:''); ?>" size="80" class="wpsy_fields"> 
    </th>       
    </tr>
    
    
    
    <tr class="wpsy_fields"><th scope="row"><?php _e("Shared Secret",'wp-shopify'); ?> <i style="color:#8A8B8C;" i="" class="fas fa-share-alt"></i></th>
    </tr><tr class="wpsy_fields">
    <th>		<input type="text" name="wpsy_db_data[wpsy_storefront_shared]" value="<?php echo (isset($wpsy_db_data['wpsy_storefront_shared'])?$wpsy_db_data['wpsy_storefront_shared']:''); ?>" size="80" class="wpsy_fields"> 
    </th>
    </tr>
    
    <tr class="wpsy_fields"><th scope="row"><?php _e("Storefront Access Token",'wp-shopify'); ?> <i style="color:#333;" class="fas fa-user-lock"></i></th>
    </tr><tr class="wpsy_fields">
    <th>		<input type="password" name="wpsy_db_data[wpsy_storefront_token]" value="<?php echo (isset($wpsy_db_data['wpsy_storefront_token'])?$wpsy_db_data['wpsy_storefront_token']:''); ?>" size="80" class="wpsy_fields"> 
    </th>
    </tr>
    
    <tr class="wpsy_fields"><th scope="row"><?php _e("Domain",'wp-shopify'); ?> <i style="color:#96BF48;" class="fab fa-shopify"></i></th>
    </tr><tr class="wpsy_fields">
    <th>		<input type="text" name="wpsy_db_data[wpsy_url]" value="<?php echo (isset($wpsy_db_data['wpsy_url'])?$wpsy_db_data['wpsy_url']:''); ?>" size="80" class="wpsy_fields" placeholder="<?php _e("shop.myshpify.com",'wp-shopify'); ?>" /> 
    </th></tr>
    
    <tr class="wpsy_fields"><th scope="row">
    	<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e("Connect your Shopify store",'wp-shopify'); ?>" />
	</th></tr>      
</tbody></table>
<?php			
			//submit_button();
    ?>
        
    </form>
<?php if(!empty($wpsy_db_data)): ?>
<ul class="wpsy-useful-links">
<li><a href="https://<?php echo $wpsy_db_data['wpsy_url']; ?>" target="_blank" aria-label="<?php _e('Go to Store', 'wp-shopify'); ?> (Opens in a new window)"><i style="color:#96BF48;" class="fab fa-shopify"></i> <?php _e("Go to Store",'wp-shopify'); ?></a></li>
<li><a href="https://accounts.shopify.com/store-login" target="_blank" aria-label="<?php _e('Store Login', 'wp-shopify'); ?> (Opens in a new window)"><i style="color:#F93;" class="fas fa-user-lock"></i> <?php _e("Store Login",'wp-shopify'); ?></a></li>
<li><a href="https://<?php echo $wpsy_db_data['wpsy_url']; ?>/admin/apps/development" target="_blank" aria-label="<?php _e('Application / API Credentials', 'wp-shopify'); ?> (Opens in a new window)"><i style="color:#369;" class="fas fa-rocket"></i> <?php _e("Application / API Credentials",'wp-shopify'); ?></a></li>
<li><a href="https://<?php echo $wpsy_db_data['wpsy_url']; ?>/admin/collections" target="_blank" aria-label="<?php _e('Create Collections', 'wp-shopify'); ?> (Opens in a new window)"><i style="color:#C3C;" class="fas fa-boxes"></i> <?php _e("Create Collections",'wp-shopify'); ?></a></li>

</ul>

<?php endif; ?>
    

            
    </div>      
    
   	 <div class="nav-tab-content container-fluid hides tab-shortcodes" data-content="shortcodes">
    
        <div class="row mt-3">
         

            <ol class="wp-shopify-shortcodes">
            <li>[wp-shopify]</li>
            <li>[wp-shopify-product]
            <br /><br />
			<small><strong><?php echo __('OR'); ?></strong> <br />[wp-shopify-product id="<?php echo __('some product ID', 'wp-shopify'); ?>" button_type="default"]</small>
            <br /><br />
			<small><strong><?php echo __('OR'); ?></strong> <br />[wp-shopify-product id="<?php echo __('some product ID', 'wp-shopify'); ?>" <i class="green">button_type="js"</i> <i class="red">template="bello-sole"</i> continue-shopping-page="<?php echo __('some product ID', 'wp-shopify'); ?>"] <b class="red">(<?php echo __('Premium Version', 'wp-shopify'); ?>)</b><br />
<br />
<ol>
<li>add_action('wp-shopify-product-after-description', 'callback_func'); //FOR EXTRA HTML BASED CONTENT</li>
<li>add_filter('wp-shopify-buy-button-link', '<?php echo $site_name; ?>_shopify_buy_btn_callback', 10, 4);</li>
</ol>
</small>

            <br /><br /></li>
            <li>[wp-shopify type="collection" id="141421936749" limit="4" searchfilter="yes" url-type="default|shopify" thumb-size="default|300"]</li>
            <li>[wp-shopify type="products" limit="100"]</li>
            <li>[wp-shopify-continue-shopping]</li>
            </ol>

		</div>    
	
    </div>        
    
    <div class="nav-tab-content container-fluid hides tab-help" data-content="help">
    
        <div class="row mt-3">
        
            <ul class="position-relative">
                <li><a class="btn btn-sm btn-info" href="https://wordpress.org/support/plugin/wp-shopify/" target="_blank" aria-label="<?php _e('Open a Ticket on Support Forums', 'wp-shopify'); ?> (Opens in a new window)"><?php _e('Open a Ticket on Support Forums', 'wp-shopify'); ?> &nbsp;<i class="fas fa-tag"></i></a></li>
                <li><a class="btn btn-sm btn-warning" href="http://demo.androidbubble.com/contact/" target="_blank" aria-label="<?php _e('Contact Developer', 'wp-shopify'); ?> (Opens in a new window)"><?php _e('Contact Developer', 'wp-shopify'); ?> &nbsp;<i class="fas fa-headset"></i></a></li>
                <li><a class="btn btn-sm btn-secondary" href="<?php echo $wpsy_premium_copy; ?>/?help" target="_blank" aria-label="<?php _e('Need Urgent Help?', 'wp-shopify'); ?> (Opens in a new window)"><?php _e('Need Urgent Help?', 'wp-shopify'); ?> &nbsp;<i class="fas fa-phone"></i></i></a></li>
                <li><a class="btn btn-sm btn-secondary" style="background-color:red;" href=" https://androidbubbles.wordpress.com/2024/04/03/wp-shopify-storefront-api-credentials/" target="_blank" aria-label="<?php _e('Video Tutorial', 'wp-shopify'); ?> (Opens in a new window)"><?php _e('Video Tutorial', 'wp-shopify'); ?> &nbsp;<i class="fab fa-youtube"></i></i></a></li>
               
                <li><iframe width="560" height="315" src="https://www.youtube.com/embed/grnbmlLhkJE?t=<?php date('d'); ?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></li>
            </ul>                
        </div>
    
    </div>  

</div>