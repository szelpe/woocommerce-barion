=== Barion Payment Gateway for WooCommerce ===
Contributors: szelpe
Tags: woocommerce, barion, gateway, payment
Requires at least: 4.0
Tested up to: 6.1
WC requires at least: 3.0.0
WC tested up to: 7.0.1
Requires PHP: 5.6
Stable tag: 3.5.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin allows your customers to pay via Barion Smart Gateway in your WooCommerce online store.

== Description ==

This plugin allows your customers to pay via [Barion Smart Gateway](https://www.barion.com/) in your WooCommerce online store.

= Features =

- Adds Barion as a payment option to the WooCommerce checkout page
- Redirects the user to the Barion payment page after checkout
- Handles the callback from Barion after payment
  - sets the order status to "processing", "completed" or "failed" respectively
- Refund payments via Barion

= Feedback =

I'd be happy to hear your feedback! Feel free to contact me at szelpeter@szelpeter.hu

= Contribution =

You're welcome to contribute to this open source plugin by creating pull-requests on [Github](https://github.com/szelpe/woocommerce-barion). To do this, you need to fork the repository, implement the changes and push them to your fork. After that you can create a pull request to merge changes from your fork the main repository.

= Bugs =

[Please report bugs as Github issues.](https://github.com/szelpe/woocommerce-barion/issues)

Barion and the Barion logo are trademarks or registered trademarks of Sense/Net Inc.

WooCommerce and the WooCommerce logo are trademarks or registered trademarks of Bubblestorm Management (Proprietary) Limited trading as WooThemes.

== Installation ==

= Minimum Requirements =

* WooCommerce 3.0 or later
* WordPress 4.0 or later

1. The recommended way to install the plugin is through the "Plugins" menu in WordPress
  - Navigate to Plugins > Add New > Search for "barion", you should already see this plugin
  - Hit "Install Now", then enable the plugin
1. Click on the menu item "WooCommerce" then select the "Checkout" tab
1. Click on the new submenu item named "Barion"
1. On this page you should set the POSKey of the shop and your Barion email address
1. Should you want to use the test envorinment, select it from the Barion Environment dropdown. You'll need to create a shop on the [Barion test page](https://test.barion.com).
1. Enable the payment method if you're ready to use Barion

== Screenshots ==

1. Settings on the WooCommerce Settings > Checkout page
2. Barion as a payment method

== Privacy Policy ==

If you choose to sign up to the newsletter, the following data will be collected: admin email address, the current user's name and email address, the URL of your blog, the locale of the blog and the IP address of the currently logged in user. We use the collected data to send newsletters occasionally.
The collected data is deleted upon request and will not be shared.

== Frequently Asked Questions ==

= How to disable the Barion Pixel for certain pages? =

You can use the `woocommerce_barion_disable_tracking` hook, like this:

```php
add_filter('woocommerce_barion_disable_tracking', 'disable_barion_pixel_for_editor');

function disable_barion_pixel_for_editor() {
	return current_user_can('edit_posts');
}
```

== Changelog ==

= 3.2.0 =

- Compatible with WooCommerce 4
- Added filter for disabling Barion Pixel
- Fixed PHP Notice for missing property on the request class
- Fixed PHP Notice in case 'environment' setting is not set

= 3.0.2 =

- Fixed the case when the shipping/billing address or region is empty

= 3.0.0 =

- Added support for 3D Secure payment
- Added basic support for Barion Pixel
- Updated shipping address to match the new model
- Added PayerPhoneNumber to the Barion request to enable "bank buttons" on Barion
- Updated language support, added Greek language
- Updated Barion library to v1.4.2

= 2.5.0 =

- Fixed floating point precision bug
- Fixed bug in case of changed payment method

= 2.4.0 =

- Added Czech language and CZK support

= 2.3.0 =

- Added newsletter signup

= 2.1.0 =

- Cancelling payment on the Barion page will redirect the customer to the cart page (default) instead of the order received page
- Updated test credit card details

= 1.3.1 =

- Ignoring Barion callback in case the payment was already completed.

= 1.3.0 =

- Changed the text domain to match the wordpress.org slug.

= 1.2.0 =

- Added option to select the order state after a successful payment
- Fixed dropdown heigh issue

= 1.1.0 =

- Updated Barion Library to the latest version
- Added supported languages (DE, SL, SK, FR)
- Substituted deprecated function calls with current ones

= 1.0.2 =

- Updated Barion Library to the latest version

= 1.0.1 =

- Added link to the icon on the checkout page

= 1.0.0 =

- Added support for EUR/USD payment

= 0.9.0 =

- Added Refund payment via Barion.
- Fixed localization.
- Fixed small bugs.

= 0.8.0 =

- Disabling the gateway in case of unsupported currency selected.
- Sending variation attributes to Barion in case of Variable product.

= 0.7.2 =

- Cleanup + added a more descriptive description to the admin page.
- Added coupons as items to the Barion transaction.
- Modified shipping cost calculation to allow multiple shipping items.

= 0.7.1 =

- Added Barion card payment banner to the checkout page.
- Various small bugfixes
- Fixed missing localization realm for order unit.

= 0.6.1 =

- Made the plugin wordpress.org ready by adding a readme.txt and moving stuff around.
- Added build script to create release and upload zip file.
- Added build scripts to make the release process easier.

= 0.6.0 =

- Added Barion payment gateway
- Added Barion prepare and redirect calls done.
- Created project structure
- Added Barion PHP library as a submodule.
- Available in English and Hungarian
