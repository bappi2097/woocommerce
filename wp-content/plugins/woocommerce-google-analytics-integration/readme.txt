=== WooCommerce Google Analytics Integration ===
Contributors: woocommerce, automattic, claudiosanches, bor0, royho, laurendavissmith001, c-shultz
Tags: woocommerce, google analytics
Requires at least: 3.9
Tested up to: 5.5
Stable tag: 1.4.24
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Provides integration between Google Analytics and WooCommerce.

== Description ==

This plugin provides the integration between Google Analytics and the WooCommerce plugin. You can link a referral to a purchase and add transaction information to your Google Analytics data. It also supports the new Universal Analytics, eCommerce, and enhanced eCommerce event tracking.

Starting WooCommerce 2.1, this integration will no longer be part of WooCommerce and will only be available by using this plugin.

Contributions are welcome via the [GitHub repository](https://github.com/woothemes/woocommerce-google-analytics-integration).

== Installation ==

1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation’s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.
4. Don't forget to enable e-commerce tracking in your Google Analytics account: https://support.google.com/analytics/answer/1009612?hl=en

Or use the automatic installation wizard through your admin panel, just search for this plugins name.

== Frequently Asked Questions ==

= Where can I find the setting for this plugin? =

This plugin will add the settings to the Integration tab, to be found in the WooCommerce > Settings menu.

= I don't see the code on my site. Where is it? =

We purposefully don't track admin visits to the site. Log out of the site (or open a Google Chrome Incognito window) and check if the site is there for non-admins.

Also please make sure your Google Analytics ID under WooCommerce -> Settings -> Integrations.

= My code is there. Why is it still not tracking sales?  =

Duplicate Google Analytics code causes a conflict in tracking. Remove any other Google Analytics plugin or code from your site to avoid duplication and conflicts in tracking.

= Can I install it already? =

Starting the WooCommerce 2.1 release, the Google Analytics integration for WooCommerce is no longer part of the WooCommerce plugin.

Until you've updated to WooCommerce 2.1, this plugin puts itself in some sort of hibernate mode.

You can leave this plugin activated and it will seamlessly take over the integration that once was in the WooCommerce plugin, once you update to the next version.

= My settings are not saving! =

Do you have SUHOSIN installed/active on your server? If so, the default index length is 64 and some settings on this plugin requires longer lengths. Try setting your SUHOSIN configuration's "max_array_index_length" to "100" and test again.

= My national data privacy laws require that I offer an opt-out for users, how can I do this? =

Include the following html code snippet within the page where you want to have the opt-out, e.g. the your Imprint our Data Privacy page:

https://gist.github.com/claudiosanches/b12d15b245be21c92ebc

Exact wording depends on the national data privacy laws and should be adjusted.

== Screenshots ==

1. Google Analytics Integration Settings.

== Changelog ==

= 1.4.24 - 2020-10-12 =
* Tweak - WC 4.5 compatibility.

= 1.4.23 - 2020-08-19 =
* Fix   - Prevent transaction from being tracked a second time when page is reloaded locally or from cache.
* Tweak - WordPress 5.5 compatibility.

= 1.4.22 - 2020-06-05 =
* Tweak - WC 4.2 compatibility.

= 1.4.21 - 2020-05-04 =
* Tweak - WC 4.1 compatibility.

= 1.4.20 - 2020-03-29 =
* Fix - Change wc_goole_analytics_send_pageview fiter name to wc_google_analytics_send_pageview.

== Upgrade Notice ==
= 1.4.0 =
Adds support for enhanced eCommerce (tracking full store process from view to order)
