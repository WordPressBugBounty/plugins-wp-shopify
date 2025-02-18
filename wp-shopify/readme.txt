=== WP Shopify ===
Contributors: fahadmahmood, invoicepress
Tags: Shopify
Requires at least: 4.3 
Tested up to: 6.8
Stable tag: 1.5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display products from your Shopify store
on your WordPress blog using shortcodes.

== Description ==

Display Shopify products on your WordPress blog.

How it works?

A) Set up all the apis as directed by the App
B) Set up two new pages on your WordPress website

Page #1 Set the permalink to shopify (or products, shop, catalogue etc.) and add code [wp-shopify type="products" limit="100" url-type="default"]

Page #2 Set the permalink to product (note there is no "s" at the end of the product in the url, the slug/permalink should be with exactly "product") and insert code [wp-shopify-product] (this is where product redirect link to shopify store will work on your WordPress website)

C) Modify the layout of your WordPress website pages with the CSS

== Installation ==

Note: This is a two-part install; you have to do some configuration on your Shopify store admin, then you must install code on your WordPress site. 

In your Shopify admin, do the following: 

1. Login to the Shopify 2 Admin Panel.
1. Go to Apps, then click the "Manage private apps" link at the bottom of the page. 
1. Click the "Generate API credentials" button.  
1. Set the Private app name to "Product Display" and be sure you have Read access to Products, variants and collections.  
1. Click the save button, and note your Private app API key and Password.

Install the WordPress part of this mod as usual (using the Install button 
on the mod page on WordPress.org).  The follow these steps: 

1. In your WordPress admin, do the following: 
- In Plugins->Installed Plugins, click the "Activate" link under WP Shopify.
- In Settings->WP Shopify, set your Shopify URL, API Key and password.  

To show a specific product on your blog, use the shortcode 
[wp-shopify-product] with parameter "id" as a self closing tag.  
So showing product 617935406 would be done as follows: 

[wp-shopify-product id="617935406"]

The id is shown in the URL when you edit a product in your admin.

== Frequently Asked Questions ==

=How should I know that my store is configured properly?=

