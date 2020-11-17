===  bKash WordPress Payment ===
Contributors: mlimon, themepaw
Tags: bkash, gateway, woocommerce, wpcf7, BDT, bangladesh
Requires at least: 5.0
Tested up to: 5.3
Requires PHP: 5.4
Stable tag: trunk
License: GPLv2 Or Later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

bKash Payment Solution for WordPress / WooCommerce

== Description ==

WPbKash is a complete solution for bKash merchant payment for WooCommerce or WordPress based sites. This plugin allows you to take payment from WooCommerce checkout and even with Contact From 7! Whether you sell a product, service or take submission with payment.

= Using the Plugin For WooCommerce =
* Activate the plugin from plugin list if not activated yet. (Note: You need WooCommerce installed if you need to use this for WooCommerce checkout.)
* Now you will find a Menu in your Dashboard called “WPbKash”, Go to WPbKash settings and place your required App Key, App Secret, Username, and Password
* Go to WooCommerce Settings > Payments and activate “bKash Payment” from the list.

[youtube https://www.youtube.com/watch?v=XTlpT0GTA88]

= Using the Plugin For Contact Form 7 =
* Activate the plugin from plugin list if not activated yet. (Note: You need Contact Form 7 installed)
* Go to your form list and edit the form. Select bKash from the Tab menu, Check Enable bKash box, set the amount for payment, set the customer email tag from the contact form tag list to send payment URL.
* You can set Payment email and Confirmation email body texts/HTML content too.

[youtube https://www.youtube.com/watch?v=Cmb0AEIFm7Y]

= Installation =

This section describes how to install the plugin and get it working.
1. Go to "Add new" from plugins menu in your dashboard.
2. Search "wpbkash" from the search section.
3. Click "Install Now" then "Activate" to make this plugin install and activate properly.
or
1. Upload the whole contents of the folder `wpbkash` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress dashboard
3. Enjoy using it :)


= Contribute =

[Fork in Github](https://github.com/mlbd/wpbkash)


== Screenshots ==

1. Woocommerce bKash Payment Gatway settings
2. WPbkash settings
3. bKash Payment Gatway option
4. bKash Payment Gatway iframe
5. WPbKash entries list
6. Entry details
7. Entry edit
8. Order recieved page payment button
9. Pay for order
10.Contact form 7
11.Contact form 7 edit bkash settings
12.Contact form 7 bkash settings error display


== Frequently Asked Questions ==

= What is bKash? =

bKash is a mobile financial service in Bangladesh operating under the authority of Bangladesh Bank as a subsidiary of BRAC Bank Limited.

= Can I use this with WooCommerce? =

Yes, you can use this for WooCommece Checkout to take payment online.

= What else does it work with? =

WPbKash Works with Contact form 7 too. You can take Payment with Contact form 7 Submission too and a Payment entry will list on WPbKash Menu.

= Can I setup with my personal bKash account? =

No, This plugin requires Merchant account and Merchant details.

= What bKash credentials are needed? =

You will need bKash app_key app_secret username and password, .

= How do I get credentials? =

You may contact with bKash support 16247 Or contact with your <strong>[bKash Account Manager](http://www.bkash.com/support/contact-us)</strong>.

= Does this support both production mode and sandbox mode for testing? =

Yes it does - just use "Enable Test Mode" checkbox to enable disable sandbox. If you are unchecked "Enable Test Mode" then your are in live/production mode and live mode requires your original bkash marchang app_key, app_secret, username and password.

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the Plugin Forum.


== Changelog ==

= 0.1.2 =
* Tweak: Displaying error message by using alertify.just
* Tweak: From after recieved payment order will be start process, So, if payment cancel or getting any error then cart won't be emty any more.
* Fix: security nonce cache issue.
* Tweak: Added `invoice` column to payment entry post


= 0.1.1 =
* Displaying error message for unsuccessful payment.

= 0.1 =
* Initial release


== Upgrade Notice ==

Nothing here