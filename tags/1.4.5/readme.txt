=== Plugin Name ===
Contributors: alekv
Donate link: https://wolfundbaer.ch/donations/
Tags: woocommerce, google, adwords, dynamic remarketing, dynamic retargeting, dynamic, remarketing, retargeting, woocommerce dynamic remarketing, woocommerce dynamic retargeting, adwords remarketing, adwords retargeting, adwords dynamic retargeting, adwords dynamic remarketing
Requires at least: 3.1
Tested up to: 4.9.1
Stable tag: 1.4.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin integrates the Google AdWords Dynamic Remarketing Tracking pixel with customized ecommerce variables in a WooCommerce shop.

== Description ==

Do you have a WooCommerce shop and want to run dynamic remarketing campaigns with Google AdWords? This plugin will insert the customized remarketing pixel on all your shop pages. Google AdWords will then be able to collect customer behaviour data (product viewers, buyers, order value, cart abandoners, etc). Based on this data you will be able to run targeted remarketing campaigns.

<strong>Requirements</strong>

* WooCommerce
* WooCommerce Google Product Feed plugin or something similar to upload the products to the Google Merchant Center
* Google Merchant Center Account with all products uploaded
* AdWords account with a configured remarketing tag

<strong>Highlights of this plugin</strong>

* Very easy to install
* Very accurate. Several methods have been build in to avoid tracking of shop managers, deduplication of purchases, etc.

<strong>Installation support</strong>

Installing the plugin is pretty simple. Just activate it and enter the conversion ID and if necessary the product prefix.

If you also need to to set up the Google Merchant Center first the entire setup becomes more complex. If you would like us to do the setup for you please contact us for an offer: support@wolfundbaer.ch

<strong>Similar plugins</strong>

