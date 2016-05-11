=== Barion Payment Gateway for WooCommerce ===
Contributors: szelpe
Tags: woocommerce, barion, gateway, payment
Requires at least: 4.0
Tested up to: 4.5.2
Stable tag: 0.6.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin allows your customers to pay via Barion Smart Gateway in your WooCommerce online store.

== Description ==

This plugin allows your customers to pay via [Barion Smart Gateway](https://www.barion.com/) in your WooCommerce online store.

## Features

- Adds Barion as a payment option to the WooCommerce checkout page
- Redirects the user to the Barion payment page after checkout
- Handles the callback from Barion after payment
  - sets the order status to "processing", "completed" or "failed" respectively

**Refunds are not supported yet.**

## Feedback

I'd be happy to hear your feedback! Feel free to contact me at szelpeter@szelpeter.hu 

## Contribution

You're welcome to contribute to this open source plugin by creating pull-requests on [Github](https://github.com/szelpe/woocommerce-barion). To do this, you need to fork the repository, implement the changes and push them to your fork. After that you can create a pull request to merge changes from your fork the main repository.

## Bugs

[Please report bugs as Github issues.](https://github.com/szelpe/woocommerce-barion/issues)

== Installation ==

1. Navigate to the [latest release](https://github.com/szelpe/woocommerce-barion/releases/latest) and download `woocommerce-barion.zip`
1. Extract and upload "woocommerce-barion" to the "/wp-content/plugins/" directory
1. Activate the plugin through the "Plugins" menu in WordPress
1. Click on the menu item "WooCommerce" then select the "Checkout" tab
1. Click on the new submenu item named "Barion"
1. On this page you should set the POSKey of the shop and your Barion email address 
1. Should you want to use the test envorinment, select it from the Barion Environment dropdown. You'll need to create a shop on the [Barion test page](https://test.barion.com).
1. Enable the payment method if you're ready to use Barion

== Screenshots ==

1. Settings on the WooCommerce Settings > Checkout page