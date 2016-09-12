<?php
/*
Plugin Name: Barion Payment Gateway for WooCommerce
Plugin URI: http://github.com/szelpe/woocommerce-barion
Description: Adds the ability to WooCommerce to pay via Barion
Version: 0.8.0
Author: Peter Szel <szelpeter@szelpeter.hu>
Author URI: http://szelpeter.hu
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Text Domain: woocommerce-barion
Domain Path: /languages/

*/

add_action('plugins_loaded', 'woocommerce_gateway_barion_init', 0);

function woocommerce_gateway_barion_init() {
    if (!class_exists('WC_Payment_Gateway'))
        return;
    
    load_plugin_textdomain('woocommerce-barion', false, plugin_basename(dirname(__FILE__)) . "/languages");
    
    require_once('class-wc-gateway-barion.php');
    
    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_gateway_barion_gateway($methods) {
        $methods[] = 'WC_Gateway_Barion';
        return $methods;
    }
    
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_barion_gateway' );
}
