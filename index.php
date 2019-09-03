<?php
/*
Plugin Name: Barion Payment Gateway for WooCommerce
Plugin URI: http://github.com/szelpe/woocommerce-barion
Description: Adds the ability to WooCommerce to pay via Barion
Version: 3.0.1
Author: Peter Szel <szelpeter@szelpeter.hu>
Author URI: http://szelpeter.hu
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Text Domain: pay-via-barion-for-woocommerce
Domain Path: /languages

*/

$plugin = new WooCommerce_Barion_Plugin();

class WooCommerce_Barion_Plugin {
    /**
     * @var WC_Gateway_Barion_Profile_Monitor
     */
    private $profile_monitor;
    private $wc_gateway_barion;

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init'], 0);
    }

    function init() {
        if (!class_exists('WC_Payment_Gateway'))
            return;

        load_plugin_textdomain('pay-via-barion-for-woocommerce', false, plugin_basename(dirname(__FILE__)) . "/languages");

        require_once 'includes/class-wc-gateway-barion-profile-monitor.php';

        $this->profile_monitor = new WC_Gateway_Barion_Profile_Monitor();


        require_once 'class-wc-gateway-barion.php';
        $this->wc_gateway_barion = new WC_Gateway_Barion($this->profile_monitor);

        require_once 'includes/class-wc-gateway-barion-pixel.php';
        $barion_pixel = new WC_Gateway_Barion_Pixel($this->wc_gateway_barion->get_barion_pixel_id());

        add_filter('woocommerce_payment_gateways', [$this, 'woocommerce_add_gateway_barion_gateway']);
    }

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_gateway_barion_gateway($methods) {
        $methods[] = $this->wc_gateway_barion;
        return $methods;
    }
}
