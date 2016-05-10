# Barion Payment Gateway for WooCommerce

This plugin allows your customers to pay via [Barion Smart Gateway](https://www.barion.com/) in your WooCommerce online store.

## Features

- Adds Barion as a payment option to the WooCommerce checkout page
- Redirects the user to the Barion payment page after checkout
- Handles the callback from Barion after payment
  - sets the order status to "Succeeded" or "Failed" respectively

**Refunds are not supported yet.**

## Installation

1. Upload 'woocommerce-barion' to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Click on the menu item "WooCommerce" then select the "Checkout" tab
1. Click on new submenu item named "Barion"
1. On this page you should set the POSKey and the Payee e-mail address 
1. Select the Barion environment and enable the payment method if you're ready to use Barion

![](checkout-settings.png)

## Feedback

I'd be happy to hear your feedback! Feel free to contact me at szelpeter@szelpeter.hu 

## Contribution

You're welcome to contribute to this open source plugin by creating pull-requests on [Github](https://github.com/szelpe/woocommerce-barion). To do this, you need to fork the repository, implement the changes and push them to your fork. After that you can create a pull request to merge changes from your fork the main repository.

## Bugs

[Please report bugs as Github issues.](https://github.com/szelpe/woocommerce-barion/issues)