To configure your store properly, you will need Storefront API access. To achieve this, go to Apps and sales channels > develop apps > create an app and configure storefront API integration. Make sure to check all the access scopes (checkboxes) and save. You will get the Storefront API access token to configure your store on the plugin settings page.
[youtube http://www.youtube.com/watch?v=3Q7P1D1owwc]

=Video tutorial showing the credentials and the crown icon=

Enter all the credentials correctly in the required fields on the plugin settings page, a crown icon in the top right will appear. And the go to store button will be active as well as redirecting you to your Shopify store. 
[youtube http://www.youtube.com/watch?v=YKlkpEKK1Sk]

=Is there any step-by-step guide to install and configure this plugin?=

Configure this plugin with Shopify Store by following the steps below:

**&#128073; 1. Create a store or if you have an existing one, find the URL**
[screenshot-9.png](https://ps.w.org/wp-shopify/assets/screenshot-9.png)

**&#128073; 2. Create an APP and Install that APP on your store**
[youtube http://www.youtube.com/watch?v=3Q7P1D1owwc]

**&#128073; 3. Configure the storefront within your store APP**
[screenshot-10.png](https://ps.w.org/wp-shopify/assets/screenshot-10.png)

**&#128073; 4. Get the app storefront API access token, API key and API secret key to insert in the required input fields on the plugin settings page. Save changes and check the outcome.**
[youtube http://www.youtube.com/watch?v=YKlkpEKK1Sk]
[screenshot-8.png](https://ps.w.org/wp-shopify/assets/screenshot-8.png)


== Screenshots ==

1. Single Product
2. Shop/Collection
3. Settings
4. Product
5. Add to cart
6. The Crown icon appears after configuring the store successfully.
7. Storefront API access token/creating an app in Shopify store.
8. Fill the required fields to configure the Shopify store with the WP Shopify plugin.
9. Store URL
10. App Development > WP Shopify App
11. button_type="js" implementation


== Changelog ==
= 1.5.3 =
* New: Clear cart and a trash icon added to remove the items.  [18/02/2025]
= 1.5.2 =
* New: button_type="js" provided with the normal theme as well.  [15/02/2025]
= 1.5.1 =
* Fix: Notice: Function _load_textdomain_just_in_time was called incorrectly.  [12/12/2024]
= 1.5.0 =
* Fix: Fatal error: Uncaught TypeError: array_map(): Argument #2 ($array) must be of type array, bool given. [18/11/2024]
= 1.4.9 =
* New: Update settings page interface. [06/11/2024]
= 1.4.7 =
* Fix: [WP Shopify] Warning: Undefined variable $o. [Thanks to @hyuman][16/05/2024]
= 1.4.6 =
* Video tutorial link added for the storefront API keys. [Thanks to Mary Mueller][04/04/2024]
= 1.4.5 =
* Concatenation related issue resolved. [Thanks to Niko Moustoukas][08/03/2024]
* Fix: https://wordpress.org/support/topic/created-api-with-keys-etc-nothing-but-errors/ [Thanks to @kindred][01/04/2024]
= 1.4.4 =
* Updated description with the helpful guidelines. [Thanks to @kira01][28/01/2024]
= 1.4.3 =
* Translatable strings. [Thanks to @bichareh][11/08/2023]
= 1.4.2 =
* Translatable strings. 03/08/2023 [Thanks to @bichareh]
= 1.4.1 =
* Accessibility feature revised. 10/03/2023 [Thanks to JAMES MENENDEZ / FKQ]
= 1.4.0 =
* Accessibility feature added. 01/03/2023 [Thanks to JAMES MENENDEZ / FKQ]
= 1.3.9 =
* Video tutorial removed from the support tab. 06/02/2023 [Thanks to Joshua Stokes & Andrew Robbins]
= 1.3.8 =
* Back to shop link added through the shortcode on single product page. 20/12/2022 [Thanks to KC Clark]
= 1.3.7 =
* JS based add to cart button included in the shortcodes as a new attribute "button_type". 14/10/2022 [Thanks to Roman von Contzen]
= 1.3.6 =
* Search filter attribute added to the collection based shortcode. 22/06/2022 [Thanks to KC Clark]
* url-type and thumb-size attribues added to the collections shortcode. 14/09/2022 [Thanks to James Menedez]
= 1.3.5 =
* Settings page css tweaks. 19/06/2022 [Thanks to KC Clark]
= 1.3.4 =
* Capri theme added in premium version. 14/05/2022 [Thanks to Dylan Ence]
= 1.3.3 =
* GraphQL implemented with access token. 17/04/2022 [Thanks to Maria Abad-Guillen]
= 1.3.2 =
* Stylesheet path fixed for shotcodes function. 14/04/2022 [Thanks to kamasi]
= 1.3.1 =
* Updated version with help tab.
= 1.3 =
* Updated version for WordPress.
= 1.2 =
* Fatal error fixed. [Thanks to @adabob and @andrewmrobbins]
= 1.1 =
* Shortcode improved with two more attributes. [Thanks to lakewebworks]
= 1.0.1 =
* Languages added. [Thanks to Abu Usman]
= 1.0.0 =
First version

== Upgrade Notice ==
= 1.5.3 =
New: Clear cart and a trash icon added to remove the items.
= 1.5.2 =
New: button_type="js" provided with the normal theme as well.
= 1.5.1 =
Fix: Notice: Function _load_textdomain_just_in_time was called incorrectly. 
= 1.5.0 =
Fix: Fatal error: Uncaught TypeError: array_map(): Argument #2 ($array) must be of type array, bool given.
= 1.4.9 =
New: Update settings page interface.
= 1.4.7 =
Fix: [WP Shopify] Warning: Undefined variable $o.
= 1.4.6 =
Video tutorial link added for the storefront API keys.
= 1.4.5 =
Concatenation related issue resolved.
= 1.4.4 =
Updated description with the helpful guidelines.
= 1.4.3 =
Translatable strings.
= 1.4.2 =
Translatable strings.
= 1.4.1 =
Accessibility feature revised.
= 1.4.0 =
Accessibility feature added.
= 1.3.9 =
Video tutorial removed from the support tab.
= 1.3.8 =
Back to shop link added through the shortcode on single product page.
= 1.3.7 =
JS based add to cart button included in the shortcodes as a new attribute "button_type".
= 1.3.6 =
Search filter attribute added to the collection based shortcode.
= 1.3.5 =
Settings page css tweaks.
= 1.3.4 =
Capri theme added in premium version.
= 1.3.3 =
GraphQL implemented with access token.
= 1.3.2 =
Stylesheet path fixed for shotcodes function.
= 1.3.1 =
Updated version with help tab.
= 1.3 =
Updated version for WordPress.
= 1.2 =
Fatal error fixed.
= 1.1 =
Shortcode improved with two more attributes.
= 1.0.1 =
Languages added.
= 1.0.0 =
First version