If you like this plugin, have a look at our other AdWords related plugin: [WooCommerce AdWords Conversion Tracking](https://wordpress.org/plugins/woocommerce-google-adwords-conversion-tracking-tag/)

<strong>Support Info</strong>

We will only support installations which run the most current versions of WordPress and WooCommerce.

<strong>More information</strong>

Please find more information about AdWords remarketing on following pages:

[Dynamic Display Ads](http://www.google.com/ads/innovations/dynamicdisplayads.html)<br>
[Dynamic Remarketing](https://www.thinkwithgoogle.com/products/dynamic-remarketing.html)<br>

<strong>Supported languages</strong>

* English
* German
* Serbian

== Installation ==

1. Upload the WGDR plugin directory into your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Get the AdWords conversion ID and the conversion label. You will find both values in the AdWords remarketing tag. [Get the conversion ID and conversion label](https://support.google.com/adwords/answer/2476688)
4. In the WordpPress admin panel go to WooCommerce and then into the 'AdWords Dynamic Retargeting' menu. Please enter the conversion ID and the conversion label into their respective fields.
5. Also add the Google Merchant Center prefix which is "woocommerce_gpf_" if you use the Google Product Feed plugin to upload your products the the Google Merchant Center.
6. Use the Google Tag Assistant browser plugin to test the tracking code. Bear in mind that the code is only visible if you are not logged in as admin or shop manager. You will have to log out first.

== Frequently Asked Questions ==

= Why does AdWords report an error? =

Give AdWords time to pick up the code. It can take up to 48 hours.

= How do I check if the plugin is working properly? =

Download the Google Tag Assistant browser plugin. It is a powerful tool to validate all Google tags on your pages. Bear in mind that validating the Merchant Center Feed only works on product pages.

= The plugin messes up my theme. What is happening? =

It has been reported in several occasions that minifaction plugins, such as Autoptimze, mess up the Javascript code which lead to errors in the theme. The Javascript code for the tracking needs to be placed exactly as Google requires it. To solve this issue turn off the minification plugin.

= Does the plugin support variations? =

The short answer is: Don’t worry about variations. Even if the visitor is only being served the main product he has visited (not the variations), the dynamic banner will lead to a much higher click-through-rates already.

Also look at this answer in the Google AdWords Community (coming from a Google developer): [Dynamic Remarketing Tag and Variations](https://www.en.advertisercommunity.com/t5/Advanced-Features/Dynamic-Remarketing-Tag-amp-Multiple-Product-Options/td-p/336966#)

The long answer is: The complexity of a solution to implement variations is quite high in the current configuration how variations are being displayed by WooCommerce. If we have one layer of variations (eg. only color) that’s not a big thing. But when someone decides to use two or more attributes (eg. color, size, etc.) then it really becomes a lot more difficult to implement. The benefit of such a solution is rather slim.

= I am getting following error: "We haven’t detected the Google Analytics remarketing functionality on your website." What can I do? =

This plugin doesn't support Google Analytics Remarketing, only Google AdWords Remarketing (as the plugin name says).

= I am getting following error: "There are problems with some of your custom parameters for Retail." What can I do? =

Give AdWords time to pick up the code. It can take up to 48 hours.

= I am getting following error: "Only 20 of 50 page visits that have passed an ID (or 40%) match IDs in your Merchant Center feed." What can I do? =

It indicates that not all products have been uploaded to the Google Merchant Center. As long as you don't upload all of them you will see this error.

Also it could be that you haven't waited the 48 hours time period until AdWords has processed everything properly.

= Where can I report a bug or suggest improvements? =

Please report your bugs and support requests in the support forum for this plugin: [Plugin Support Forum](http://wordpress.org/support/plugin/woocommerce-google-dynamic-retargeting-tag)

We will need some data to be able to help you.

* Website address
* WordPress version
* WooCommerce version
* WooCommerce theme and version of the theme
* The AdWords remarketing tags conversion ID and conversion label

(Most of these information is publicly viewable on your webpage. If you still want to keep it private don't hesitate to send us a support request to support@wolfundbaer.ch)

= Why does AdWords report that the ecomm_prodid are not found? =

* After installation wait a few days. It can take that long until all is processed correctly.
* You need the prefix only if you use the WooThemes Google Product Feed plugin.
* Check if you really have loaded all products into the feed and into the Google Merchant Center.



== Screenshots ==
1. Simple configuration
2. Validate the configuration of the plugin with the Google Tag Assistant

== Changelog ==

= 1.4.5 =
* Tweak: Hiding the white space at the bottom of some themes by adding a new css sheet
= 1.4.4 =
* Tweak: Catch warning if a product has no categories set
* Tweak: Catch error and log it in debug.log if is_product() returns true, but wc_get_product() can't retrieve a product object
= 1.4.3 =
* Tweak: The campaign URL parameters have been removed
= 1.4.2 =
* Tweak: json_encode conversion_id output to prevent JavaScript errors in edge cases
= 1.4.1 =
* Tweak: Replacing deprecated $order->id with $order->get_order_number()
= 1.4 =
* Tweak: Code cleanup
= 1.3.7 =
* New: Admin notice asking to leave a rating
* Tweak: Better options update routine
* Tweak: Switching order_total to order_subtotal (no taxes and shipping cost)
= 1.3.6 =
* Tweak: Code cleanup
= 1.3.5 =
* Fix: Avoid 'undefined index' for product_identifier
= 1.3.4 =
* New: Choose product ID or SKU as product identifier
= 1.3.3 =
* Tweak: Refurbishment of the settings page
= 1.3.2 =
* Fix: Version check with new function logic to make it work with older PHP versions
= 1.3 =
* Tweak: Options table upgrade
= 1.2.1 =
* New: Uninstall routine
= 1.2 =
* New: Exclusion for the Autoptimize plugin
= 1.1.2 =
* New: Added filter capability for products and categories
= 1.1.1 =
* Tweak: Code cleanup
= 1.0.9 =
* Tweak: Code cleanup
* Tweak: To avoid overreporting only insert the retargeting code for visitors, not shop managers and admins
= 1.0.8 =
* Tweak: Encoding all JavaScript variables with json_encode
= 1.0.7 =
* Tweak: Switching single pixel function from transient to post meta
= 1.0.6 =
* Fix: Adding session handling to avoid duplications
= 1.0.5 =
* Fix: Implement different logic to exclude failed orders as the old one is too restrictive
= 1.0.4 =
* Fix: Exclude orders where the payment has failed
= 1.0.3 =
* Fix: Minor fix to the code to avoid an invalid argument error which happens in rare cases.
= 1.0.2 =
* Update: New translation into Serbian
* Update: Change of plugin name
* New: Plugin banner and icon
= 1.0.1 =
* Update: Minor update to the code to make it cleaner and easier to read
= 1.0 =
* New: Internationalization (German)
* New: Category support
= 0.1.4 =
* Update: Increase plugin security
* Update: Moved the settings to the submenu of WooCommerce
* Update: Improved DB handling of orders on the thankyou page
* Update: Code cleanup
* Update: Removed the conversion label. It is not necessary.
= 0.1.3 =
* Added settings field to the plugin page.
= 0.1.2 =
* The code reflects now that the conversion_label field is optional.
= 0.1.1 =
* Changed the woo_foot hook to wp_footer to avoid problems with some themes. This should be more compatible with most themes as long as they use the wp_footer hook.
= 0.1 =
* This is the initial release of the plugin.
